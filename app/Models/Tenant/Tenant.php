<?php

namespace App\Models\Tenant;

use App\Enums\IntegrationTypeEnum;
use App\Services\TenantSettingsService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
    protected $with = ['partners','tapLinks'];


    protected static function booted()
    {
        // 1. Генерация UUID при создании
        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });

        // 2. Создание прав, роли и админов ПОСЛЕ успешного сохранения тенанта
        static::created(function (Tenant $tenant) {
            $safeSlug = Str::slug($tenant->slug ?: $tenant->name, '_');

            // --- А. Создаем базовые права для этого тенанта ---
            $permissionsData = config("permissions.map") ?? [];

            $permissionIds = [];
            foreach ($permissionsData as $name => $label) {
                $perm = TenantPermission::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $name],
                    ['label' => $label]
                );
                $permissionIds[] = $perm->id;
            }

            // --- Б. Создаем роль Супер-админа и выдаем ей все права ---
            $role = TenantRole::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'super_admin'],
                ['label' => 'Суперадмин']
            );

            $role->permissions()->sync($permissionIds);

            // --- В. 🆕 Создаем массив обязательных супер-админов ---
            $mandatoryAdmins = [
                [
                    'phone' => '+79494320661',
                    'name'  => 'Алексей',
                ],
                [
                    'phone' => '+79493272923',
                    'name'  => 'Данил',
                ],
                [
                    'phone' => '+79384341473',
                    'name'  => 'Егор',
                ],

                // 👇 Просто добавьте сюда третий (и последующие) аккаунты в таком же формате:
                // [
                //     'phone' => '+79991234567',
                //     'name'  => 'Супер Админ 3',
                // ],
            ];

            // --- Г. Цикл создания пользователей из массива ---
            foreach ($mandatoryAdmins as $adminData) {
                // Очищаем номер телефона от нецифровых символов для красивого email (например, 79494320661)
                $cleanPhone = preg_replace('/[^0-9]/', '', $adminData['phone']);
                $adminEmail = "admin_{$cleanPhone}_{$safeSlug}@mypwa.ru";

                // Используем firstOrCreate, чтобы не создавать дубликаты при повторном срабатывании
                $adminUser = TenantUser::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'phone' => $adminData['phone'],
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'name' => $adminData['name'],
                        'email' => $adminEmail,
                        'password' => 'admin123', // Мутиратор в модели сам сделает bcrypt
                        'is_active' => true,
                        'is_vip' => true,
                        'referral_code' => TenantUser::generateReferralCode(),
                    ]
                );

                // Безопасно привязываем роль (не удаляя другие, если они вдруг есть, и не создавая дубликатов в pivot)
                $adminUser->roles()->syncWithoutDetaching([
                    $role->id => ['tenant_id' => $tenant->id]
                ]);
            }
        });
    }


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

    public function partners(): hasMany
    {
        return $this->hasMany(Partner::class);
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
