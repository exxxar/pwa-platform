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
            // ✅ Обеспечиваем наличие кода и для уже авторизованных
            $this->ensureUserHasReferralCode($tempUser);
            $this->handleReferralCodeForExistingUser($request);
            return $next($request);
        }

        $uuid = $request->session()->get('user_uuid');
        $header = $request->header('X-Integration-Auth');

        if ($header) {
            $decoded = base64_decode($header);
            if (str_contains($decoded, ':')) {
                [$parsedTenantId, $parsedUuid] = explode(':', $decoded, 2);
                if ($parsedUuid && !$uuid) {
                    $uuid = $parsedUuid;
                }
            }
        }

        $referralCode = $request->get('ref') ?? $request->session()->get('referral_code');

        $user = null;

        if ($uuid) {
            // ✅ Ищем только в рамках текущего тенанта
            $user = TenantUser::query()
                ->with(["roles"])
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $uuid)
                ->first();
        }

        if (!$user) {
            try {
                $user = $this->createGuestUser($tenant, $referralCode, $request);
            } catch (\Illuminate\Database\QueryException $e) {
                // Защита от race condition
                if (str_contains($e->getMessage(), 'Duplicate') && $uuid) {
                    $user = TenantUser::query()
                        ->with(["roles"])
                        ->where('tenant_id', $tenant->id)
                        ->where('uuid', $uuid)
                        ->first();
                }
                if (!$user) {
                    throw $e;
                }
            }
        } else {
            $this->ensureUserHasReferralCode($user);
            $this->tryLinkExistingUserToReferrer($user, $referralCode);
        }

        if ($referralCode && !$request->session()->has('referral_code')) {
            $request->session()->put('referral_code', $referralCode);
        }

        Auth::guard('tenant')->login($user, true);

        return $next($request);
    }

    private function ensureUserHasReferralCode(TenantUser $user): void
    {
        if (empty($user->referral_code)) {
            $referralCode = TenantUser::generateReferralCode();
            $user->referral_code = $referralCode;
            $user->saveQuietly(); // ✅ Без триггеров

            Log::info("🎫 Сгенерирован реферальный код", [
                'user_id' => $user->id,
                'referral_code' => $referralCode
            ]);
        }
    }

    private function createGuestUser($tenant, ?string $referralCode, Request $request): TenantUser
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

        $referralCodeForUser = TenantUser::generateReferralCode();

        $user = TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $guestName,
            'uuid' => (string) Str::uuid(),
            'referral_code' => $referralCodeForUser,
            'is_active' => true,
            'referrals_count' => 0,
            'total_referral_earnings' => 0,
        ]);

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

        // ✅ Используем $request вместо глобального helper
        $request->session()->put('user_uuid', $user->uuid);

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

    private function tryLinkExistingUserToReferrer(TenantUser $user, ?string $referralCode): void
    {
        if (!$referralCode) {
            return;
        }

        if ($user->referred_by) {
            Log::debug("⚠️ Пользователь уже имеет реферера", [
                'user_id' => $user->id,
                'existing_referrer_id' => $user->referred_by,
                'attempted_referral_code' => $referralCode
            ]);
            return;
        }

        $success = $this->referralService->registerReferral($user, $referralCode);

        if ($success) {
            Log::info("✅ Существующий пользователь привязан к рефереру", [
                'user_id' => $user->id,
                'referral_code' => $referralCode
            ]);
        }
    }

    private function handleReferralCodeForExistingUser(Request $request): void
    {
        $referralCode = $request->query('ref');

        if ($referralCode && !$request->session()->has('referral_code')) {
            $request->session()->put('referral_code', $referralCode);

            Log::debug("💾 Реферальный код сохранён в сессию", [
                'user_id' => Auth::guard('tenant')->id(),
                'referral_code' => $referralCode
            ]);
        }
    }
}
