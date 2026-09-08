<?php
// app/Http/Controllers/Api/Agent/AgentFinanceController.php
namespace App\Http\Controllers\Agent;

use App\DTOs\Agent\RequestPayoutDTO;
use App\Exceptions\Agent\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\RequestPayoutRequest;
use App\Http\Resources\Agent\PayoutResource;
use App\Http\Resources\Agent\TransactionResource;
use App\Services\Agent\AgentFinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentFinanceController extends Controller
{
    public function __construct(
        private AgentFinanceService $financeService
    ) {}

    /**
     * GET /api/agent/finance/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        $transactions = $this->financeService->getTransactions(
            $agent,
            $request->only(['type', 'from', 'to']),
            (int) $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data'    => TransactionResource::collection($transactions),
            'meta'    => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }

    /**
     * POST /api/agent/finance/payout
     */
    public function requestPayout(RequestPayoutRequest $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        try {
            $dto = new RequestPayoutDTO(
                amount: (float) $request->validated('amount'),
                comment: $request->validated('comment')
            );

            $payout = $this->financeService->requestPayout($agent, $dto);

            return response()->json([
                'success' => true,
                'data'    => new PayoutResource($payout),
                'message' => "Заявка на выплату #{$payout->number} принята",
            ], 201);
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/agent/finance/payouts
     */
    public function payouts(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $payouts = $this->financeService->getPayouts($agent, $request->input('status'));

        return response()->json([
            'success' => true,
            'data'    => PayoutResource::collection($payouts),
        ]);
    }
}
