<?php

namespace App\Services\Admin\Global;

use App\Models\Admin\User;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    /**
     * Получить список админов
     */
    public function getAdmins(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Создать админа
     */
    public function createAdmin(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Мутиратор в модели сделает bcrypt
        ]);

        // Назначаем роли
        if (!empty($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return $user->load('roles');
    }

    /**
     * Обновить админа
     */
    public function updateAdmin(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        // Синхронизируем роли
        if (isset($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return $user->fresh()->load('roles');
    }

    /**
     * Удалить админа
     */
    public function deleteAdmin(User $user): bool
    {
        return $user->delete();
    }
}
