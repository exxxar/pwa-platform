<?php

namespace App\Models\Tenant;

use App\Enums\IntegrationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'credentials',
        'settings',
        'is_active',
        'last_error',
        'last_synced_at',
    ];

    protected $casts = [
        'credentials' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'type' => IntegrationTypeEnum::class,
    ];

    /*
     |--------------------------------------------------------------------------
     | RELATIONS
     |--------------------------------------------------------------------------
     */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /*
     |--------------------------------------------------------------------------
     | HELPERS
     |--------------------------------------------------------------------------
     */

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getCredential(string $key, $default = null)
    {
        return data_get($this->credentials, $key, $default);
    }

    public function setCredential(string $key, $value): void
    {
        $data = $this->credentials ?? [];
        data_set($data, $key, $value);
        $this->credentials = $data;
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): void
    {
        $data = $this->settings ?? [];
        data_set($data, $key, $value);
        $this->settings = $data;
    }
}
