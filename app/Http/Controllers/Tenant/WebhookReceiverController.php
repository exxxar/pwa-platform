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
        // 1. Валидация
        $validated = $request->validate([
            'event' => 'required|string|in:product.updated,workspace.sync',
            'timestamp' => 'required|string',
            'workspace' => 'required|array',
            'workspace.id' => 'required|integer',
            'workspace.uuid' => 'required|string|uuid',
            'workspace.name' => 'required|string',
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

        // 3. Обрабатываем событие
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
