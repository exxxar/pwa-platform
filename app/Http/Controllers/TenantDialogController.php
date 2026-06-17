<?php

namespace App\Http\Controllers;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TenantDialogController extends Controller
{
    /**
     * Список диалогов
     */
    public function index()
    {

        $tenantUser = Auth::guard('tenant')->user();

        return TenantDialog::query()
            ->where('tenant_user_id', $tenantUser->id)
            ->with('lastMessage')
            ->orderByDesc('last_message_at')
            ->get();
    }

    /**
     * Сообщения диалога
     */
    public function messages($tenant, $id)
    {
        $tenantUser = Auth::guard('tenant')->user();

        $dialog = TenantDialog::query()
            ->where('tenant_user_id', $tenantUser->id)
            ->findOrFail($id);

        return TenantMessage::query()
            ->where('dialog_id', $dialog->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * Отправка сообщения + PUSH
     */
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'dialog_id' => 'required'
        ]);


        $id = $request->dialog_id;

        return DB::transaction(function () use ($request, $id) {
            $tenant = app('tenant');
            $tenantUser = Auth::guard('tenant')->user();

            $dialog = TenantDialog::query()
                ->findOrFail($id);

            $message = TenantMessage::create([
                'tenant_id' => $tenant->id,//todo:исправть
                'dialog_id' => $dialog->id,
                'message' => $request->message,
                'meta' => [
                    'user_id' => auth()->id()
                ],
                'is_read' => false,
            ]);

            // обновляем диалог
            $dialog->update([
                'last_message_at' => now()
            ]);

            /**
             * PUSH УВЕДОМЛЕНИЕ
             */
            // получаем всех пользователей, кроме отправителя
            $users = TenantUser::query()
                ->where('tenant_id', $dialog->tenant_id)
                ->where('id', '!=', auth()->id())
                ->get();

            foreach ($users as $user) {
                try {
                    $user->notify(new NewMessageNotification($message, $dialog));
                } catch (\Throwable $e) {
                    \Log::error($e->getMessage());
                }
            }

            return response()->json($message);
        });
    }
}
