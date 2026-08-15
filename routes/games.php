<?php

use App\Http\Controllers\Tenant\Games\CardGameController;
use App\Http\Controllers\Tenant\Games\DailyBonusController;
use App\Http\Controllers\Tenant\Games\GuessNumberController;
use App\Http\Controllers\Tenant\Games\PrizeCardController;
use App\Http\Controllers\Tenant\Games\QuizController;
use App\Http\Controllers\Tenant\Games\ScratchCardController;
use App\Http\Controllers\Tenant\Games\SlotMachineController;
use App\Http\Controllers\Tenant\Games\TreasureHuntController;
use App\Http\Controllers\Tenant\Games\WheelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Game Routes
|--------------------------------------------------------------------------
|
| Маршруты для всех игровых механик: карточные игры, викторины, слоты и т.д.
|
*/


// 🃏 Карточная игра с призами
Route::prefix('prize-card')->group(function () {
    Route::get('/settings', [PrizeCardController::class, 'getSettings']);
    Route::get('/state', [PrizeCardController::class, 'getState']);
    Route::post('/play', [PrizeCardController::class, 'play']);
});

// 🧠 Викторина
Route::prefix('quiz')->group(function () {
    Route::get('/state', [QuizController::class, 'getState']);
    Route::post('/start', [QuizController::class, 'startQuiz']);
    Route::post('/answer', [QuizController::class, 'submitAnswer']);
    Route::post('/finish', [QuizController::class, 'finishQuiz']);
});

// 🔢 Угадай число
Route::prefix('guess-number')->group(function () {
    Route::get('/state', [GuessNumberController::class, 'getState']);
    Route::post('/start', [GuessNumberController::class, 'startGame']);
    Route::post('/guess', [GuessNumberController::class, 'guess']);
    Route::post('/give-up', [GuessNumberController::class, 'giveUp']);
});

// 🗺️ Охота за сокровищами
Route::prefix('treasure-hunt')->group(function () {
    Route::get('/state', [TreasureHuntController::class, 'getState']);
    Route::post('/start', [TreasureHuntController::class, 'startGame']);
    Route::post('/reveal', [TreasureHuntController::class, 'revealCell']);
    Route::post('/give-up', [TreasureHuntController::class, 'giveUp']);
    Route::post('/booster', [TreasureHuntController::class, 'useBooster']);
    Route::post('/hint', [TreasureHuntController::class, 'getHint']);
});

// 🎰 Слот-машина
Route::prefix('slot-machine')->group(function () {
    Route::get('/settings', [SlotMachineController::class, 'getSettings']);
    Route::get('/state', [SlotMachineController::class, 'getState']);
    Route::post('/spin', [SlotMachineController::class, 'spin']);
});

// 🎁 Ежедневный бонус
Route::prefix('daily-bonus')->group(function () {
    Route::get('/settings', [DailyBonusController::class, 'getSettings']);
    Route::get('/state', [DailyBonusController::class, 'getState']);
    Route::post('/open', [DailyBonusController::class, 'open']);
    Route::post('/claim', [DailyBonusController::class, 'claim']);
});

// 🎴 Скретч-карта
Route::prefix('scratch-card')->group(function () {
    Route::get('/settings', [ScratchCardController::class, 'getSettings']);
    Route::get('/state', [ScratchCardController::class, 'getState']);
    Route::post('/start', [ScratchCardController::class, 'startGame']);
    Route::post('/confirm', [ScratchCardController::class, 'confirmPrize']);
});

// 🎴 Карточная игра (старая версия)
Route::prefix('card-game')->group(function () {
    Route::get('/settings', [CardGameController::class, 'getSettings']);
    Route::get('/state', [CardGameController::class, 'getState']);
    Route::post('/play', [CardGameController::class, 'play']);
});

// 🎡 Колесо фортуны
Route::prefix('wheel')->group(function () {
    Route::get('/data', [WheelController::class, 'getData']);
    Route::post('/record-attempt', [WheelController::class, 'recordAttempt']);
    Route::post('/win', [WheelController::class, 'saveWin']);
    Route::get('/history', [WheelController::class, 'getHistory']);
});


