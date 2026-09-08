<?php
// app/Http/Controllers/Api/Agent/AgentInvoiceController.php
namespace App\Http\Controllers\Agent;

use App\DTOs\Agent\CreateInvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateInvoiceRequest;
use App\Http\Resources\Agent\InvoiceResource;
use App\Services\Agent\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentInvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * GET /api/agent/invoices
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $invoices = $this->invoiceService->getAgentInvoices($agent, $request->input('status'));

        return response()->json([
            'success' => true,
            'data'    => InvoiceResource::collection($invoices),
        ]);
    }

    /**
     * POST /api/agent/invoices
     */
    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        $dto = CreateInvoiceDTO::fromArray($request->validated());
        $invoice = $this->invoiceService->createInvoice($agent, $dto);

        return response()->json([
            'success' => true,
            'data'    => new InvoiceResource($invoice),
            'message' => "Счёт #{$invoice->number} создан",
        ], 201);
    }

    /**
     * POST /api/agent/invoices/{invoice}/send
     */
    public function send(Request $request, int $invoiceId): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $invoice = $agent->invoices()->findOrFail($invoiceId);

        try {
            $updated = $this->invoiceService->sendInvoice($invoice);

            return response()->json([
                'success' => true,
                'data'    => new InvoiceResource($updated),
                'message' => 'Счёт отправлен клиенту',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
