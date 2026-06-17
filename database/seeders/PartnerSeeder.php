<?php

namespace Database\Seeders;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;
use App\Models\Tenant\Partner;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where("slug","test")
            ->first();

        $tenantPartner = Tenant::query()
            ->where("slug","fastoran")
            ->first();

        $partners = [
            [
                'tenant_id' => $tenant->id,
                'tenant_partner_id' => $tenantPartner->id,
                'title' => 'Glovo',
                'description' => 'Быстрая доставка еды и товаров',
                'order_position' => 1,
                'image' => 'partners/glovo.png',
                'is_active' => true,
                'extra_charge' => 0,
                'config' => [
                    'api_enabled' => true,
                    'delivery_time_min' => 30,
                ],
                'legal_info' => [
                    'company' => 'Glovo Inc.',
                    'bin' => '123456789',
                ],
            ],
           /* [
                'tenant_id' => $tenant->id,
                'tenant_partner_id' => null,
                'title' => 'Wolt',
                'description' => 'Доставка из ресторанов',
                'order_position' => 2,
                'image' => 'partners/wolt.png',
                'is_active' => true,
                'extra_charge' => 5.5,
                'config' => [
                    'api_enabled' => true,
                    'delivery_time_min' => 40,
                ],
                'legal_info' => [
                    'company' => 'Wolt Ltd.',
                    'bin' => '987654321',
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'tenant_partner_id' => null,
                'title' => 'Яндекс Доставка',
                'description' => 'Курьерская доставка',
                'order_position' => 3,
                'image' => 'partners/yandex.png',
                'is_active' => true,
                'extra_charge' => 10,
                'config' => [
                    'api_enabled' => false,
                    'manual_dispatch' => true,
                ],
                'legal_info' => [
                    'company' => 'Yandex LLC',
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'tenant_partner_id' => null,
                'title' => 'Самовывоз',
                'description' => 'Клиент забирает заказ сам',
                'order_position' => 4,
                'image' => 'partners/pickup.png',
                'is_active' => true,
                'extra_charge' => 0,
                'config' => [
                    'type' => 'pickup',
                ],
                'legal_info' => null,
            ],
            [
                'tenant_id' => $tenantId,
                'tenant_partner_id' => null,
                'title' => 'Test Partner (disabled)',
                'description' => 'Тестовый неактивный партнер',
                'order_position' => 5,
                'image' => null,
                'is_active' => false,
                'extra_charge' => 0,
                'config' => [],
                'legal_info' => [],
            ],*/
        ];

        foreach ($partners as $partnerData) {
            Partner::create($partnerData);
        }
    }
}
