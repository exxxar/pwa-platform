<?php
// app/Http/Controllers/Api/Agent/MarketingController.php
namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\Agent\MarketingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function __construct(
        private MarketingService $marketingService
    ) {}

    /**
     * GET /api/agent/marketing
     */
    public function index(): JsonResponse
    {
        $categories = $this->marketingService->getCategoriesWithMaterials();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * POST /api/agent/marketing/{material}/download
     */
    public function download(Request $request, int $materialId): JsonResponse
    {
        $material = \App\Models\MarketingMaterial::findOrFail($materialId);
        $this->marketingService->trackDownload($material);

        return response()->json([
            'success'  => true,
            'data'     => [
                'download_url' => asset('storage/' . $material->file_path),
                'file_name'    => $material->file_name,
            ],
            'message'  => 'Скачивание началось',
        ]);
    }
}
