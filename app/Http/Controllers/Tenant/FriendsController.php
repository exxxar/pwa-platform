<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TenantUser;
use App\Models\Tenant\UserFriend;
use Illuminate\Http\Request;

class FriendsController extends Controller
{
    /**
     * Отправить заявку в друзья
     */
    public function sendRequest(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:tenant_users,id',
        ]);

        $user = auth()->guard('tenant')->user();
        $friendId = $request->friend_id;

        // Нельзя добавить себя
        if ($user->id === $friendId) {
            return response()->json([
                'message' => 'Нельзя добавить себя в друзья'
            ], 422);
        }

        // Проверяем, не друзья ли уже
        $isAlreadyFriend = UserFriend::where(function ($q) use ($user, $friendId) {
            $q->where(function ($q2) use ($user, $friendId) {
                $q2->where('user_id', $user->id)
                    ->where('friend_id', $friendId);
            })->orWhere(function ($q2) use ($user, $friendId) {
                $q2->where('user_id', $friendId)
                    ->where('friend_id', $user->id);
            });
        })
            ->where('status', UserFriend::STATUS_ACCEPTED)
            ->exists();

        if ($isAlreadyFriend) {
            return response()->json([
                'message' => 'Вы уже друзья'
            ], 422);
        }

        // Проверяем существующие pending заявки
        $existingRequest = UserFriend::where(function ($q) use ($user, $friendId) {
            $q->where(function ($q2) use ($user, $friendId) {
                $q2->where('user_id', $user->id)
                    ->where('friend_id', $friendId);
            })->orWhere(function ($q2) use ($user, $friendId) {
                $q2->where('user_id', $friendId)
                    ->where('friend_id', $user->id);
            });
        })
            ->where('status', UserFriend::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            // Если друг отправил заявку нам - автоматически принимаем
            if ($existingRequest->friend_id === $user->id) {
                $existingRequest->update([
                    'status' => UserFriend::STATUS_ACCEPTED,
                    'accepted_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Заявка автоматически принята!',
                    'auto_accepted' => true
                ]);
            }

            return response()->json([
                'message' => 'Заявка уже отправлена'
            ], 422);
        }

        // Создаём заявку
        UserFriend::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'friend_id' => $friendId,
            'initiator_id' => $user->id,
            'status' => UserFriend::STATUS_PENDING,
        ]);

        // 🆕 УВЕДОМЛЕНИЕ: Можно отправить push/telegram уведомление
        // event(new FriendRequestSent($user, TenantUser::find($friendId)));

        return response()->json([
            'message' => 'Заявка отправлена!'
        ]);
    }

    /**
     * Принять заявку
     */
    public function acceptRequest(Request $request, $requestId)
    {
        $user = auth()->guard('tenant')->user();

        $friendRequest = UserFriend::where('id', $requestId)
            ->where('friend_id', $user->id) // Только нам адресованные
            ->where('status', UserFriend::STATUS_PENDING)
            ->firstOrFail();

        $friendRequest->update([
            'status' => UserFriend::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        // Обновляем счётчики
        $user->increment('friends_count');
        TenantUser::where('id', $friendRequest->user_id)
            ->increment('friends_count');

        return response()->json([
            'message' => 'Заявка принята!'
        ]);
    }

    /**
     * Отклонить заявку
     */
    public function rejectRequest(Request $request, $requestId)
    {
        $user = auth()->guard('tenant')->user();

        $friendRequest = UserFriend::where('id', $requestId)
            ->where('friend_id', $user->id)
            ->where('status', UserFriend::STATUS_PENDING)
            ->firstOrFail();

        $friendRequest->update([
            'status' => UserFriend::STATUS_REJECTED,
        ]);

        return response()->json([
            'message' => 'Заявка отклонена'
        ]);
    }

    /**
     * Удалить из друзей
     */
    public function removeFriend(Request $request, $friendId)
    {
        $user = auth()->guard('tenant')->user();

        $friendship = UserFriend::where(function ($q) use ($user, $friendId) {
            $q->where(function ($q2) use ($user, $friendId) {
                $q2->where('user_id', $user->id)
                    ->where('friend_id', $friendId);
            })->orWhere(function ($q2) use ($user, $friendId) {
                $q2->where('user_id', $friendId)
                    ->where('friend_id', $user->id);
            });
        })
            ->where('status', UserFriend::STATUS_ACCEPTED)
            ->first();

        if ($friendship) {
            $friendship->delete();

            // Обновляем счётчики
            $user->decrement('friends_count');
            TenantUser::where('id', $friendId)->decrement('friends_count');
        }

        return response()->json([
            'message' => 'Удалён из друзей'
        ]);
    }
}
