<?php

namespace App\Models\Tenant;

use App\Enums\IntegrationTypeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Tenant extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'description',
        'icon',
        'theme_color',
        'app_type',
        'meta'
    ];

    protected $casts = [
        "meta" => "array"
    ];

    protected $appends = ['settings', 'topics'];
    protected $with = ['partners'];

    public function pages()
    {
        return $this->hasMany(TenantPage::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function users()
    {
        return $this->hasMany(TenantUser::class);
    }

    public function roles()
    {
        return $this->hasMany(TenantRole::class);
    }

    public function permissions()
    {
        return $this->hasMany(TenantPermission::class);
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function partners(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }


    public function integrationsByType(IntegrationTypeEnum $type)
    {
        return $this->integrations()
            ->where('type', $type->value)
            ->where('is_active', true);
    }

    public function __call($method, $args)
    {
        $type = IntegrationTypeEnum::fromMethod($method);

        if ($type !== null) {
            return $this->integrationsByType($type);
        }

        return parent::__call($method, $args);
    }

    public static function defaultSettings(): array
    {
        return [
            'sbp' => [
                'sber' => [],
                'tinkoff' => [
                    'tax' => 'osn',
                    'vat' => 'none',
                    'terminal_key' => null,
                    'terminal_password' => null,
                ],
                'selected_sbp_bank' => 'tinkoff',
            ],
            'coffee' => [
                "max"=>6,
                "rules"=>"",
                "enabled" => true
            ],
            'icons' => [],
            'theme' => null,
            'products' => [
                'token' => null,
            ],
            'pick_up_type' => 0,
            'main_menu_btn' => 'К магазинам',
            'shop_display_type' => 0,
            'is_disabled' => false,
            'disabled_text' => "Магазин временно не работает",
            'is_product_list' => false,
            'need_hide_disabled_products' => true,
            'crm' => [
                'is_active' => false,
                'board_uuid' => null,
                'token' => null,
            ],
            'manager' => [
                'link' => null,
                'title' => null,
            ],
            'shop_coords' => '0,0',
            'delivery' => [
                'min_base_delivery_price' => 0,
                'price_per_km' => 0,
                'min_price' => 0,
                'free_shipping_from' => 0,
            ],
            'partners' => [
                "is_active" => false,
                'display_self' => false,
            ],
            'threads' => [

            ],
            'features' => [
                'can_use_sbp' => false,
                'can_use_card' => false,
                'can_use_cash' => false,
                'can_use_booking' => false,
                'need_bonuses_section' => true,
            ],
            'schedule' => [],
            'max_cashback_use_percent' => 15,

        ];
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: fn() => array_replace_recursive(
                self::defaultSettings(),
                $this->meta ?? []
            )
        );
    }

    public function topics(): Attribute
    {
        return Attribute::make(
            get: function () {
                $threads = $this->settings['threads'] ?? null;

                if (is_null($threads)) {
                    return null;
                }

                return Collection::make($threads ?? [])
                    ->mapWithKeys(function ($message) {
                        $key = $message['key'] ?? $message->key ?? null;
                        $value = $message['value'] ?? $message->value ?? null;

                        return $key ? [$key => $value] : [];
                    })
                    ->toArray();
            }
        );
    }

    /**
     * Ссылки Taplink для этого тенанта
     */
    public function tapLinks(): HasMany
    {
        return $this->hasMany(TenantTapLink::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Получение только активных ссылок (удобный хелпер)
     */
    public function getActiveTapLinksAttribute()
    {
        return $this->tapLinks()->where('is_active', true)->get();
    }
}
