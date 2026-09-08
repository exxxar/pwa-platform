<?php
// app/Http/Controllers/Api/Agent/AgentProfileController.php
namespace App\Http\Controllers\Agent;

use App\DTOs\Agent\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\UpdateProfileRequest;
use App\Http\Resources\Agent\AgentResource;
use App\Services\Agent\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentProfileController extends Controller
{
    public function __construct(
        private AgentService $agentService
    ) {}

    /**
     * GET /api/agent/profile
     * Получить профиль текущего агента
     */
    public function show(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Агентский профиль не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AgentResource($agent),
        ]);
    }

    /**
     * PUT /api/agent/profile
     * Обновить профиль
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        $dto = UpdateProfileDTO::fromArray($request->validated());
        $updatedAgent = $this->agentService->updateProfile($agent, $dto);

        return response()->json([
            'success' => true,
            'data'    => new AgentResource($updatedAgent),
            'message' => 'Профиль успешно обновлён',
        ]);
    }

    /**
     * GET /api/agent/dashboard
     * Метрики для дашборда
     */
    public function dashboard(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $metrics = $this->agentService->getDashboardMetrics($agent);

        return response()->json([
            'success' => true,
            'data'    => $metrics,
        ]);
    }
}
