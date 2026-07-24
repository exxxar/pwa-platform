<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; // 🆕 Добавляем Query Builder

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tenant_partner_id',
        'title',
        'description',
        'tags',
        'order_position',
        'image',
        'is_active',
        'extra_charge',
        'config',
        'legal_info',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'legal_info' => 'array',
        'tags' => 'array',
    ];

    // 🆕 Добавляем виртуальные атрибуты
    protected $appends = [
        'address',
        'shop_coords',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function partner()
    {
        return $this->belongsTo(Tenant::class, 'tenant_partner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (Обход рекурсии через DB::table)
    |--------------------------------------------------------------------------
    */

    /**
     * 🆕 Получаем настройки из связанного Tenant БЕЗ использования Eloquent-связи
     * Это предотвращает бесконечный цикл загрузки связей.
     */
    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->tenant_partner_id) return [];

                // 🆕 Простой кэш в рамках жизненного цикла запроса
                static $cache = [];

                if (isset($cache[$this->tenant_partner_id])) {
                    return $cache[$this->tenant_partner_id];
                }

                $rawMeta = DB::table('tenants')->where('id', $this->tenant_partner_id)->value('meta');
                $meta = is_string($rawMeta) ? json_decode($rawMeta, true) : ($rawMeta ?: []);

                $cache[$this->tenant_partner_id] = $meta;
                return $meta;
            }
        );
    }

    /**
     * 🆕 Удобный аксессор для адреса
     */
    protected function address(): Attribute
    {
        return Attribute::make(
            get: function () {
               /* $settings = $this->settings; // Использует аксессор выше

                return $settings['company']['address']
                    ?? $settings['shop']['address']
                    ?? $settings['address']
                    ?? null;*/
                return "г. Донецк, ул. Аретам, 2б";
            }
        );
    }

    /**
     * 🆕 Удобный аксессор для координат
     */
    protected function shopCoords(): Attribute
    {
        return Attribute::make(
            get: function () {
               /* $settings = $this->settings;

                return $settings['shop']['shop_coords']
                    ?? $settings['shop_coords']
                    ?? '0,0';*/
                return "31.4455223, 45.0033244";
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_position');
    }

    public function scopeWhereTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeWhereTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    public function partnerProducts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class, 'tenant_id', 'tenant_partner_id');
    }

    public function partnerCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class, 'tenant_id', 'tenant_partner_id');
    }
}
