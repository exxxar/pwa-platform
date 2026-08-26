<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Services\Admin\Global\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    protected ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Экспорт пользователей в CSV
     */
    public function exportUsers(Request $request)
    {
        $filters = $request->only(['tenant_id', 'is_active']);

        try {
            $url = $this->exportService->exportUsersToCsv($filters);

            return response()->json([
                'success' => true,
                'message' => 'Экспорт пользователей выполнен успешно',
                'download_url' => $url,
                'filename' => basename($url),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Экспорт заказов в CSV
     */
    public function exportOrders(Request $request)
    {
        $filters = $request->only(['tenant_id', 'from', 'to', 'status']);

        try {
            $url = $this->exportService->exportOrdersToCsv($filters);

            return response()->json([
                'success' => true,
                'message' => 'Экспорт заказов выполнен успешно',
                'download_url' => $url,
                'filename' => basename($url),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Экспорт транзакций в CSV
     */
    public function exportTransactions(Request $request)
    {
        $filters = $request->only(['tenant_id', 'status', 'from', 'to']);

        try {
            $url = $this->exportService->exportTransactionsToCsv($filters);

            return response()->json([
                'success' => true,
                'message' => 'Экспорт транзакций выполнен успешно',
                'download_url' => $url,
                'filename' => basename($url),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Экспорт тенантов в CSV
     */
    public function exportTenants(Request $request)
    {
        $filters = $request->only(['is_active']);

        try {
            $url = $this->exportService->exportTenantsToCsv($filters);

            return response()->json([
                'success' => true,
                'message' => 'Экспорт тенантов выполнен успешно',
                'download_url' => $url,
                'filename' => basename($url),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте: ' . $e->getMessage(),
            ], 500);
        }
    }
}
