<?php
// app/Http/Controllers/Api/Agent/AgentTenantController.php
namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Agent\TenantResource;
use App\Services\Agent\AgentTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTenantController extends Controller
{
    public function __construct(
        private AgentTenantService $tenantService
    ) {}

    /**
     * GET /api/agent/tenants
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $tenants = $this->tenantService->getAgentTenants(
            $agent,
            $request->only(['status', 'search'])
        );

        return response()->json([
            'success' => true,
            'data'    => TenantResource::collection($tenants),
        ]);
    }
}
