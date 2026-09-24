<?php

namespace App\Http\Controllers\Agent;

use App\DTOs\Agent\CreateProfileDTO;
use App\DTOs\Agent\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateProfileRequest;
use App\Http\Requests\Agent\UpdateProfileRequest;
use App\Http\Resources\Agent\AgentResource;
use App\Services\Agent\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $agent = Auth::guard('tenant')->user()
            ->agentProfile;


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
     * POST /api/agent/profile
     * Создать агентский профиль (онбординг)
     */
    public function store(CreateProfileRequest $request): JsonResponse
    {
        $user = Auth::guard('tenant')->user();

        // Защита от повторного создания, если профиль уже есть
        if ($user->agentProfile) {

            return response()->json([
                'success' => false,
                'message' => 'Агентский профиль уже существует',
            ], 409); // 409 Conflict
        }

        // 🆕 ИСПРАВЛЕНО: используем fromRequest вместо fromArray
        $dto = CreateProfileDTO::fromRequest($request);

        // Вызываем сервис для создания
        $agent = $this->agentService->createProfile($user, $dto);

        return response()->json([
            'success' => true,
            'data' => new AgentResource($agent),
            'message' => 'Агентский профиль успешно создан',
        ], 201);
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
