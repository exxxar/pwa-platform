<?php
// app/Http/Controllers/Api/Agent/AgentReferralController.php
namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\Agent\AgentReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentReferralController extends Controller
{
    public function __construct(
        private AgentReferralService $referralService
    ) {}

    /**
     * GET /api/agent/referrals/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $stats = $this->referralService->getStats($agent);

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * GET /api/agent/referrals/link
     */
    public function link(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        return response()->json([
            'success' => true,
            'data'    => [
                'referral_code' => $agent->referral_code,
                'referral_url'  => $agent->referral_url,
            ],
        ]);
    }
}
