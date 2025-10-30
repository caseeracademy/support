<?php

use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\OrderWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Order Status Webhook from main website (exempt from CSRF in bootstrap/app.php)
Route::post('/webhook/order-status', [OrderWebhookController::class, 'handle']);

// Financial Report PDF Downloads (protected by auth middleware)
Route::middleware(['auth'])->prefix('reports')->name('financial.reports.')->group(function () {
    Route::get('profit-loss', [FinancialReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('cash-flow', [FinancialReportController::class, 'cashFlow'])->name('cash-flow');
    Route::get('transaction-summary', [FinancialReportController::class, 'transactionSummary'])->name('transaction-summary');
    Route::get('customer-payments', [FinancialReportController::class, 'customerPayments'])->name('customer-payments');
    Route::get('trend-analysis', [FinancialReportController::class, 'trendAnalysis'])->name('trend-analysis');
});
