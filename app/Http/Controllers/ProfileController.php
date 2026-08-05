<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Tenant\TenantUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }


    /**
     * 🆕 Смена/установка пароля
     *
     * Логика:
     * - Если у пользователя пароля НЕТ (NULL) — устанавливаем новый БЕЗ проверки текущего
     * - Если пароль УЖЕ установлен — требуем текущий для безопасности
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        // 🎯 Используем аксессор модели, а не прямую проверку $user->password
        $hasPassword = $user->has_password;

        // Правила валидации зависят от того, был ли пароль ранее
        $rules = [
            'new_password' => 'required|string|min:6|confirmed',
        ];

        if ($hasPassword) {
            // Пароль уже есть — требуется текущий
            $rules['current_password'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules, [
            'current_password.required' => 'Введите текущий пароль',
            'new_password.required' => 'Введите новый пароль',
            'new_password.min' => 'Пароль должен содержать минимум 6 символов',
            'new_password.confirmed' => 'Пароли не совпадают',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 🛡️ Если пароль уже был — проверяем текущий
        if ($hasPassword) {
            // Дополнительная защита: если в БД вдруг не NULL, но Hash::check падает
            if (empty($user->password) || !Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Текущий пароль неверный',
                    'errors' => [
                        'current_password' => ['Неверный текущий пароль'],
                    ],
                ], 422);
            }
        }

        // 🔄 Обновляем пароль
        $user->update([
            'password' => $request->new_password, // Мутатор сам сделает bcrypt()
        ]);

        return response()->json([
            'success' => true,
            'message' => $hasPassword
                ? 'Пароль успешно изменён'
                : 'Пароль успешно установлен',
            'data' => [
                'has_password' => true,
            ],
        ]);
    }

    /**
     * Обновление текстовых данных профиля
     */
    public function update(Request $request)
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['message' => 'Не авторизован'], 401);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('tenant_users')->ignore($user->id),
            ],
            'birthday' => 'nullable|date|before:today',
            'city' => 'nullable|string|max:255',
        ]);

        // Обновляем только переданные поля
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Профиль успешно обновлён',
            'data' => $user->fresh(), // Возвращаем обновлённого пользователя
        ]);
    }

    /**
     * Загрузка/обновление аватара
     */
    public function updateAvatar(Request $request)
    {
        /** @var TenantUser $user */
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return response()->json(['message' => 'Не авторизован'], 401);
        }

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Макс 2 МБ
        ]);

        $file = $request->file('avatar');

        // 🆕 Удаляем старый аватар, если он есть и хранится локально
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        // Если у вас поле называется 'photo', замените 'avatar' на 'photo' ниже

        // Сохраняем новый файл
        $path = "/storage/".$file->store('avatars/tenants', 'public');

        // Обновляем запись в БД
        $user->update([
            'avatar' => $path, // Или 'photo' => $path, в зависимости от названия колонки
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Фото профиля обновлено',
            'data' => [
                'avatar_url' => $path,
            ],
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
