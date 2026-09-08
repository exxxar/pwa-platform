<?php
// app/Http/Middleware/EnsureAgent.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->agentProfile) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет агентского профиля. Создайте его в личном кабинете.',
            ], 403);
        }

        // Проверка статуса агента
        if ($user->agentProfile->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Ваш агентский аккаунт приостановлен. Обратитесь в поддержку.',
            ], 403);
        }

        return $next($request);
    }
}
