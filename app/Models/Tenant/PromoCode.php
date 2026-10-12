<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'description',
        'discount',              // Размер скидки
        'discount_type',         // 'percent' или 'fixed'
        'min_order_amount',      // Минимальная сумма заказа
        'starts_at',             // Дата начала действия
        'expires_at',            // Дата окончания действия
        'usage_limit',           // Общий лимит активаций (0 = безлимит)
        'for_new_users',         // Только для новых
        'for_vip',               // Только для VIP
        'is_active',
        'config',                // Доп. настройки (JSON)
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'discount' => 'float',
        'min_order_amount' => 'float',
        'usage_limit' => 'integer',
        'for_new_users' => 'boolean',
        'for_vip' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'expires_at' => 'date',
        'config' => 'array',
    ];

    protected $with = ["users"];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(TenantUser::class, 'promo_code_user', 'promo_code_id', 'tenant_user_id');
    }
}
