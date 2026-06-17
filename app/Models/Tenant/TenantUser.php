<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
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
        'email',
        'phone',
        'sex',
        'password',
        'birthday',
        'city',
        'country',
        'address',
        'meta',
        'parent_id',
        'blocked_at',
        'blocked_message',
    ];

    protected $casts = [
        'birthday' => 'date',
        'blocked_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = ['role_names','default_address', 'settings','cashback_balance','cashback_subs'];


    protected $with = ["cashbacks" , "addresses"];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    protected function roleNames(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->roles->pluck('name')->toArray()
        );
    }

    public function parent()
    {
        return $this->belongsTo(TenantUser::class, 'parent_id');
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

    public function scopeActive($query)
    {
        return $query->whereNull('blocked_at');
    }

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
            set: fn ($value) => $value ? bcrypt($value) : null,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name
        );
    }

    public function addresses()
    {
        return $this->hasMany(TenantUserAddress::class, 'tenant_user_id');
    }

    protected function defaultAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->addresses()
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
            set: fn ($value) => $value
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
            get: fn () => (float) $this->cashBacks()->sum('amount'),
        );
    }

    public function dialogs()
    {
        return $this->hasMany(TenantDialog::class);
    }
}
