<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function profitLoss(Request $request): Responsable
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $data = $this->reportService->generateProfitLossStatement($startDate, $endDate);

        $pdf = Pdf::view('financial.reports.profit-loss', compact('data'))
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->headerView('financial.reports.header')
            ->footerView('financial.reports.footer');

        $filename = 'profit-loss-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function cashFlow(Request $request): Responsable
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $data = $this->reportService->generateCashFlowStatement($startDate, $endDate);

        $pdf = Pdf::view('financial.reports.cash-flow', compact('data'))
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->headerView('financial.reports.header')
            ->footerView('financial.reports.footer');

        $filename = 'cash-flow-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function transactionSummary(Request $request): Responsable
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:income,expense',
            'category_id' => 'nullable|exists:categories,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $data = $this->reportService->generateTransactionSummary(
            $startDate,
            $endDate,
            $request->type,
            $request->category_id,
            $request->payment_method_id
        );

        $pdf = Pdf::view('financial.reports.transaction-summary', compact('data'))
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->headerView('financial.reports.header')
            ->footerView('financial.reports.footer');

        $filename = 'transaction-summary-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function customerPayments(Request $request): Responsable
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfYear();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfYear();

        $data = $this->reportService->generateCustomerPaymentHistory(
            $request->customer_id,
            $startDate,
            $endDate
        );

        $pdf = Pdf::view('financial.reports.customer-payments', compact('data'))
            ->format('a4')
            ->margins(10, 10, 10, 10)
            ->headerView('financial.reports.header')
            ->footerView('financial.reports.footer');

        $filename = 'customer-payments-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function trendAnalysis(Request $request): Responsable
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'period' => 'nullable|in:daily,weekly,monthly',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $period = $request->period ?? 'monthly';

        $data = $this->reportService->generateTrendAnalysis($startDate, $endDate, $period);

        $pdf = Pdf::view('financial.reports.trend-analysis', compact('data'))
            ->format('a4')
            ->landscape()
            ->margins(10, 10, 10, 10)
            ->headerView('financial.reports.header')
            ->footerView('financial.reports.footer');

        $filename = 'trend-analysis-'.$period.'-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
