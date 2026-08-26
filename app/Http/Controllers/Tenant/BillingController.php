<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    // В вашем контроллере

    /**
     * Получить доступные тарифные планы
     */
    public function getPricingPlans()
    {
        $plans = config('pricing.plans', [
            [
                'slug' => 'start',
                'name' => 'Старт',
                'description' => 'Для небольших магазинов и начинающих предпринимателей',
                'price' => 990
            ],
            [
                'slug' => 'business',
                'name' => 'Бизнес',
                'description' => 'Для растущих магазинов с активной клиентской базой',
                'price' => 4990
            ],
            [
                'slug' => 'premium',
                'name' => 'Премиум',
                'description' => 'Для крупных сетей и франшиз с индивидуальными требованиями',
                'price' => 7990
            ],
        ]);

        return response()->json($plans);
    }


    public function updateTenantPlan(Request $request)
    {
        // 1. Получаем тарифы из конфига для валидации
        $plans = config('pricing.plans', []);
        $validSlugs = collect($plans)->pluck('slug')->toArray();

        // 2. Валидируем запрос (убеждаемся, что слаг существует в конфиге)
        $request->validate([
            'plan_slug' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::in($validSlugs),
            ],
        ]);

        $tenant = app('tenant');
        $planSlug = $request->plan_slug;

        // 3. Находим выбранный план в конфиге
        $selectedPlan = collect($plans)->firstWhere('slug', $planSlug);

        if (!$selectedPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Указанный тарифный план не найден.'
            ], 404);
        }

        // 4. Вычисляем ежедневную стоимость (Цена / 31 день)
        // Используем round(..., 2), чтобы избежать проблем с плавающей точкой в БД (копейки)
        $monthlyPrice = (float) $selectedPlan['price'];
        $taxPerDay = round($monthlyPrice / 31, 2);

        // 5. Обновляем данные тенанта
        $tenant->update([
            'plan_slug'   => $planSlug,
            'tax_per_day' => $taxPerDay,

            // Опционально: можно сбросить или продлить дату следующей оплаты
            // 'next_billing_date' => now()->addMonth(),
        ]);

        // 6. Возвращаем успешный ответ с актуальными данными (чтобы фронт мог сразу обновить состояние)
        return response()->json([
            'success' => true,
            'message' => 'Тарифный план успешно изменен',
            'data' => [
                'plan_slug'   => $planSlug,
                'plan_name'   => $selectedPlan['title'],
                'tax_per_day' => $taxPerDay,
            ]
        ]);
    }
}
