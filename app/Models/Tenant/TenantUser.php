<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\HasPushSubscriptions;

class TenantUser extends Authenticatable
{
    use  Notifiable, HasPushSubscriptions;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'avatar',
        'email',
        'phone',
        'sex',
        'password',
        'remember_token',
        'birthday',
        'city',
        'country',
        'address',
        'meta',
        'parent_id',
        'blocked_at',
        'blocked_message',

        'referral_code',
        'referred_by',
        'referrals_count',
        'friends_count',
        'total_referral_earnings',
        'cashback',

        'is_active',
        'is_vip',
        'vip_activated_at',
        'vip_expires_at',
    ];

    protected $casts = [
        'birthday' => 'date',
        'blocked_at' => 'datetime',
        'cashback' => 'float',
        'total_referral_earnings' => 'float',
        'meta' => 'array',

        'is_active' => 'boolean',
        'is_vip' => 'boolean',
        'vip_activated_at' => 'datetime',
        'vip_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'role_names',
        'permission_names',
        'default_address',
        'settings',
        'cashback_balance',
        'cashback_subs',
        'referral_link',
        'wheel_wins'

    ];


    protected $with = ["cashbacks", "addresses"];

   /* protected $attributes = [
        'is_active' => true,
        'is_vip' => false,
    ];*/

    // ==========================================
    // 🆕 SCOPES
    // ==========================================

    protected function permissionNames(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->roles->flatMap->permissions->pluck('name')->unique()->values()->toArray()
        );
    }

    /**
     * 🆕 Собрать информацию о пользователе для Telegram-уведомлений.
     *
     * Возвращает единый массив со всеми данными, которые нужны
     * для уведомлений о чатах, заказах и прочих событиях.
     *
     * @param array $extra — дополнительные поля (например, ['order_id' => 42])
     * @return array
     */
    public function getTelegramInfo(array $extra = []): array
    {
        $tenant = app('tenant');
        $baseUrl = request()->getSchemeAndHttpHost();

        $defaultAddress = $this->default_address;

        return array_merge([
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name ?? 'Не указано',
            'phone' => $this->phone ?? 'Не указан',
            'email' => $this->email ?? 'Не указан',
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,

            // Гео
            'city' => $this->city ?? null,
            'country' => $this->country ?? null,
            'address' => $this->address ?? null,
            'default_address' => $defaultAddress ? [
                'address' => $defaultAddress->address ?? null,
                'street' => $defaultAddress->street ?? null,
                'house' => $defaultAddress->house ?? null,
                'flat' => $defaultAddress->flat ?? null,
                'entrance' => $defaultAddress->entrance ?? null,
                'floor' => $defaultAddress->floor ?? null,
                'comment' => $defaultAddress->comment ?? null,
            ] : null,

            // Статусы
            'is_vip' => (bool) $this->is_vip,
            'has_active_vip' => $this->has_active_vip,
            'is_blocked' => $this->isBlocked(),

            // Рефералка
            'referral_code' => $this->referral_code,
            'referrals_count' => (int) $this->referrals_count,
            'cashback_balance' => (float) $this->cashback_balance,

            // Ссылки
            'profile_url' => $baseUrl ? "{$baseUrl}/pwa#/admin/users/{$this->id}" : null,
            'tenant_slug' => $tenant->slug ?? null,
        ], $extra);
    }

    protected function wheelWins(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->meta['wheel_wins'] ?? []
        );
    }

    /**
     * Только активные пользователи
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('blocked_at');
    }

    /**
     * Только заблокированные пользователи
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Только VIP пользователи
     */
    public function scopeVip($query)
    {
        return $query->where('is_vip', true);
    }

    /**
     * Только обычные пользователи (не VIP)
     */
    public function scopeRegular($query)
    {
        return $query->where('is_vip', false);
    }

    /**
     * VIP с действующей подпиской
     */
    public function scopeActiveVip($query)
    {
        return $query->where('is_vip', true)
            ->where(function ($q) {
                $q->whereNull('vip_expires_at')
                    ->orWhere('vip_expires_at', '>', now());
            });
    }

    // ==========================================
    // 🆕 ACCESSORS & MUTATORS
    // ==========================================

    /**
     * Проверка, активен ли VIP статус (с учётом срока)
     */
    public function getHasActiveVipAttribute(): bool
    {
        if (!$this->is_vip) {
            return false;
        }

        if ($this->vip_expires_at && $this->vip_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Осталось дней VIP
     */
    public function getVipDaysLeftAttribute(): ?int
    {
        if (!$this->has_active_vip || !$this->vip_expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->vip_expires_at, false));
    }

    // ==========================================
    // 🆕 МЕТОДЫ УПРАВЛЕНИЯ СТАТУСОМ
    // ==========================================

    /**
     * Активировать пользователя
     */
    public function activate(): self
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    /**
     * Деактивировать пользователя
     */
    public function deactivate(): self
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    /**
     * Выдать VIP статус
     */
    public function grantVip(?int $days = null): self
    {
        $data = [
            'is_vip' => true,
            'vip_activated_at' => now(),
        ];

        if ($days) {
            $data['vip_expires_at'] = now()->addDays($days);
        } else {
            $data['vip_expires_at'] = null; // Бессрочно
        }

        $this->update($data);
        return $this;
    }

    /**
     * Отозвать VIP статус
     */
    public function revokeVip(): self
    {
        $this->update([
            'is_vip' => false,
            'vip_activated_at' => null,
            'vip_expires_at' => null,
        ]);
        return $this;
    }

    /**
     * Продлить VIP
     */
    public function extendVip(int $days): self
    {
        if (!$this->is_vip) {
            return $this->grantVip($days);
        }

        $newExpiry = $this->vip_expires_at && $this->vip_expires_at->isFuture()
            ? $this->vip_expires_at->addDays($days)
            : now()->addDays($days);

        $this->update(['vip_expires_at' => $newExpiry]);
        return $this;
    }


    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Кто пригласил этого пользователя
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'referred_by');
    }

    /**
     * Кого пригласил этот пользователь (прямые рефералы 1-го уровня)
     */
    public function directReferrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_id')
            ->where('level', 1);
    }

    /**
     * Все рефералы (все уровни)
     */
    public function allReferrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    /**
     * Друзья (принятые заявки)
     */
    public function friends()
    {
        return $this->belongsToMany(
            TenantUser::class,
            'user_friends',
            'user_id',
            'friend_id'
        )->wherePivot('status', 'accepted');
    }

    /**
     * Исходящие заявки в друзья
     */
    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(UserFriend::class, 'initiator_id')
            ->where('status', UserFriend::STATUS_PENDING);
    }

    /**
     * Входящие заявки в друзья
     */
    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(UserFriend::class, 'friend_id')
            ->where('status', UserFriend::STATUS_PENDING);
    }

    /**
     * История реферальных наград
     */
    public function referralRewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    // ==========================================
    // МЕТОДЫ
    // ==========================================

    /**
     * Генерация уникального реферального кода
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Получить реферальную ссылку
     */
    public function getReferralLinkAttribute(): string
    {
        $tenant = app('tenant');
        return url("/{$tenant->slug}?ref={$this->referral_code}");
    }

    protected function roleNames(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->roles->pluck('name')->toArray()
        );
    }

    public function parent()
    {
        return $this->belongsTo(TenantUser::class, 'parent_id');
    }

    /**
     * Отзывы пользователя
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'tenant_user_id');
    }

    public function children()
    {
        return $this->hasMany(TenantUser::class, 'parent_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */


    public function scopeBlocked($query)
    {
        return $query->whereNotNull('blocked_at');
    }

    public function scopeFromTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isBlocked(): bool
    {
        return !is_null($this->blocked_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Mutators
    |--------------------------------------------------------------------------
    */

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value ? bcrypt($value) : null,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->name
        );
    }

    public function addresses()
    {
        return $this->hasMany(TenantUserAddress::class, 'tenant_user_id');
    }

    protected function defaultAddress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->addresses()
                ->where('is_default', true)
                ->first()
        );
    }

    public function setDefaultAddress($addressId)
    {
        $this->addresses()->update(['is_default' => false]);

        $this->addresses()
            ->where('id', $addressId)
            ->update(['is_default' => true]);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles()
    {
        return $this->belongsToMany(
            TenantRole::class,
            'tenant_role_user',
            'tenant_user_id',
            'tenant_role_id'
        )->withPivot('tenant_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($q) use ($permission) {
                $q->where('name', $permission);
            })
            ->exists();
    }

    public static function defaultSettings(): array
    {
        return [
            'need_bot_mailing' => false,
            'current_promocodes' => [],
            'favorites' => [2],
            'coffee' => [
                'count' => 0,
            ],
            'fav_partners' => [2, 1],
        ];
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function () {
                return array_replace_recursive(
                    self::defaultSettings(),
                    $this->meta ?? []
                );
            },
            set: fn($value) => $value
        );
    }

    public function cashbacks()
    {
        return $this->hasMany(CashBack::class, 'tenant_user_id');
    }

    protected function cashbackSubs(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->cashBacks()
                    ->select('sub_title', DB::raw('SUM(amount) as total'))
                    ->groupBy('sub_title')
                    ->pluck('total', 'sub_title'); // ['food' => 100, 'taxi' => 50]
            }
        );
    }

    protected function cashbackBalance(): Attribute
    {
        return Attribute::make(
            get: fn() => (float)$this->cashBacks()->sum('amount'),
        );
    }

    public function dialogs()
    {
        return $this->hasMany(TenantDialog::class);
    }
}
