<?php

namespace App\Policies\Admin;

use App\Models\Admin\User;
use App\Models\Tenant\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка товаров
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    /**
     * Просмотр конкретного товара
     */
    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('products.view');
    }

    /**
     * Создание товара
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    /**
     * Обновление товара
     */
    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update');
    }

    /**
     * Удаление товара
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete');
    }

    /**
     * Переключение стоп-листа
     */
    public function toggleStopList(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update');
    }

    /**
     * Переключение активности
     */
    public function toggleActive(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update');
    }
}
