<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;
use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TenantUserResolver
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->tenant;

        if (!$tenant) {
            return $next($request);
        }

        $tempUser = Auth::guard('tenant')->user();



        if (!is_null($tempUser)) {
            // 🆕 Даже для авторизованных пользователей сохраняем реферальный код в сессию (для аналитики)
            $this->handleReferralCodeForExistingUser($request);
            return $next($request);
        }

        $uuid = $request->session()->get('user_uuid');
        $header = $request->header('X-Integration-Auth');

        $parsedTenantId = null;
        $parsedUuid = null;

        if ($header) {
            $decoded = base64_decode($header);
            if (str_contains($decoded, ':')) {
                [$parsedTenantId, $parsedUuid] = explode(':', $decoded, 2);
                if ($parsedUuid && !$uuid) {
                    $uuid = $parsedUuid;
                }
            }
        }

        // 🆕 1. Получаем реферальный код из query params или сессии
        $referralCode = $request->get('ref') ?? $request->session()->get('referral_code');


        $user = null;

        if ($uuid) {
            $user = TenantUser::query()
                ->with(["roles"])
                ->where('uuid', $uuid)
                ->first();
        }

        if (!$user) {
            $user = $this->createGuestUser($tenant, $referralCode);
        } else {
            // 🆕 Обеспечиваем наличие реферального кода у существующего пользователя
            $this->ensureUserHasReferralCode($user);

            // 🆕 2. Пользователь существует — проверяем, можно ли его привязать к рефереру
            $this->tryLinkExistingUserToReferrer($user, $referralCode);
        }

        // 🆕 3. Сохраняем реферальный код в сессию для будущих действий
        if ($referralCode && !$request->session()->has('referral_code')) {
            $request->session()->put('referral_code', $referralCode);
        }

        config(['session.lifetime' => 43200]);
        Auth::guard('tenant')->login($user, true);

        return $next($request);
    }

    /**
     * 🆕 Обеспечивает наличие реферального кода у пользователя
     * Если кода нет — генерирует и сохраняет
     */
    private function ensureUserHasReferralCode(TenantUser $user): void
    {
        if (empty($user->referral_code)) {
            $referralCode = TenantUser::generateReferralCode();
            $user->referral_code = $referralCode;
            $user->save();

            Log::info("🎫 Сгенерирован реферальный код для существующего пользователя", [
                'user_id' => $user->id,
                'referral_code' => $referralCode
            ]);
        }
    }
    /**
     * 🆕 Создание гостевого пользователя с реферальным кодом
     */
    private function createGuestUser($tenant, ?string $referralCode): TenantUser
    {
        $role = TenantRole::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'guest',
        ]);

        $defaultIdentities = config('guests.default_identities', []);
        $defaultWelcomeMessage = config('guests.default_welcome_message', 'Добро пожаловать!');

        $settings = $tenant->settings ?? [];
        $guestSettings = $settings['guests'] ?? [];

        $identities = $guestSettings['identities'] ?? null;
        if (!is_array($identities) || count($identities) === 0) {
            $identities = $defaultIdentities;
        }

        if (empty($identities)) {
            $identities = ['Неизвестный'];
        }
        $randomIdentity = $identities[array_rand($identities)];
        $guestName = 'Гость • ' . $randomIdentity;

        // 🆕 ВАЖНО: Генерируем реферальный код для гостя!
        $referralCodeForUser = TenantUser::generateReferralCode();

        $user = TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $guestName,
            'uuid' => (string) Str::uuid(),
            'referral_code' => $referralCodeForUser, // 🆕 Сохраняем сгенерированный код
            'is_active' => true,

            'referrals_count' => 0,
            'total_referral_earnings' => 0,
        ]);

        // Создаём системный диалог
        $dialog = TenantDialog::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $user->id,
            'type' => "system",
            'title' => "Сообщение от администрации"
        ]);

        $animalName = str_replace('Гость • ', '', $guestName);
        $welcomeTemplate = $guestSettings['welcome_message'] ?? $defaultWelcomeMessage;
        $welcomeMessage = str_replace('{name}', $animalName, $welcomeTemplate);

        TenantMessage::query()->create([
            'tenant_id' => $tenant->id,
            'dialog_id' => $dialog->id,
            'message' => $welcomeMessage
        ]);

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'tenant_id' => $user->tenant_id,
            ]
        ]);

        request()->session()->put('user_uuid', $user->uuid);

        // 🆕 4. Если есть реферальный код — привязываем гостя к рефереру
        if ($referralCode) {
            $success = $this->referralService->registerReferral($user, $referralCode);

            if ($success) {
                Log::info("✅ Гость привязан к рефереру", [
                    'guest_id' => $user->id,
                    'referral_code' => $referralCode,
                    'guest_referral_code' => $referralCodeForUser
                ]);
            }
        }

        return $user;
    }

    /**
     * 🆕 Попытка привязать существующего пользователя к рефереру
     * (только если у него ещё нет referrer)
     */
    private function tryLinkExistingUserToReferrer(TenantUser $user, ?string $referralCode): void
    {
        if (!$referralCode) {
            return;
        }

        // Если у пользователя уже есть реферер — не меняем
        if ($user->referred_by) {
            Log::debug("⚠️ Пользователь уже имеет реферера", [
                'user_id' => $user->id,
                'existing_referrer_id' => $user->referred_by,
                'attempted_referral_code' => $referralCode
            ]);
            return;
        }

        // Пытаемся привязать
        $success = $this->referralService->registerReferral($user, $referralCode);

        if ($success) {
            Log::info("✅ Существующий пользователь привязан к рефереру", [
                'user_id' => $user->id,
                'referral_code' => $referralCode
            ]);
        }
    }

    /**
     * 🆕 Обработка реферального кода для уже авторизованных пользователей
     * (сохраняем в сессию для аналитики)
     */
    private function handleReferralCodeForExistingUser(Request $request): void
    {
        $referralCode = $request->query('ref');

        if ($referralCode && !$request->session()->has('referral_code')) {
            $request->session()->put('referral_code', $referralCode);

            Log::debug("💾 Реферальный код сохранён в сессию для авторизованного пользователя", [
                'user_id' => Auth::guard('tenant')->id(),
                'referral_code' => $referralCode
            ]);
        }
    }
}
