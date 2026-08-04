<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Tenant;
use App\Services\WebhookSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookReceiverController extends Controller
{
    public function __construct(
        private WebhookSyncService $syncService
    ) {}

    /**
     * Приём вебхука от платформы workspace
     * URL: POST shop-name.mypwa.ru/webhook
     * Tenant определяется автоматически по поддомену (shop-name)
     */
    public function handle(Request $request)
    {
        // 1. Умная валидация в зависимости от события
        $validated = $request->validate([
            'event' => 'required|string|in:product.updated,workspace.sync',
            'timestamp' => 'required|string',

            // Правила для workspace.sync
            'workspace' => 'required_if:event,workspace.sync|array',
            'workspace.id' => 'required_if:event,workspace.sync|integer',
            'workspace.uuid' => 'required_if:event,workspace.sync|string|uuid',
            'workspace.name' => 'required_if:event,workspace.sync|string',
            'workspace.products' => 'sometimes|array',
            'workspace.collections' => 'sometimes|array',

            // Правила для product.updated
            'product' => 'required_if:event,product.updated|array',
            'product.id' => 'required_if:event,product.updated',
            'product.name' => 'sometimes|string',
        ]);

        $event = $validated['event'];
        $tenant = app('tenant');

        if (!$tenant) {
            Log::error('Webhook: tenant not resolved', [
                'host' => $request->getHost(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Tenant not found',
            ], 404);
        }

        Log::info('Webhook received', [
            'event' => $event,
            'tenant_id' => $tenant->id,
            'host' => $request->getHost(),
        ]);

        // 2. Обрабатываем событие (без изменений)
        try {
            $result = match ($event) {
                'product.updated' => $this->syncService->syncSingleProduct($tenant, $request->all()),
                'workspace.sync' => $this->syncService->syncFullWorkspace($tenant, $request->all()),
            };

            return response()->json([
                'success' => true,
                'message' => 'Sync completed',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', [
                'event' => $event,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Добавил трейс, чтобы было проще дебажить
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ⚠️ ЗАМЕНИ ЭТУ ЛОГИКУ НА СВОЮ
     */
    private function resolveTenant(string $uuid): ?Tenant
    {
        // Пример:
        // return Tenant::where('workspace_uuid', $uuid)->first();
        // return Tenant::find(1);
        return null;
    }
}
