<?php

namespace App\Models\Tenant;

use App\Enums\IntegrationTypeEnum;
use App\Services\TenantSettingsService;
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
        'short_name',
        'description',
        'icon',
        'theme_color',
        'background_color',
        'app_type',
        'meta'
    ];

    protected $casts = [
        "meta" => "array"
    ];

    protected $appends = ['settings', 'topics'];
    protected $with = ['partners'];

    // ... связи остаются без изменений ...

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

    /**
     * 🆕 Получить настройки по умолчанию из конфигов
     */
    public static function defaultSettings(): array
    {
        return TenantSettingsService::getDefaultSettings();
    }

    /**
     * 🆕 Получить конкретную секцию настроек
     */
    public static function getSettingsSection(string $section): array
    {
        return TenantSettingsService::getSection($section);
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function () {
                // 1. Получаем значение meta
                $currentMeta = $this->meta;

                // 2. Если по какой-то причине это строка (старые данные или двойной json_encode) - декодируем
                if (is_string($currentMeta)) {
                    $currentMeta = json_decode($currentMeta, true) ?? [];
                }
                // 3. Если это null или не массив - делаем пустым массивом
                elseif (!is_array($currentMeta)) {
                    $currentMeta = [];
                }

                // 4. Безопасно объединяем с настройками по умолчанию
                return array_replace_recursive(
                    self::defaultSettings(),
                    $currentMeta
                );
            }
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

    public function tapLinks(): HasMany
    {
        return $this->hasMany(TenantTapLink::class)->orderBy('sort_order', 'asc');
    }

    public function getActiveTapLinksAttribute()
    {
        return $this->tapLinks()->where('is_active', true)->get();
    }
}
