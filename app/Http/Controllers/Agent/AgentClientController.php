<?php
// app/Http/Controllers/Api/Agent/AgentClientController.php
namespace App\Http\Controllers\Agent;

use App\DTOs\Agent\AddClientDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AddClientRequest;
use App\Http\Resources\Agent\ClientResource;
use App\Models\TenantUser;
use App\Services\Agent\AgentClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentClientController extends Controller
{
    public function __construct(
        private AgentClientService $clientService
    ) {}

    /**
     * GET /api/agent/clients
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $clients = $this->clientService->getClients($agent, $request->input('status'));

        return response()->json([
            'success' => true,
            'data'    => ClientResource::collection($clients),
        ]);
    }

    /**
     * POST /api/agent/clients
     */
    public function store(AddClientRequest $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $tenantUser = TenantUser::findOrFail($request->validated('tenant_user_id'));

        $dto = AddClientDTO::fromArray($request->validated());
        $client = $this->clientService->addClient($agent, $tenantUser, $dto->notes);

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client->load('tenantUser')),
            'message' => 'Клиент добавлен',
        ], 201);
    }

    /**
     * DELETE /api/agent/clients/{client}
     */
    public function destroy(Request $request, int $clientId): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $client = $agent->clients()->findOrFail($clientId);

        $this->clientService->deactivateClient($client);

        return response()->json([
            'success' => true,
            'message' => 'Клиент деактивирован',
        ]);
    }
}
