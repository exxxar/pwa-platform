<?php

namespace App\Http\Controllers\Admin\TenantData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantData\ReplyToDialogRequest;
use App\Http\Resources\Admin\DialogResource;
use App\Http\Resources\Admin\MessageResource;
use App\Models\Tenant\TenantDialog;
use App\Services\Admin\TenantData\DialogService;
use Illuminate\Http\Request;

class DialogController extends Controller
{
    protected DialogService $dialogService;

    public function __construct(DialogService $dialogService)
    {
        $this->dialogService = $dialogService;
    }

    /**
     * Список диалогов с фильтрацией и пагинацией
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TenantDialog::class);

        $filters = $request->only([
            'tenant_id',
            'is_closed',
            'has_unread',
        ]);
        $perPage = $request->input('per_page', 15);

        $dialogs = $this->dialogService->getDialogs($filters, $perPage);

        return DialogResource::collection($dialogs);
    }

    /**
     * Просмотр диалога с сообщениями
     */
    public function show(Request $request, TenantDialog $dialog)
    {
        $this->authorize('view', $dialog);

        $perPage = $request->input('per_page', 50);
        $data = $this->dialogService->getDialogWithMessages($dialog, $perPage);

        return response()->json([
            'success' => true,
            'dialog' => new DialogResource($data['dialog']),
            'messages' => MessageResource::collection($data['messages'])->response()->getData(true),
        ]);
    }

    /**
     * Ответ в диалог
     */
    public function reply(ReplyToDialogRequest $request, TenantDialog $dialog)
    {
        $this->authorize('reply', $dialog);

        $message = $this->dialogService->reply($dialog, $request->validated());

        return (new MessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Закрытие диалога
     */
    public function close(TenantDialog $dialog)
    {
        $this->authorize('close', $dialog);

        $dialog = $this->dialogService->closeDialog($dialog);

        return new DialogResource($dialog);
    }

    /**
     * Отметить все сообщения как прочитанные
     */
    public function markAsRead(TenantDialog $dialog)
    {
        $this->authorize('view', $dialog);

        $count = $this->dialogService->markAsRead($dialog);

        return response()->json([
            'success' => true,
            'message' => "Отмечено как прочитанные: {$count} сообщений",
            'marked_count' => $count,
        ]);
    }
}
