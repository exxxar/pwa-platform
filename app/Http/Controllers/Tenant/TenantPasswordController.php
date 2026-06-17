<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\TenantUser;

class TenantPasswordController extends Controller
{
    public function confirmPasswordPage()
    {
        return Inertia::render('Tenant/Auth/ConfirmPassword', [
            'tenant' => tenant(),
            'user'   => auth('tenant')->user(),
        ]);
    }

    public function confirmPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::guard('tenant')->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Неверный пароль']);
        }

        session(['tenant.password_confirmed_at' => now()]);

        return redirect()->intended('/');
    }

    public function forgotPasswordPage()
    {
        return Inertia::render('Tenant/Auth/ForgotPassword', [
            'tenant' => tenant(),
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
        ]);

        $user = TenantUser::where('tenant_id', tenant()->id)
            ->where(function ($q) use ($request) {
                $q->where('email', $request->login)
                    ->orWhere('phone', $request->login);
            })
            ->first();

        if (!$user) {
            return back()->withErrors(['login' => 'Пользователь не найден']);
        }

        // Здесь ты можешь отправить SMS или email
        // Пока просто имитируем отправку
        return back()->with('status', 'Ссылка отправлена');
    }

    public function resetPasswordPage($token)
    {
        return Inertia::render('Tenant/Auth/ResetPassword', [
            'tenant' => tenant(),
            'token'  => $token,
            'login'  => request('login'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'login'                 => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $user = TenantUser::where('tenant_id', tenant()->id)
            ->where(function ($q) use ($request) {
                $q->where('email', $request->login)
                    ->orWhere('phone', $request->login);
            })
            ->first();

        if (!$user) {
            return back()->withErrors(['login' => 'Пользователь не найден']);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        Auth::guard('tenant')->login($user);

        return redirect('/');
    }
}
