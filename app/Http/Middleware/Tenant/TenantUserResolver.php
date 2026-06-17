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
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $tenant = $request->tenant;

        $tempUser = Auth::guard('tenant')->user();

        if (!is_null($tempUser))
            return $next($request);

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
                ->where('uuid', $uuid)->first();
        }


        if (!$user) {
            $role = TenantRole::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'guest',
            ]);

            // создаем нового гостя
            $user = TenantUser::query()
                ->create([
                    'tenant_id' => $tenant->id, // можно менять по текущему контексту
                    'name' => 'Гость',
                    'uuid' => (string)Str::uuid(),
                ]);

            $dialog = TenantDialog::query()
                ->create([
                    'tenant_id'=>$tenant->id,
                    'tenant_user_id'=>$user->id,
                    'type'=>"system",
                    'title'=>"Сообщение от администрации"
                ]);

            $message = TenantMessage::query()
                ->create([
                    'tenant_id'=>$tenant->id,
                    'dialog_id'=>$dialog->id,
                    'message'=>"Приветствуем вас в системе!"
                ]);

            $user->roles()->syncWithoutDetaching([
                $role->id => [
                    'tenant_id' => $user->tenant_id,
                ]
            ]);

            // сохраняем в сессию
            $request->session()->put('user_uuid', $user->uuid);
        }

        // авторизуем гостя на время сессии
        Auth::guard('tenant')->login($user);

        return $next($request);
    }

}
