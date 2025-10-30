<?php

use App\Jobs\GenerateMonthlyFinancialSummary;
use App\Models\Budget;
use App\Models\Invoice;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Financial Automation Schedules
Schedule::command('invoices:process-automation')->daily()->at('02:00');
Schedule::call(function () {
    Budget::processAutomaticUpdates();
})->daily()->at('03:00');
Schedule::call(function () {
    Invoice::processAutomaticActions();
})->daily()->at('04:00');
Schedule::call(function () {
    GenerateMonthlyFinancialSummary::dispatch();
})->monthlyOn(1, '05:00');
