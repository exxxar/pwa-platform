<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TenantUserResolver
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->tenant;
        $tempUser = Auth::guard('tenant')->user();

        if (!is_null($tempUser)) {
            return $next($request);
        }

        $uuid = $request->session()->get('user_uuid');
        $header = $request->header('X-Integration-Auth');

        if ($header) {
            $decoded = base64_decode($header);
            [$tenantId, $uuid] = explode(':', $decoded, 2);
        }

        $user = null;

        if ($uuid) {
            $user = TenantUser::query()
                ->with(["roles"])
                ->where('uuid', $uuid)
                ->first();
        }

        if (!$user) {
            $role = TenantRole::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'guest',
            ]);

            // 🆕 1. Получаем дефолтные значения из конфига
            $defaultIdentities = config('guests.default_identities', []);
            $defaultWelcomeMessage = config('guests.default_welcome_message', 'Добро пожаловать!');

            // 🆕 2. Получаем кастомные настройки тенанта (если они есть)
            $settings = $tenant->settings ?? [];
            $guestSettings = $settings['guests'] ?? [];

            // Если в БД задан непустой массив, используем его. Иначе берем из конфига.
            $identities = $guestSettings['identities'] ?? null;
            if (!is_array($identities) || count($identities) === 0) {
                $identities = $defaultIdentities;
            }

            // 🆕 3. Выбираем случайное имя и формируем полное имя
            $randomIdentity = $identities[array_rand($identities)];
            $guestName = 'Гость • ' . $randomIdentity;

            $user = TenantUser::query()->create([
                'tenant_id' => $tenant->id,
                'name'      => $guestName,
                'uuid'      => (string) Str::uuid(),
            ]);

            $dialog = TenantDialog::query()->create([
                'tenant_id'      => $tenant->id,
                'tenant_user_id' => $user->id,
                'type'           => "system",
                'title'          => "Сообщение от администрации"
            ]);

            // 🆕 4. Формируем приветствие (кастомное из БД или дефолтное из конфига)
            $animalName = str_replace('Гость • ', '', $guestName);
            $welcomeTemplate = $guestSettings['welcome_message'] ?? $defaultWelcomeMessage;

            $welcomeMessage = str_replace('{name}', $animalName, $welcomeTemplate);

            TenantMessage::query()->create([
                'tenant_id' => $tenant->id,
                'dialog_id' => $dialog->id,
                'message'   => $welcomeMessage
            ]);

            $user->roles()->syncWithoutDetaching([
                $role->id => [
                    'tenant_id' => $user->tenant_id,
                ]
            ]);

            $request->session()->put('user_uuid', $user->uuid);
        }

        Auth::guard('tenant')->login($user);

        return $next($request);
    }
}
