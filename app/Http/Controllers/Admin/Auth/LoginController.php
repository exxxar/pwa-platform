<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Пытаемся авторизовать пользователя
        if (!Auth::guard('web')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учетные данные.'],
            ]);
        }

        // Регенерируем сессию для защиты от CSRF-атак
        $request->session()->regenerate();

        // 🆕 ВОТ ГЛАВНОЕ ИЗМЕНЕНИЕ:
        // Возвращаем редирект. Inertia автоматически перехватит его,
        // сделает GET-запрос по новому адресу и отрендерит Vue-компонент.
        return redirect()->intended(route('admin.dashboard'));
        // Если роут не именован, можно использовать: return redirect('/admin');
    }
}
