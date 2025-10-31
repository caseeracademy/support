<?php

use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\OrderWebhookController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Order Status Webhook from main website (exempt from CSRF in bootstrap/app.php)
Route::post('/webhook/order-status', [OrderWebhookController::class, 'handle']);

// Fallback POST route for Filament login when Livewire doesn't intercept
// This handles cases where Livewire scripts fail to load or initialize
Route::post('/admin/login', function () {
    $credentials = request()->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $remember = request()->boolean('remember');

    if (Auth::attempt($credentials, $remember)) {
        request()->session()->regenerate();

        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})
    ->middleware('web')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Financial Report PDF Downloads (protected by auth middleware)
Route::middleware(['auth'])->prefix('reports')->name('financial.reports.')->group(function () {
    Route::get('profit-loss', [FinancialReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('cash-flow', [FinancialReportController::class, 'cashFlow'])->name('cash-flow');
    Route::get('transaction-summary', [FinancialReportController::class, 'transactionSummary'])->name('transaction-summary');
    Route::get('customer-payments', [FinancialReportController::class, 'customerPayments'])->name('customer-payments');
    Route::get('trend-analysis', [FinancialReportController::class, 'trendAnalysis'])->name('trend-analysis');
});
