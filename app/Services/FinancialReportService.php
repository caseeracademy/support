<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function generateProfitLossStatement(Carbon $startDate, Carbon $endDate): array
    {
        $income = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->get();

        $expenses = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->get();

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netProfit = $totalIncome - $totalExpenses;

        return [
            'period' => [
                'start' => $startDate->format('F j, Y'),
                'end' => $endDate->format('F j, Y'),
            ],
            'income' => [
                'total' => $totalIncome,
                'formatted' => '$'.number_format($totalIncome, 2),
                'categories' => $this->groupByCategory($income),
                'transactions' => $income->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->transaction_date->format('M j, Y'),
                        'title' => $transaction->title,
                        'amount' => $transaction->amount,
                        'formatted_amount' => '$'.number_format($transaction->amount, 2),
                        'category' => $transaction->category?->name ?? 'Uncategorized',
                        'reference' => $transaction->reference_number,
                    ];
                }),
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'formatted' => '$'.number_format($totalExpenses, 2),
                'categories' => $this->groupByCategory($expenses),
                'transactions' => $expenses->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->transaction_date->format('M j, Y'),
                        'title' => $transaction->title,
                        'amount' => $transaction->amount,
                        'formatted_amount' => '$'.number_format($transaction->amount, 2),
                        'category' => $transaction->category?->name ?? 'Uncategorized',
                        'reference' => $transaction->reference_number,
                    ];
                }),
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'formatted_income' => '$'.number_format($totalIncome, 2),
                'formatted_expenses' => '$'.number_format($totalExpenses, 2),
                'formatted_profit' => '$'.number_format($netProfit, 2),
                'profit_margin' => $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 2) : 0,
                'is_profitable' => $netProfit > 0,
            ],
        ];
    }

    public function generateCashFlowStatement(Carbon $startDate, Carbon $endDate): array
    {
        $data = [];
        $cumulativeBalance = 0;

        // Get opening balance (all transactions before start date)
        $openingBalance = Transaction::where('status', 'completed')
            ->where('transaction_date', '<', $startDate)
            ->selectRaw('
                SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) - 
                SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as balance
            ')
            ->value('balance') ?? 0;

        $cumulativeBalance = $openingBalance;

        // Generate daily cash flow
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dailyIncome = Transaction::where('type', 'income')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $current)
                ->sum('amount');

            $dailyExpenses = Transaction::where('type', 'expense')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $current)
                ->sum('amount');

            $netCashFlow = $dailyIncome - $dailyExpenses;
            $cumulativeBalance += $netCashFlow;

            $data[] = [
                'date' => $current->format('M j, Y'),
                'date_short' => $current->format('j'),
                'income' => $dailyIncome,
                'expenses' => $dailyExpenses,
                'net_flow' => $netCashFlow,
                'cumulative_balance' => $cumulativeBalance,
                'formatted_income' => '$'.number_format($dailyIncome, 2),
                'formatted_expenses' => '$'.number_format($dailyExpenses, 2),
                'formatted_net_flow' => '$'.number_format($netCashFlow, 2),
                'formatted_balance' => '$'.number_format($cumulativeBalance, 2),
            ];

            $current->addDay();
        }

        return [
            'period' => [
                'start' => $startDate->format('F j, Y'),
                'end' => $endDate->format('F j, Y'),
            ],
            'opening_balance' => [
                'amount' => $openingBalance,
                'formatted' => '$'.number_format($openingBalance, 2),
            ],
            'closing_balance' => [
                'amount' => $cumulativeBalance,
                'formatted' => '$'.number_format($cumulativeBalance, 2),
            ],
            'daily_flows' => $data,
            'summary' => [
                'total_inflow' => collect($data)->sum('income'),
                'total_outflow' => collect($data)->sum('expenses'),
                'net_change' => $cumulativeBalance - $openingBalance,
                'formatted_inflow' => '$'.number_format(collect($data)->sum('income'), 2),
                'formatted_outflow' => '$'.number_format(collect($data)->sum('expenses'), 2),
                'formatted_net_change' => '$'.number_format($cumulativeBalance - $openingBalance, 2),
            ],
        ];
    }

    public function generateTransactionSummary(
        Carbon $startDate,
        Carbon $endDate,
        ?string $type = null,
        ?int $categoryId = null,
        ?int $paymentMethodId = null
    ): array {
        $query = Transaction::where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['category', 'paymentMethod', 'createdBy']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        return [
            'period' => [
                'start' => $startDate->format('F j, Y'),
                'end' => $endDate->format('F j, Y'),
            ],
            'filters' => [
                'type' => $type,
                'category_id' => $categoryId,
                'category_name' => $categoryId ? Category::find($categoryId)?->name : null,
                'payment_method_id' => $paymentMethodId,
                'payment_method_name' => $paymentMethodId ? PaymentMethod::find($paymentMethodId)?->name : null,
            ],
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference_number,
                    'date' => $transaction->transaction_date->format('M j, Y'),
                    'type' => $transaction->type,
                    'title' => $transaction->title,
                    'description' => $transaction->description,
                    'amount' => $transaction->amount,
                    'formatted_amount' => '$'.number_format($transaction->amount, 2),
                    'currency' => $transaction->currency,
                    'category' => $transaction->category?->name ?? 'Uncategorized',
                    'payment_method' => $transaction->paymentMethod?->name ?? 'Unknown',
                    'status' => $transaction->status,
                    'created_by' => $transaction->createdBy?->name ?? 'System',
                ];
            }),
            'summary' => [
                'total_count' => $transactions->count(),
                'income_count' => $transactions->where('type', 'income')->count(),
                'expense_count' => $transactions->where('type', 'expense')->count(),
                'total_amount' => $transactions->sum('amount'),
                'income_amount' => $transactions->where('type', 'income')->sum('amount'),
                'expense_amount' => $transactions->where('type', 'expense')->sum('amount'),
                'formatted_total' => '$'.number_format($transactions->sum('amount'), 2),
                'formatted_income' => '$'.number_format($transactions->where('type', 'income')->sum('amount'), 2),
                'formatted_expenses' => '$'.number_format($transactions->where('type', 'expense')->sum('amount'), 2),
            ],
        ];
    }

    public function generateCustomerPaymentHistory(
        ?int $customerId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->startOfYear();
        $endDate = $endDate ?? Carbon::now()->endOfYear();

        $query = Customer::query()->with(['transactions', 'tickets.invoices']);

        if ($customerId) {
            $query->where('id', $customerId);
        }

        $customers = $query->get();

        return [
            'period' => [
                'start' => $startDate->format('F j, Y'),
                'end' => $endDate->format('F j, Y'),
            ],
            'customers' => $customers->map(function ($customer) use ($startDate, $endDate) {
                $transactions = $customer->transactions()
                    ->where('status', 'completed')
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->get();

                $invoices = collect();
                foreach ($customer->tickets as $ticket) {
                    $invoices = $invoices->merge($ticket->invoices ?? collect());
                }

                $totalPaid = $transactions->where('type', 'income')->sum('amount');
                $totalInvoiced = $invoices->sum('total_amount');
                $totalOutstanding = $invoices->where('status', '!=', 'paid')->sum('remaining_amount');

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone_number,
                    'transactions_count' => $transactions->count(),
                    'invoices_count' => $invoices->count(),
                    'total_paid' => $totalPaid,
                    'total_invoiced' => $totalInvoiced,
                    'total_outstanding' => $totalOutstanding,
                    'formatted_paid' => '$'.number_format($totalPaid, 2),
                    'formatted_invoiced' => '$'.number_format($totalInvoiced, 2),
                    'formatted_outstanding' => '$'.number_format($totalOutstanding, 2),
                    'payment_status' => $totalOutstanding > 0 ? 'outstanding' : 'current',
                ];
            })->sortByDesc('total_paid')->values(),
        ];
    }

    public function generateTrendAnalysis(Carbon $startDate, Carbon $endDate, string $period = 'monthly'): array
    {
        $data = [];

        switch ($period) {
            case 'daily':
                $current = $startDate->copy();
                while ($current->lte($endDate)) {
                    $data[] = $this->getPeriodData($current->copy(), $current->copy()->endOfDay(), $current->format('M j'));
                    $current->addDay();
                }
                break;

            case 'weekly':
                $current = $startDate->copy()->startOfWeek();
                while ($current->lte($endDate)) {
                    $weekEnd = $current->copy()->endOfWeek();
                    if ($weekEnd->gt($endDate)) {
                        $weekEnd = $endDate->copy();
                    }
                    $data[] = $this->getPeriodData($current->copy(), $weekEnd, 'Week of '.$current->format('M j'));
                    $current->addWeek();
                }
                break;

            case 'monthly':
            default:
                $current = $startDate->copy()->startOfMonth();
                while ($current->lte($endDate)) {
                    $monthEnd = $current->copy()->endOfMonth();
                    if ($monthEnd->gt($endDate)) {
                        $monthEnd = $endDate->copy();
                    }
                    $data[] = $this->getPeriodData($current->copy(), $monthEnd, $current->format('M Y'));
                    $current->addMonth();
                }
                break;
        }

        return [
            'period' => $period,
            'date_range' => [
                'start' => $startDate->format('F j, Y'),
                'end' => $endDate->format('F j, Y'),
            ],
            'data' => $data,
            'summary' => [
                'total_income' => collect($data)->sum('income'),
                'total_expenses' => collect($data)->sum('expenses'),
                'average_profit' => collect($data)->average('profit'),
                'best_period' => collect($data)->sortByDesc('profit')->first(),
                'worst_period' => collect($data)->sortBy('profit')->first(),
            ],
        ];
    }

    private function groupByCategory(Collection $transactions): array
    {
        return $transactions->groupBy('category.name')
            ->map(function ($group, $categoryName) {
                $total = $group->sum('amount');

                return [
                    'name' => $categoryName ?: 'Uncategorized',
                    'count' => $group->count(),
                    'total' => $total,
                    'formatted_total' => '$'.number_format($total, 2),
                    'percentage' => 0, // Will be calculated later if needed
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    private function getPeriodData(Carbon $start, Carbon $end, string $label): array
    {
        $income = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $expenses = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $profit = $income - $expenses;

        return [
            'label' => $label,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $profit,
            'formatted_income' => '$'.number_format($income, 2),
            'formatted_expenses' => '$'.number_format($expenses, 2),
            'formatted_profit' => '$'.number_format($profit, 2),
            'profit_margin' => $income > 0 ? round(($profit / $income) * 100, 2) : 0,
        ];
    }
}

