<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;


class TenantSocialAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('vkontakte')
            ->scopes(['email'])
            ->redirect();
    }

    public function callback()
    {
        $vkUser = Socialite::driver('vkontakte')->user();

        // Определяем tenant по домену или slug
        $tenant = app('currentTenant'); // если у тебя есть TenantResolver

        // Ищем пользователя
        $user = TenantUser::where('tenant_id', $tenant->id)
            ->where('vk_id', $vkUser->id)
            ->first();

        if (!$user) {
            $user = TenantUser::create([
                'tenant_id' => $tenant->id,
                'name'      => $vkUser->name,
                'email'     => $vkUser->email,
                'vk_id'     => $vkUser->id,
                'meta'      => [
                    'avatar' => $vkUser->avatar,
                ],
            ]);
        }

        Auth::guard('tenant')->login($user);

        return redirect('/'); // в PWA
    }
}

