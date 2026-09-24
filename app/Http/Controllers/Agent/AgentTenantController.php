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

    /**
     * POST /api/agent/tenants
     * Создание приложения через калькулятор
     */
    public function store(Request $request, AgentTenantService $tenantService): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        $validated = $request->validate([
            'formData.businessType' => 'required|string',
            'formData.customName' => 'required|string|max:255',
            'formData.systemName' => 'required|string|regex:/^[a-z0-9-]+$/|max:255',
            'formData.clientName' => 'required|string|max:255',
            'formData.clientPhone' => 'required|string|max:20',
            'formData.paymentPlan' => 'required|string|in:free,monthly,yearly,custom',
            'formData.customAmount' => 'nullable|numeric|min:500',
            'total' => 'required|numeric|min:0',
            'monthly' => 'required|numeric|min:0',
            'planPrice' => 'required|numeric|min:0',
            'planBalance' => 'required|numeric|min:0',
        ]);

        try {
            $tenant = $tenantService->createFromCalculator($agent, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Приложение успешно создано и настроено!',
                'data' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании приложения: ' . $e->getMessage()
            ], 500);
        }
    }
}
