<?php

namespace App\Http\Controllers\Tenant\Games;

use App\Facades\CashbackService;
use App\Facades\MessageService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    /**
     * Стоимость одной игры (кэшбэк)
     */
    private const GAME_COST = 500;

    /**
     * Минимальный процент для победы
     */
    private const WIN_THRESHOLD = 75;

    /**
     * Базовый вопросник (в реальности — из БД или настроек тенанта)
     */
    private const QUESTION_BANK = [
        [
            'id' => 'q1',
            'text' => 'Какой ингредиент является основой классической пиццы Маргарита?',
            'answers' => [
                ['id' => 'a', 'text' => 'Томатный соус, моцарелла и базилик'],
                ['id' => 'b', 'text' => 'Грибы, ветчина и оливки'],
                ['id' => 'c', 'text' => 'Ананасы и бекон'],
                ['id' => 'd', 'text' => 'Только сыр и тесто'],
            ],
            'correct_answer' => 'a',
        ],
        [
            'id' => 'q2',
            'text' => 'Сколько минут обычно выпекается неаполитанская пицца в дровяной печи?',
            'answers' => [
                ['id' => 'a', 'text' => '15-20 минут'],
                ['id' => 'b', 'text' => '60-90 секунд'],
                ['id' => 'c', 'text' => '5-7 минут'],
                ['id' => 'd', 'text' => '30 минут'],
            ],
            'correct_answer' => 'b',
        ],
        [
            'id' => 'q3',
            'text' => 'Какой сыр традиционно используется в пицце Четыре Сыра?',
            'answers' => [
                ['id' => 'a', 'text' => 'Только Моцарелла'],
                ['id' => 'b', 'text' => 'Моцарелла, Горгонзола, Пармезан и Эмменталь'],
                ['id' => 'c', 'text' => 'Чеддер и Гауда'],
                ['id' => 'd', 'text' => 'Фета и Брынза'],
            ],
            'correct_answer' => 'b',
        ],
        [
            'id' => 'q4',
            'text' => 'Что означает слово "Пицца" в переводе с итальянского?',
            'answers' => [
                ['id' => 'a', 'text' => 'Плоский хлеб'],
                ['id' => 'b', 'text' => 'Круглый пирог'],
                ['id' => 'c', 'text' => 'Сырная лепешка'],
                ['id' => 'd', 'text' => 'Быстрая еда'],
            ],
            'correct_answer' => 'a',
        ],
        [
            'id' => 'q5',
            'text' => 'Из какой страны родом пицца?',
            'answers' => [
                ['id' => 'a', 'text' => 'Франция'],
                ['id' => 'b', 'text' => 'Италия'],
                ['id' => 'c', 'text' => 'Греция'],
                ['id' => 'd', 'text' => 'Испания'],
            ],
            'correct_answer' => 'b',
        ],
        [
            'id' => 'q6',
            'text' => 'Какая температура оптимальна для выпечки пиццы?',
            'answers' => [
                ['id' => 'a', 'text' => '180°C'],
                ['id' => 'b', 'text' => '250°C'],
                ['id' => 'c', 'text' => '350°C и выше'],
                ['id' => 'd', 'text' => '100°C'],
            ],
            'correct_answer' => 'c',
        ],
    ];

    /**
     * 📋 Получение настроек и состояния
     */
    public function getState(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['quiz'] ?? [];
        $intervalDays = (int) ($settings['interval'] ?? 1);
        $attemptsPerPeriod = (int) ($settings['attempts_per_period'] ?? 1);

        $meta = $user->meta ?? [];
        $attempts = $meta['quiz_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values();

        $attemptsLeft = max(0, $attemptsPerPeriod - $validAttempts->count());

        // Активная сессия (если есть)
        $activeSession = $meta['quiz_active_session'] ?? null;

        return response()->json([
            'success' => true,
            'balance' => (float) $user->cashback_balance,
            'game_cost' => self::GAME_COST,
            'win_threshold' => self::WIN_THRESHOLD,
            'attempts_left' => $attemptsLeft,
            'attempts_per_period' => $attemptsPerPeriod,
            'questions_count' => count(self::QUESTION_BANK),
            'active_session' => $activeSession ? $this->sanitizeSession($activeSession) : null,
            'history' => array_slice($meta['quiz_history'] ?? [], 0, 30),
        ]);
    }

    /**
     * 🎮 Начать новую викторину
     */
    public function startQuiz(Request $request)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $tenant = app('tenant');
        $settings = $tenant->settings['quiz'] ?? [];
        $intervalDays = (int) ($settings['interval'] ?? 1);
        $attemptsPerPeriod = (int) ($settings['attempts_per_period'] ?? 1);

        $meta = $user->meta ?? [];
        $attempts = $meta['quiz_attempts'] ?? [];
        $cutoffDate = Carbon::now()->subDays($intervalDays);

        // Проверка: есть ли активная сессия?
        if (!empty($meta['quiz_active_session'])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас уже есть активная викторина. Завершите её.',
            ], 403);
        }

        // Проверка лимита попыток
        $validAttempts = collect($attempts)->filter(function ($attempt) use ($cutoffDate) {
            return Carbon::parse($attempt['played_at'])->gte($cutoffDate);
        })->values();

        if ($validAttempts->count() >= $attemptsPerPeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Попытки на этот период закончились. Попробуйте позже!',
            ], 403);
        }

        // Проверка баланса
        $currentBalance = (float) $user->cashback_balance;
        if ($currentBalance < self::GAME_COST) {
            return response()->json([
                'success' => false,
                'message' => "Недостаточно кэшбэка. Нужно " . self::GAME_COST . "₽, у вас " . number_format($currentBalance, 0, '.', '') . "₽",
                'balance' => $currentBalance,
                'required' => self::GAME_COST,
                'shortage' => self::GAME_COST - $currentBalance,
            ], 403);
        }

        // Перемешиваем вопросы и выбираем N случайных
        $allQuestions = self::QUESTION_BANK;
        shuffle($allQuestions);
        $selectedQuestions = array_slice($allQuestions, 0, min(6, count($allQuestions)));

        $sessionToken = (string) Str::uuid();

        try {
            DB::transaction(function () use ($user, &$meta, $selectedQuestions, $sessionToken) {
                // 💸 Списываем ставку
                CashBackService::call()->removeCashBack(
                    self::GAME_COST,
                    "🧠 Ставка в Викторину",
                    $user
                );

                // 🔒 Создаём активную сессию (вопросы БЕЗ правильных ответов!)
                $meta['quiz_active_session'] = [
                    'token' => $sessionToken,
                    'questions' => $selectedQuestions, // Полные данные (с correct_answer) — скрыты от клиента
                    'user_answers' => [], // Ответы пользователя по question_id
                    'score' => 0,
                    'started_at' => Carbon::now()->toIso8601String(),
                    'finished' => false,
                ];

                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[Quiz] Ошибка старта викторины', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка. Средства не списаны.',
            ], 500);
        }

        $user->refresh();

        Log::info('[Quiz] Викторина начата', [
            'user_id' => $user->id,
            'token' => $sessionToken,
            'questions_count' => count($selectedQuestions),
        ]);

        return response()->json([
            'success' => true,
            'token' => $sessionToken,
            'questions' => $this->getPublicQuestions($selectedQuestions), // БЕЗ correct_answer!
            'balance' => (float) $user->cashback_balance,
        ]);
    }

    /**
     * ✏️ Ответить на конкретный вопрос
     */
    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'question_id' => 'required|string',
            'answer_id' => 'required|string',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $session = $meta['quiz_active_session'] ?? null;

        // Проверка токена
        if (!$session || ($session['token'] ?? null) !== $validated['token']) {
            return response()->json([
                'success' => false,
                'message' => 'Активная викторина не найдена',
            ], 404);
        }

        if (!empty($session['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Викторина уже завершена',
            ], 400);
        }

        $questionId = $validated['question_id'];
        $answerId = $validated['answer_id'];

        // Проверка: не отвечал ли уже на этот вопрос?
        if (isset($session['user_answers'][$questionId])) {
            return response()->json([
                'success' => false,
                'message' => 'На этот вопрос уже отвечено',
            ], 400);
        }

        // Ищем вопрос в сессии
        $question = null;
        foreach ($session['questions'] as $q) {
            if ($q['id'] === $questionId) {
                $question = $q;
                break;
            }
        }

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Вопрос не найден в текущей викторине',
            ], 404);
        }

        // Проверяем ответ
        $isCorrect = ($question['correct_answer'] === $answerId);

        try {
            DB::transaction(function () use (&$meta, &$session, $questionId, $answerId, $isCorrect, $user) {
                // Сохраняем ответ
                $session['user_answers'][$questionId] = [
                    'answer_id' => $answerId,
                    'is_correct' => $isCorrect,
                    'answered_at' => Carbon::now()->toIso8601String(),
                ];

                if ($isCorrect) {
                    $session['score'] = ($session['score'] ?? 0) + 1;
                }

                $meta['quiz_active_session'] = $session;
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[Quiz] Ошибка сохранения ответа', [
                'user_id' => $user->id,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении ответа',
            ], 500);
        }

        // Проверяем, все ли вопросы отвечены
        $totalQuestions = count($session['questions']);
        $answeredCount = count($session['user_answers']);
        $isComplete = $answeredCount >= $totalQuestions;

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'correct_answer' => $isCorrect ? null : $question['correct_answer'], // Показываем только если неправильно
            'score' => $session['score'],
            'answered_count' => $answeredCount,
            'total_questions' => $totalQuestions,
            'is_complete' => $isComplete,
        ]);
    }

    /**
     * 🏁 Завершить викторину и получить результат
     */
    public function finishQuiz(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::guard('tenant')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $meta = $user->meta ?? [];
        $session = $meta['quiz_active_session'] ?? null;

        if (!$session || ($session['token'] ?? null) !== $validated['token']) {
            return response()->json([
                'success' => false,
                'message' => 'Викторина не найдена',
            ], 404);
        }

        if (!empty($session['finished'])) {
            return response()->json([
                'success' => false,
                'message' => 'Викторина уже завершена',
            ], 400);
        }

        $totalQuestions = count($session['questions']);
        $answeredCount = count($session['user_answers']);
        $score = $session['score'] ?? 0;
        $winPercentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;
        $isWin = $winPercentage >= self::WIN_THRESHOLD;

        // Расчёт приза
        $prize = 0;
        if ($isWin) {
            // Приз зависит от процента правильных ответов
            if ($winPercentage === 100) {
                $prize = 2000; // Идеальный результат
            } elseif ($winPercentage >= 90) {
                $prize = 1200;
            } else {
                $prize = 800; // Минимум для победы (75%+)
            }
        }

        try {
            DB::transaction(function () use (
                $user, &$meta, &$session,
                $totalQuestions, $score, $winPercentage, $isWin, $prize
            ) {
                // 💰 Начисляем приз, если победа
                if ($isWin && $prize > 0) {
                    CashBackService::call()->addCashBack(
                        $prize,
                        "🏆 Победа в Викторине ({$winPercentage}%, {$score}/{$totalQuestions})",
                        $user
                    );
                }

                // Помечаем сессию как завершённую
                $session['finished'] = true;
                $session['finished_at'] = Carbon::now()->toIso8601String();
                $session['prize'] = $prize;
                $session['win_percentage'] = $winPercentage;
                $session['is_win'] = $isWin;

                // Записываем попытку
                $attempts = $meta['quiz_attempts'] ?? [];
                $attempts[] = [
                    'played_at' => Carbon::now()->toIso8601String(),
                    'score' => $score,
                    'total' => $totalQuestions,
                    'percentage' => $winPercentage,
                    'won' => $isWin,
                    'prize' => $prize,
                ];
                if (count($attempts) > 100) {
                    $attempts = array_slice($attempts, -100);
                }
                $meta['quiz_attempts'] = $attempts;

                // 📜 История
                $history = $meta['quiz_history'] ?? [];
                array_unshift($history, [
                    'id' => uniqid('quiz_'),
                    'date' => Carbon::now()->format('Y-m-d H:i'),
                    'score' => $score,
                    'total' => $totalQuestions,
                    'percentage' => $winPercentage,
                    'won' => $isWin,
                    'prize' => $prize,
                    'cost' => self::GAME_COST,
                    'net_profit' => $prize - self::GAME_COST,
                ]);
                if (count($history) > 50) {
                    $history = array_slice($history, 0, 50);
                }
                $meta['quiz_history'] = $history;

                // Очищаем активную сессию
                unset($meta['quiz_active_session']);
                $user->meta = $meta;
                $user->save();
            });
        } catch (\Throwable $e) {
            Log::error('[Quiz] Ошибка завершения викторины', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при завершении викторины',
            ], 500);
        }

        $user->refresh();
        $newBalance = (float) $user->cashback_balance;

        Log::info('[Quiz] Викторина завершена', [
            'user_id' => $user->id,
            'score' => $score,
            'total' => $totalQuestions,
            'percentage' => $winPercentage,
            'won' => $isWin,
            'prize' => $prize,
        ]);

        // 📢 Уведомления для идеального результата
        if ($winPercentage === 100) {
            $this->notifyPerfectScore($user, $score, $totalQuestions, $prize);
        }

        // Формируем детальные результаты (с правильными ответами для обзора)
        $review = $this->buildReview($session);

        return response()->json([
            'success' => true,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'win_percentage' => $winPercentage,
            'is_win' => $isWin,
            'prize' => $prize,
            'net_profit' => $prize - self::GAME_COST,
            'balance' => $newBalance,
            'review' => $review,
        ]);
    }

    // ==========================================
    // 🛠️ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==========================================

    /**
     * Вернуть вопросы БЕЗ правильных ответов (для клиента)
     */
    protected function getPublicQuestions(array $questions): array
    {
        return array_map(function ($q) {
            return [
                'id' => $q['id'],
                'text' => $q['text'],
                'answers' => $q['answers'], // БЕЗ isCorrect!
            ];
        }, $questions);
    }

    /**
     * Очистить сессию для getState (не отдаём correct_answer)
     */
    protected function sanitizeSession(array $session): array
    {
        return [
            'token' => $session['token'],
            'questions' => $this->getPublicQuestions($session['questions']),
            'user_answers' => $session['user_answers'] ?? [],
            'score' => $session['score'] ?? 0,
            'started_at' => $session['started_at'],
            'finished' => $session['finished'] ?? false,
        ];
    }

    /**
     * Построить детальный обзор результатов
     */
    protected function buildReview(array $session): array
    {
        $review = [];
        $userAnswers = $session['user_answers'] ?? [];

        foreach ($session['questions'] as $q) {
            $userAnswer = $userAnswers[$q['id']] ?? null;

            $answers = array_map(function ($a) use ($q, $userAnswer) {
                return [
                    'id' => $a['id'],
                    'text' => $a['text'],
                    'is_correct' => ($a['id'] === $q['correct_answer']),
                    'user_selected' => ($userAnswer && $userAnswer['answer_id'] === $a['id']),
                ];
            }, $q['answers']);

            $review[] = [
                'id' => $q['id'],
                'text' => $q['text'],
                'answers' => $answers,
                'user_answer_id' => $userAnswer['answer_id'] ?? null,
                'is_correct' => $userAnswer['is_correct'] ?? false,
            ];
        }

        return $review;
    }

    /**
     * 📢 Уведомление об идеальном результате
     */
    protected function notifyPerfectScore($user, int $score, int $total, int $prize): void
    {
        try {
            $tenant = app('tenant');
            $phone = $user->phone ?? 'не указан';
            $userName = $user->name ?? 'Не указано';

            $adminMessage = <<<HTML
🎯 <b>ИДЕАЛЬНЫЙ РЕЗУЛЬТАТ в Викторине!</b>

👤 <b>Клиент:</b> {$userName}
📱 <b>Телефон:</b> {$phone}
🧠 <b>Результат:</b> {$score}/{$total} (100%)
💰 <b>Приз:</b> +{$prize} бонусов

🏢 <b>Тенант:</b> {$tenant->name}
HTML;

            MessageService::call()->sendMessage([
                'message' => $adminMessage,
                'title' => '🎯 Идеальный результат в Викторине',
                'recipients' => ['partners' => true, 'telegram' => true],
                'meta' => [
                    'event_type' => 'quiz_perfect_score',
                    'customer_name' => $userName,
                    'customer_phone' => $phone,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Quiz] Ошибка уведомления', ['error' => $e->getMessage()]);
        }
    }
}
