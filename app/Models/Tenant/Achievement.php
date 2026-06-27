<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'icon',
        'condition_type',
        'condition_value',
        'reward_type',
        'reward_value',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'condition_value' => 'integer',
        'reward_value' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Типы условий
     */
    public const CONDITION_TYPES = [
        'orders_count' => 'Количество заказов',
        'orders_sum' => 'Сумма заказов',
        'reviews_count' => 'Количество отзывов',
        'cashback_earned' => 'Заработано кэшбэка',
        'cashback_spent' => 'Потрачено кэшбэка',
        'friends_invited' => 'Приглашённых друзей',
        'games_played' => 'Сыграно игр',
        'products_viewed' => 'Просмотрено товаров',
        'days_registered' => 'Дней с регистрации',
        'products_in_cart' => 'Товаров добавлено в корзину',
    ];

    /**
     * Типы наград
     */
    public const REWARD_TYPES = [
        'cashback' => 'Кэшбэк (₽)',
        'discount' => 'Скидка (%)',
        'points' => 'Баллы',
        'none' => 'Без награды',
    ];
}
