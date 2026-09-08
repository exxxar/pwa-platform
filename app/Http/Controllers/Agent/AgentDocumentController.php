<?php
// app/Http/Controllers/Api/Agent/AgentDocumentController.php
namespace App\Http\Controllers\Agent;

use App\Exceptions\Agent\DocumentValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\UploadDocumentRequest;
use App\Http\Resources\Agent\DocumentResource;
use App\Services\Agent\AgentDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentDocumentController extends Controller
{
    public function __construct(
        private AgentDocumentService $documentService
    ) {}

    /**
     * GET /api/agent/documents
     */
    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        $data = $this->documentService->getDocumentsWithProgress($agent);

        return response()->json([
            'success' => true,
            'data'    => [
                'documents' => DocumentResource::collection($data['documents']),
                'progress'  => $data['progress'],
                'status'    => $data['status'],
            ],
        ]);
    }

    /**
     * POST /api/agent/documents/upload
     */
    public function upload(UploadDocumentRequest $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;

        try {
            $document = $this->documentService->uploadDocument(
                $agent,
                (int) $request->validated('document_type_id'),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'data'    => new DocumentResource($document),
                'message' => 'Документ успешно загружен',
            ], 201);
        } catch (DocumentValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
