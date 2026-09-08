<?php
// routes/agent.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Agent\{
    AgentProfileController,
    AgentFinanceController,
    AgentInvoiceController,
    AgentDocumentController,
    AgentTenantController,
    AgentClientController,
    AgentReferralController,
    MarketingController,
};

Route::prefix('agent')
    ->group(function () {

        // === Профиль ===
        Route::get('profile', [AgentProfileController::class, 'show']);
        Route::put('profile', [AgentProfileController::class, 'update']);
        Route::get('dashboard', [AgentProfileController::class, 'dashboard']);

        // === Финансы ===
        Route::prefix('finance')->group(function () {
            Route::get('transactions', [AgentFinanceController::class, 'transactions']);
            Route::post('payout', [AgentFinanceController::class, 'requestPayout']);
            Route::get('payouts', [AgentFinanceController::class, 'payouts']);
        });

        // === Счета ===
        Route::apiResource('invoices', AgentInvoiceController::class)->only(['index', 'store']);
        Route::post('invoices/{invoice}/send', [AgentInvoiceController::class, 'send']);

        // === Документы ===
        Route::get('documents', [AgentDocumentController::class, 'index']);
        Route::post('documents/upload', [AgentDocumentController::class, 'upload']);

        // === Приложения ===
        Route::get('tenants', [AgentTenantController::class, 'index']);

        // === Клиенты ===
        Route::apiResource('clients', AgentClientController::class)->only(['index', 'store', 'destroy']);

        // === Рефералы ===
        Route::prefix('referrals')->group(function () {
            Route::get('stats', [AgentReferralController::class, 'stats']);
            Route::get('link', [AgentReferralController::class, 'link']);
        });

        // === Маркетинг ===
        Route::get('marketing', [MarketingController::class, 'index']);
        Route::post('marketing/{material}/download', [MarketingController::class, 'download']);
    });
