<?php

namespace App\Services\Tenants;

use App\Models\Tenant\Basket;
use App\Models\Tenant\PromoCode;
use App\Models\Tenant\TenantUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PromoCodeService
{
    public static function call(): self
    {
        return app(self::class);
    }

    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    /**
     * Активация промокода на скидку в корзине
     */
    public function activatePromoCodeForDiscount(array $data): object
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, ['code' => 'required|string']);
        if ($validator->fails()) throw new ValidationException($validator);

        $code = PromoCode::query()
            ->where('tenant_id', $tenant->id)
            ->where('code', strtoupper(trim($data['code'])))
            ->first();

        if (!$code) {
            throw new HttpException(404, "Промокод не найден");
        }

        if (!$code->is_active) {
            throw new HttpException(403, "Промокод не активен");
        }

        $now = Carbon::now();
        if ($code->starts_at && $now->lt($code->starts_at)) {
            throw new HttpException(400, "Промокод начнет действовать с " . $code->starts_at->format('d.m.Y'));
        }
        if ($code->expires_at && $now->gt($code->expires_at)) {
            throw new HttpException(400, "Срок действия промокода истек");
        }

        if ($code->for_vip && !$tenantUser->is_vip) {
            throw new HttpException(403, "Этот промокод доступен только для VIP-клиентов");
        }

        if ($code->for_new_users) {
            // Проверяем, есть ли у пользователя завершенные заказы
            $hasOrders = \App\Models\Tenant\Order::where('tenant_user_id', $tenantUser->id)->exists();
            if ($hasOrders) {
                throw new HttpException(403, "Этот промокод доступен только при первом заказе");
            }
        }

        // Проверка общего лимита активаций
        if ($code->usage_limit > 0 && $code->users()->count() >= $code->usage_limit) {
            $code->update(['is_active' => false]);
            throw new HttpException(400, "Лимит активаций этого промокода исчерпан");
        }

        // Проверка, активировал ли уже этот пользователь данный промокод
        if ($code->users()->where('tenant_user_id', $tenantUser->id)->exists()) {
            throw new HttpException(400, "Вы уже активировали этот промокод");
        }

        // Получаем текущую корзину
        $basket = Basket::query()
            ->with('product')
            ->where('tenant_id', $tenant->id)
            ->where('tenant_user_id', $tenantUser->id)
            ->whereNull('ordered_at')
            ->get();

        if ($basket->isEmpty()) {
            throw new HttpException(400, "Корзина пуста. Добавьте товары для применения промокода.");
        }

        $basketTotal = $basket->sum(fn($item) => $item->count * ($item->product->price ?? 0));

        if ($code->min_order_amount > 0 && $basketTotal < $code->min_order_amount) {
            throw new HttpException(400, "Минимальная сумма заказа для этого промокода: " . number_format($code->min_order_amount, 0, '.', ' ') . " ₽");
        }

        // Применяем промокод
        $code->users()->attach($tenantUser->id);

        $inPercent = $code->discount_type === 'percent';
        $val = $code->discount;
        $totalDiscount = 0;

        foreach ($basket as $item) {
            $itemPrice = $item->product->price ?? 0;

            // Рассчитываем скидку на единицу товара
            $discountPerItem = $inPercent ? ($itemPrice * ($val / 100)) : $val;
            $discountPerItem = min($discountPerItem, $itemPrice); // Защита от отрицательной цены

            $params = $item->params ?? [];
            $params['discount_price'] = $itemPrice - $discountPerItem;
            $params['discount_amount'] = $discountPerItem;
            $params['discount_object'] = (object)[
                'code_id' => $code->id,
                'amount' => $val,
                'in_percent' => $inPercent,
            ];

            $item->params = $params;
            $item->save();

            $totalDiscount += $discountPerItem * $item->count;
        }

        return (object)[
            'discount' => round($totalDiscount, 2),
            'discount_in_percent' => $inPercent,
            'activate_price' => $code->min_order_amount,
            'message' => 'Промокод успешно применен'
        ];
    }

    /**
     * Создание или обновление промокода (для админки)
     */
    public function savePromoCode(array $data, ?int $id = null): PromoCode
    {
        $tenant = app('tenant');

        $validator = Validator::make($data, [
            'code' => 'required|string|unique:promo_codes,code,' . ($id ?? 'NULL') . ',id,tenant_id,' . $tenant->id,
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percent,fixed',
        ]);

        if ($validator->fails()) throw new ValidationException($validator);

        $payload = [
            'tenant_id' => $tenant->id,
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'discount' => $data['discount'],
            'discount_type' => $data['discount_type'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? 0,
            'for_new_users' => (bool)($data['for_new_users'] ?? false),
            'for_vip' => (bool)($data['for_vip'] ?? false),
            'is_active' => (bool)($data['is_active'] ?? true),
            'config' => $data['config'] ?? [],
        ];

        if ($id) {
            $promoCode = PromoCode::where('tenant_id', $tenant->id)->findOrFail($id);
            $promoCode->update($payload);
        } else {
            $promoCode = PromoCode::create($payload);
        }

        return $promoCode;
    }
}
