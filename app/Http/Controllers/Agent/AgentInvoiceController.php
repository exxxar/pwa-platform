<?php

namespace App\Http\Controllers\Agent;

use App\DTOs\Agent\CreateInvoiceDTO;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateInvoiceRequest;
use App\Http\Resources\Agent\InvoiceResource;
use App\Services\Agent\InvoiceService;
use App\Services\Tenants\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

    /**
     * POST /api/agent/invoices/create-and-send
     */
    public function createAndSend(Request $request, PaymentService $paymentService) // 🎯 Внедряем сервис сюда
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email',
            'service_type' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        $agent = $request->user();

        try {
            // 🎯 Вызываем метод напрямую у экземпляра класса, а не через фасад
            $paymentResult = $paymentService->createInvoiceLink([
                'amount' => (float) $validated['amount'],
                'description' => $validated['description'] ?: "Оплата услуги: {$validated['service_type']}",
                'name' => $validated['client_name'],
                'phone' => '',
                'email' => $validated['client_email'],
            ]);

            $paymentUrl = $paymentResult->url;
            $orderId = $paymentResult->order_id;

            // Отправка письма (ваш код)
            Mail::send('emails.agent_invoice', [
                'clientName' => $validated['client_name'],
                'amount' => number_format($validated['amount'], 0, ',', ' '),
                'description' => $validated['description'] ?: "Оплата услуги: {$validated['service_type']}",
                'paymentUrl' => $paymentUrl,
                'agentName' => $agent->name ?? 'Ваш менеджер',
            ], function ($message) use ($validated) {
                $message->to($validated['client_email'], $validated['client_name'])
                    ->subject('Счёт на оплату услуг');
            });

            return response()->json([
                'success' => true,
                'message' => 'Счёт успешно создан и отправлен клиенту на почту',
                'data' => [
                    'order_id' => $orderId,
                    'payment_url' => $paymentUrl
                ]
            ]);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка платежной системы: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Ошибка создания счета агентом: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать и отправить счёт. Проверьте логи.'
            ], 500);
        }
    }
}
