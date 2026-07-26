<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use App\Services\FriendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function __construct(
        private ReferralService $referralService,
        private FriendService $friendService
    ) {}

    // ==========================================
    // РЕФЕРАЛЫ
    // ==========================================

    /**
     * 🆕 Получить дерево рефералов
     */
    public function tree()
    {
        $userId = Auth::guard('tenant')->id();

        return response()->json([
            'data' => $this->referralService->getReferralTree($userId),
        ]);
    }

    /**
     * Получить реферальную ссылку пользователя
     */
    public function link(Request $request)
    {
        $user = Auth::user(); // или $request->user() в зависимости от вашей аутентификации

        // Формируем ссылку. Замените 'domain.com' на ваш реальный домен или используйте url()
        // Предполагаем, что регистрация принимает параметр ?ref={user_id}
        $referralLink = url('/register?ref=' . $user->id);

        return response()->json([
            'link' => $referralLink,
        ]);
    }




    /**
     * 🆕 Получить историю наград
     */
    public function rewards(Request $request)
    {
        $userId = Auth::guard('tenant')->id();
        $limit = $request->input('limit', 50);

        return response()->json([
            'data' => $this->referralService->getRewardsHistory($userId, $limit),
        ]);
    }



    // ==========================================
    // ДРУЗЬЯ
    // ==========================================

    /**
     * 🆕 Список друзей
     */
    public function friends()
    {
        $userId = Auth::guard('tenant')->id();

        return response()->json([
            'data' => $this->friendService->getFriends($userId),
        ]);
    }

    /**
     * 🆕 Входящие заявки
     */
    public function friendRequests()
    {
        $userId = Auth::guard('tenant')->id();

        return response()->json([
            'data' => $this->friendService->getIncomingRequests($userId),
        ]);
    }

    /**
     * 🆕 Отправить заявку в друзья
     */
    public function sendFriendRequest(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|integer|exists:tenant_users,id',
        ]);

        $userId = Auth::guard('tenant')->id();

        try {
            $friendship = $this->friendService->sendRequest($userId, $request->friend_id);

            return response()->json([
                'success' => true,
                'data' => $friendship,
                'message' => 'Заявка отправлена',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 🆕 Принять заявку
     */
    public function acceptFriendRequest(int $requestId)
    {
        $userId = Auth::guard('tenant')->id();

        $result = $this->friendService->acceptRequest($userId, $requestId);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Заявка принята' : 'Заявка не найдена',
        ]);
    }

    /**
     * 🆕 Отклонить заявку
     */
    public function rejectFriendRequest(int $requestId)
    {
        $userId = Auth::guard('tenant')->id();

        $result = $this->friendService->rejectRequest($userId, $requestId);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Заявка отклонена' : 'Заявка не найдена',
        ]);
    }

    /**
     * 🆕 Удалить из друзей
     */
    public function removeFriend(int $friendId)
    {
        $userId = Auth::guard('tenant')->id();

        $result = $this->friendService->removeFriend($userId, $friendId);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Удалён из друзей' : 'Не в списке друзей',
        ]);
    }
}
