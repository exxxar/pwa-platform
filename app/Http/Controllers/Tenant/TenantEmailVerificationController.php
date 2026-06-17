<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class TenantEmailVerificationController extends Controller
{
    public function verifyEmailPage()
    {
        return Inertia::render('Tenant/Auth/VerifyEmail', [
            'tenant' => tenant(),
            'user'   => auth('tenant')->user(),
            'status' => session('status'),
        ]);
    }

    public function resend()
    {
        $user = Auth::guard('tenant')->user();

        // Здесь ты можешь отправить email
        // Пока просто имитируем отправку
        return back()->with('status', 'Письмо отправлено');
    }

    public function verify($id, $hash)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user || $user->id != $id) {
            abort(403);
        }

        // Помечаем email как подтверждённый
        $user->email_verified_at = now();
        $user->save();

        return redirect('/')->with('status', 'Email подтверждён');
    }
}
