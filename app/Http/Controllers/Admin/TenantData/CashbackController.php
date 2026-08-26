<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantData\ManuallyAdjustCashbackRequest;
use App\Http\Resources\Admin\CashbackHistoryResource;
use App\Services\Admin\TenantData\CashbackService;
use Illuminate\Http\Request;

class CashbackController extends Controller
{
    protected CashbackService $cashbackService;

    public function __construct(CashbackService $cashbackService)
    {
        $this->cashbackService = $cashbackService;
    }

    /**
     * История операций кэшбэка
     */
    public function history(Request $request)
    {
        $this->authorize('viewHistory', 'cashback');

        $filters = $request->only([
            'tenant_id',
            'tenant_user_id',
            'type',
        ]);
        $perPage = $request->input('per_page', 15);

        $history = $this->cashbackService->getHistory($filters, $perPage);

        return CashbackHistoryResource::collection($history);
    }

    /**
     * Ручное начисление/списание кэшбэка
     */
    public function manuallyAdjust(ManuallyAdjustCashbackRequest $request)
    {
        $this->authorize('manuallyAdjust', 'cashback');

        $userId = $request->input('tenant_user_id');
        $amount = $request->input('amount');
        $type = $request->input('type');
        $description = $request->input('description', '');

        $history = $this->cashbackService->manuallyAdjust($userId, $amount, $type, $description);

        return (new CashbackHistoryResource($history))
            ->response()
            ->setStatusCode(201);
    }
}
