<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantData\ManuallyAdjustReferralRequest;
use App\Http\Resources\Admin\ReferralResource;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\UserReferral;
use App\Services\Admin\TenantData\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Список реферальных связей с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', UserReferral::class);

        $filters = $request->only([
            'tenant_id',
            'level',
            'is_active',
            'referrer_id',
        ]);
        $perPage = $request->input('per_page', 15);

        $referrals = $this->referralService->getReferrals($filters, $perPage);

        return ReferralResource::collection($referrals);
    }

    /**
     * Цепочка рефералов конкретного пользователя
     */
    public function showChain(TenantUser $user)
    {
        $this->authorize('showChain', UserReferral::class);

        $chain = $this->referralService->getUserChain($user);

        return response()->json([
            'success' => true,
            'data' => $chain,
        ]);
    }

    /**
     * Статистика по рефералам
     */
    public function stats(Request $request)
    {
        $this->authorize('stats', UserReferral::class);

        $filters = $request->only(['tenant_id']);
        $stats = $this->referralService->getStats($filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Ручное изменение реферальной связи
     */
    public function manuallyAdjust(ManuallyAdjustReferralRequest $request, UserReferral $referral)
    {
        $this->authorize('manuallyAdjust', $referral);

        $referral = $this->referralService->manuallyAdjust($referral, $request->validated());

        return new ReferralResource($referral);
    }
}
