<?php

namespace App\Services;

use App\Models\Tenant\TenantUser;
use App\Models\Tenant\UserFriend;
use Illuminate\Support\Facades\DB;

class FriendService
{
    /**
     * 🆕 Отправить заявку в друзья
     */
    public function sendRequest(int $userId, int $friendId): UserFriend
    {
        if ($userId === $friendId) {
            throw new \Exception('Нельзя добавить себя в друзья');
        }

        // Проверяем, нет ли уже связи
        $existing = UserFriend::where(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $userId);
        })->first();

        if ($existing) {
            if ($existing->status === UserFriend::STATUS_ACCEPTED) {
                throw new \Exception('Пользователь уже в друзьях');
            }
            if ($existing->status === UserFriend::STATUS_PENDING) {
                throw new \Exception('Заявка уже отправлена');
            }
        }

        return UserFriend::create([
            'tenant_id' => app('tenant')->id,
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => UserFriend::STATUS_PENDING,
            'initiator_id' => $userId,
        ]);
    }

    /**
     * 🆕 Принять заявку в друзья
     */
    public function acceptRequest(int $userId, int $requestId): bool
    {
        $request = UserFriend::where('id', $requestId)
            ->where('friend_id', $userId)
            ->where('status', UserFriend::STATUS_PENDING)
            ->first();

        if (!$request) {
            return false;
        }

        return DB::transaction(function () use ($request) {
            $request->update([
                'status' => UserFriend::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            // Обновляем счётчики
            $request->user->increment('friends_count');
            $request->friend->increment('friends_count');

            return true;
        });
    }

    /**
     * 🆕 Отклонить заявку
     */
    public function rejectRequest(int $userId, int $requestId): bool
    {
        return UserFriend::where('id', $requestId)
            ->where('friend_id', $userId)
            ->where('status', UserFriend::STATUS_PENDING)
            ->update(['status' => UserFriend::STATUS_REJECTED]);
    }

    /**
     * 🆕 Удалить из друзей
     */
    public function removeFriend(int $userId, int $friendId): bool
    {
        $friendship = UserFriend::where(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $userId);
        })->first();

        if (!$friendship) {
            return false;
        }

        return DB::transaction(function () use ($friendship) {
            $friendship->delete();

            $friendship->user->decrement('friends_count');
            $friendship->friend->decrement('friends_count');

            return true;
        });
    }

    /**
     * 🆕 Получить список друзей
     */
    public function getFriends(int $userId): array
    {
        return UserFriend::forUser($userId)
            ->accepted()
            ->with(['user:id,name,avatar,phone', 'friend:id,name,avatar,phone'])
            ->get()
            ->map(function ($friendship) use ($userId) {
                $friend = $friendship->user_id === $userId
                    ? $friendship->friend
                    : $friendship->user;

                return [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'avatar' => $friend->avatar,
                    'phone' => $friend->phone,
                    'friendship_id' => $friendship->id,
                    'accepted_at' => $friendship->accepted_at,
                ];
            })
            ->toArray();
    }

    /**
     * 🆕 Получить входящие заявки
     */
    public function getIncomingRequests(int $userId): array
    {
        return UserFriend::where('friend_id', $userId)
            ->pending()
            ->with(['initiator:id,name,avatar,phone'])
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'from' => $request->initiator,
                    'created_at' => $request->created_at,
                ];
            })
            ->toArray();
    }
}
