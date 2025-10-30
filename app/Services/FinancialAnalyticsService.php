<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;

class FinancialAnalyticsService
{
    public function forecastRevenue(int $forecastMonths = 3, string $method = 'linear'): array
    {
        // Get historical revenue data (last 12 months)
        $historicalData = $this->getHistoricalRevenue(12);

        $forecast = match ($method) {
            'linear' => $this->linearForecast($historicalData, $forecastMonths),
            'moving_average' => $this->movingAverageForecast($historicalData, $forecastMonths),
            'seasonal' => $this->seasonalForecast($historicalData, $forecastMonths),
            default => $this->linearForecast($historicalData, $forecastMonths),
        };

        return [
            'method' => $method,
            'forecast_months' => $forecastMonths,
            'historical_data' => $historicalData,
            'forecasted_data' => $forecast,
            'total_forecasted_revenue' => collect($forecast)->sum('amount'),
            'formatted_forecast' => '$'.number_format(collect($forecast)->sum('amount'), 2),
            'confidence_level' => $this->calculateConfidence($historicalData),
        ];
    }

    protected function getHistoricalRevenue(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            $revenue = Transaction::where('type', 'income')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $data[] = [
                'month' => $startOfMonth->format('M Y'),
                'month_number' => $startOfMonth->month,
                'year' => $startOfMonth->year,
                'amount' => $revenue,
                'formatted_amount' => '$'.number_format($revenue, 2),
            ];
        }

        return $data;
    }

    protected function linearForecast(array $historical, int $forecastMonths): array
    {
        $amounts = collect($historical)->pluck('amount')->values()->all();

        // Calculate linear regression
        $n = count($amounts);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($amounts);
        $sumXY = 0;
        $sumXX = 0;

        foreach ($amounts as $i => $value) {
            $x = $i + 1;
            $sumXY += $x * $value;
            $sumXX += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Generate forecast
        $forecast = [];
        for ($i = 1; $i <= $forecastMonths; $i++) {
            $month = Carbon::now()->addMonths($i);
            $predictedValue = $intercept + $slope * ($n + $i);
            $predictedValue = max(0, $predictedValue); // Ensure non-negative

            $forecast[] = [
                'month' => $month->format('M Y'),
                'month_number' => $month->month,
                'year' => $month->year,
                'amount' => round($predictedValue, 2),
                'formatted_amount' => '$'.number_format($predictedValue, 2),
                'is_forecast' => true,
            ];
        }

        return $forecast;
    }

    protected function movingAverageForecast(array $historical, int $forecastMonths, int $window = 3): array
    {
        $amounts = collect($historical)->pluck('amount')->values();

        // Calculate moving average
        $movingAvg = $amounts->slice(-$window)->avg();

        $forecast = [];
        for ($i = 1; $i <= $forecastMonths; $i++) {
            $month = Carbon::now()->addMonths($i);

            $forecast[] = [
                'month' => $month->format('M Y'),
                'month_number' => $month->month,
                'year' => $month->year,
                'amount' => round($movingAvg, 2),
                'formatted_amount' => '$'.number_format($movingAvg, 2),
                'is_forecast' => true,
            ];
        }

        return $forecast;
    }

    protected function seasonalForecast(array $historical, int $forecastMonths): array
    {
        // Group historical data by month to find seasonal patterns
        $byMonth = collect($historical)->groupBy('month_number')
            ->map(fn ($group) => $group->avg('amount'));

        $forecast = [];
        for ($i = 1; $i <= $forecastMonths; $i++) {
            $month = Carbon::now()->addMonths($i);
            $seasonalAvg = $byMonth->get($month->month, collect($historical)->avg('amount'));

            // Apply growth trend
            $trend = $this->calculateTrend($historical);
            $adjustedValue = $seasonalAvg * (1 + $trend);

            $forecast[] = [
                'month' => $month->format('M Y'),
                'month_number' => $month->month,
                'year' => $month->year,
                'amount' => round(max(0, $adjustedValue), 2),
                'formatted_amount' => '$'.number_format(max(0, $adjustedValue), 2),
                'is_forecast' => true,
            ];
        }

        return $forecast;
    }

    protected function calculateTrend(array $historical): float
    {
        $amounts = collect($historical)->pluck('amount')->values();

        if ($amounts->count() < 2) {
            return 0;
        }

        $firstHalf = $amounts->slice(0, (int) ($amounts->count() / 2))->avg();
        $secondHalf = $amounts->slice((int) ($amounts->count() / 2))->avg();

        if ($firstHalf == 0) {
            return 0;
        }

        return ($secondHalf - $firstHalf) / $firstHalf;
    }

    protected function calculateConfidence(array $historical): float
    {
        $amounts = collect($historical)->pluck('amount');

        if ($amounts->count() < 3) {
            return 50;
        }

        // Calculate coefficient of variation
        $mean = $amounts->avg();
        $stdDev = $this->standardDeviation($amounts->all());

        if ($mean == 0) {
            return 50;
        }

        $cv = $stdDev / $mean;

        // Convert to confidence percentage (lower CV = higher confidence)
        $confidence = max(20, 100 - ($cv * 100));

        return min(95, $confidence);
    }

    protected function standardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($val) => ($val - $mean) ** 2, $values)) / count($values);

        return sqrt($variance);
    }

    public function analyzeCashFlowPattern(int $months = 12): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $income = Transaction::where('type', 'income')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $expenses = Transaction::where('type', 'expense')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $netCashFlow = $income - $expenses;

            $data[] = [
                'month' => $month->format('M Y'),
                'income' => $income,
                'expenses' => $expenses,
                'net_cash_flow' => $netCashFlow,
                'formatted_income' => '$'.number_format($income, 2),
                'formatted_expenses' => '$'.number_format($expenses, 2),
                'formatted_net' => '$'.number_format($netCashFlow, 2),
            ];
        }

        $avgIncome = collect($data)->avg('income');
        $avgExpenses = collect($data)->avg('expenses');
        $avgNetCashFlow = collect($data)->avg('net_cash_flow');

        return [
            'months_analyzed' => $months,
            'monthly_data' => $data,
            'patterns' => [
                'average_income' => $avgIncome,
                'average_expenses' => $avgExpenses,
                'average_net_cash_flow' => $avgNetCashFlow,
                'formatted_avg_income' => '$'.number_format($avgIncome, 2),
                'formatted_avg_expenses' => '$'.number_format($avgExpenses, 2),
                'formatted_avg_net' => '$'.number_format($avgNetCashFlow, 2),
                'cash_flow_volatility' => $this->standardDeviation(collect($data)->pluck('net_cash_flow')->all()),
            ],
            'trend' => $this->determineCashFlowTrend($data),
        ];
    }

    protected function determineCashFlowTrend(array $data): string
    {
        $recentAvg = collect($data)->slice(-3)->avg('net_cash_flow');
        $olderAvg = collect($data)->slice(0, 3)->avg('net_cash_flow');

        if ($recentAvg > $olderAvg * 1.1) {
            return 'improving';
        } elseif ($recentAvg < $olderAvg * 0.9) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    public function calculateCustomerLifetimeValue(int $months = 12): array
    {
        $cutoffDate = Carbon::now()->subMonths($months);

        $customers = Customer::with(['transactions' => function ($query) use ($cutoffDate) {
            $query->where('type', 'income')
                ->where('status', 'completed')
                ->where('transaction_date', '>=', $cutoffDate);
        }])
            ->has('transactions')
            ->get();

        $clvData = $customers->map(function ($customer) {
            $totalRevenue = $customer->transactions->sum('amount');
            $transactionCount = $customer->transactions->count();
            $avgTransactionValue = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;
            $monthsActive = $customer->transactions->min('transaction_date') ?
                Carbon::parse($customer->transactions->min('transaction_date'))->diffInMonths(now()) + 1 : 1;

            $monthlyValue = $monthsActive > 0 ? $totalRevenue / $monthsActive : 0;
            $projectedLifetimeValue = $monthlyValue * 36; // Project over 3 years

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'total_revenue' => $totalRevenue,
                'transaction_count' => $transactionCount,
                'avg_transaction_value' => $avgTransactionValue,
                'months_active' => $monthsActive,
                'monthly_value' => $monthlyValue,
                'projected_lifetime_value' => $projectedLifetimeValue,
                'formatted_revenue' => '$'.number_format($totalRevenue, 2),
                'formatted_clv' => '$'.number_format($projectedLifetimeValue, 2),
            ];
        })->sortByDesc('projected_lifetime_value')->values();

        return [
            'period_months' => $months,
            'customers_analyzed' => $clvData->count(),
            'average_clv' => $clvData->avg('projected_lifetime_value'),
            'total_clv' => $clvData->sum('projected_lifetime_value'),
            'formatted_avg_clv' => '$'.number_format($clvData->avg('projected_lifetime_value'), 2),
            'formatted_total_clv' => '$'.number_format($clvData->sum('projected_lifetime_value'), 2),
            'top_customers' => $clvData->take(10)->toArray(),
            'all_customers' => $clvData->toArray(),
        ];
    }

    public function analyzeExpenseTrends(int $months = 6): array
    {
        $categoryTrends = [];
        $categories = \App\Models\Category::where('type', 'expense')->get();

        foreach ($categories as $category) {
            $monthlyData = [];

            for ($i = $months - 1; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $startOfMonth = $month->copy()->startOfMonth();
                $endOfMonth = $month->copy()->endOfMonth();

                $amount = Transaction::where('type', 'expense')
                    ->where('status', 'completed')
                    ->where('category_id', $category->id)
                    ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                    ->sum('amount');

                $monthlyData[] = [
                    'month' => $month->format('M Y'),
                    'amount' => $amount,
                ];
            }

            $amounts = collect($monthlyData)->pluck('amount');
            $trend = $this->calculateTrendDirection($amounts->all());
            $avgMonthly = $amounts->avg();

            if ($avgMonthly > 0) { // Only include categories with expenses
                $categoryTrends[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_color' => $category->color,
                    'monthly_data' => $monthlyData,
                    'average_monthly' => $avgMonthly,
                    'trend' => $trend,
                    'formatted_avg' => '$'.number_format($avgMonthly, 2),
                    'recommendations' => $this->getExpenseRecommendations($trend, $avgMonthly, $category->name),
                ];
            }
        }

        return [
            'months_analyzed' => $months,
            'categories_analyzed' => count($categoryTrends),
            'category_trends' => $categoryTrends,
            'total_monthly_avg' => collect($categoryTrends)->sum('average_monthly'),
            'formatted_total_avg' => '$'.number_format(collect($categoryTrends)->sum('average_monthly'), 2),
        ];
    }

    protected function calculateTrendDirection(array $values): string
    {
        if (count($values) < 3) {
            return 'insufficient_data';
        }

        $firstThird = array_slice($values, 0, (int) (count($values) / 3));
        $lastThird = array_slice($values, -(int) (count($values) / 3));

        $firstAvg = array_sum($firstThird) / count($firstThird);
        $lastAvg = array_sum($lastThird) / count($lastThird);

        if ($firstAvg == 0) {
            return 'no_data';
        }

        $change = (($lastAvg - $firstAvg) / $firstAvg) * 100;

        if ($change > 15) {
            return 'increasing_strongly';
        } elseif ($change > 5) {
            return 'increasing';
        } elseif ($change < -15) {
            return 'decreasing_strongly';
        } elseif ($change < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    protected function getExpenseRecommendations(string $trend, float $avgMonthly, string $categoryName): string
    {
        return match ($trend) {
            'increasing_strongly' => "'{$categoryName}' expenses are increasing rapidly. Review for cost optimization opportunities.",
            'increasing' => "'{$categoryName}' expenses are trending upward. Monitor closely.",
            'decreasing_strongly' => "'{$categoryName}' expenses are decreasing significantly. Good cost control!",
            'decreasing' => "'{$categoryName}' expenses are trending downward. Maintain this efficiency.",
            'stable' => "'{$categoryName}' expenses are stable and predictable.",
            default => 'Insufficient data for recommendations.',
        };
    }

    public function projectCashFlow(int $months = 6): array
    {
        $historical = $this->analyzeCashFlowPattern(12);
        $avgIncome = $historical['patterns']['average_income'];
        $avgExpenses = $historical['patterns']['average_expenses'];
        $trend = $this->calculateTrend($historical['monthly_data']);

        $projections = [];
        $cumulativeBalance = 0;

        for ($i = 1; $i <= $months; $i++) {
            $month = Carbon::now()->addMonths($i);

            // Apply trend to projections
            $projectedIncome = $avgIncome * (1 + ($trend * $i * 0.1));
            $projectedExpenses = $avgExpenses * (1 + ($trend * $i * 0.05));
            $netCashFlow = $projectedIncome - $projectedExpenses;
            $cumulativeBalance += $netCashFlow;

            $projections[] = [
                'month' => $month->format('M Y'),
                'projected_income' => round($projectedIncome, 2),
                'projected_expenses' => round($projectedExpenses, 2),
                'net_cash_flow' => round($netCashFlow, 2),
                'cumulative_balance' => round($cumulativeBalance, 2),
                'formatted_income' => '$'.number_format($projectedIncome, 2),
                'formatted_expenses' => '$'.number_format($projectedExpenses, 2),
                'formatted_net' => '$'.number_format($netCashFlow, 2),
                'formatted_balance' => '$'.number_format($cumulativeBalance, 2),
            ];
        }

        return [
            'projection_months' => $months,
            'based_on_historical_months' => 12,
            'projections' => $projections,
            'summary' => [
                'total_projected_income' => collect($projections)->sum('projected_income'),
                'total_projected_expenses' => collect($projections)->sum('projected_expenses'),
                'ending_balance' => $cumulativeBalance,
                'formatted_total_income' => '$'.number_format(collect($projections)->sum('projected_income'), 2),
                'formatted_total_expenses' => '$'.number_format(collect($projections)->sum('projected_expenses'), 2),
                'formatted_ending_balance' => '$'.number_format($cumulativeBalance, 2),
            ],
        ];
    }

    public function generateScenarioAnalysis(array $scenarios = []): array
    {
        $baseline = $this->projectCashFlow(6);

        $defaultScenarios = [
            'optimistic' => [
                'name' => 'Optimistic',
                'description' => '20% revenue increase, 10% expense reduction',
                'revenue_multiplier' => 1.2,
                'expense_multiplier' => 0.9,
            ],
            'pessimistic' => [
                'name' => 'Pessimistic',
                'description' => '20% revenue decrease, 10% expense increase',
                'revenue_multiplier' => 0.8,
                'expense_multiplier' => 1.1,
            ],
            'realistic' => [
                'name' => 'Realistic',
                'description' => '5% revenue growth, stable expenses',
                'revenue_multiplier' => 1.05,
                'expense_multiplier' => 1.0,
            ],
        ];

        $scenarios = array_merge($defaultScenarios, $scenarios);
        $scenarioResults = [];

        foreach ($scenarios as $key => $scenario) {
            $projectedData = [];

            foreach ($baseline['projections'] as $month) {
                $adjustedIncome = $month['projected_income'] * $scenario['revenue_multiplier'];
                $adjustedExpenses = $month['projected_expenses'] * $scenario['expense_multiplier'];
                $netFlow = $adjustedIncome - $adjustedExpenses;

                $projectedData[] = [
                    'month' => $month['month'],
                    'income' => round($adjustedIncome, 2),
                    'expenses' => round($adjustedExpenses, 2),
                    'net_flow' => round($netFlow, 2),
                    'formatted_income' => '$'.number_format($adjustedIncome, 2),
                    'formatted_expenses' => '$'.number_format($adjustedExpenses, 2),
                    'formatted_net' => '$'.number_format($netFlow, 2),
                ];
            }

            $scenarioResults[$key] = [
                'name' => $scenario['name'],
                'description' => $scenario['description'],
                'projections' => $projectedData,
                'total_net_cash_flow' => collect($projectedData)->sum('net_flow'),
                'formatted_total' => '$'.number_format(collect($projectedData)->sum('net_flow'), 2),
            ];
        }

        return [
            'baseline' => $baseline,
            'scenarios' => $scenarioResults,
        ];
    }

    public function identifyCostOptimizationOpportunities(): array
    {
        $expenseAnalysis = $this->analyzeExpenseTrends(6);
        $opportunities = [];

        foreach ($expenseAnalysis['category_trends'] as $categoryTrend) {
            if (in_array($categoryTrend['trend'], ['increasing_strongly', 'increasing'])) {
                $opportunities[] = [
                    'category' => $categoryTrend['category_name'],
                    'current_monthly_avg' => $categoryTrend['average_monthly'],
                    'trend' => $categoryTrend['trend'],
                    'potential_savings' => $categoryTrend['average_monthly'] * 0.15, // Assume 15% reduction possible
                    'formatted_current' => $categoryTrend['formatted_avg'],
                    'formatted_savings' => '$'.number_format($categoryTrend['average_monthly'] * 0.15, 2),
                    'recommendation' => "Review '{$categoryTrend['category_name']}' expenses for reduction opportunities",
                    'priority' => $categoryTrend['trend'] === 'increasing_strongly' ? 'high' : 'medium',
                ];
            }
        }

        return [
            'opportunities_found' => count($opportunities),
            'total_potential_savings' => collect($opportunities)->sum('potential_savings'),
            'formatted_potential_savings' => '$'.number_format(collect($opportunities)->sum('potential_savings'), 2),
            'opportunities' => $opportunities,
        ];
    }

    public function getFinancialHealthScore(): array
    {
        $profitability = $this->calculateProfitabilityScore();
        $cashFlow = $this->calculateCashFlowScore();
        $growth = $this->calculateGrowthScore();
        $efficiency = $this->calculateEfficiencyScore();

        $overallScore = ($profitability + $cashFlow + $growth + $efficiency) / 4;

        return [
            'overall_score' => round($overallScore, 1),
            'overall_grade' => $this->getGrade($overallScore),
            'scores' => [
                'profitability' => [
                    'score' => $profitability,
                    'grade' => $this->getGrade($profitability),
                ],
                'cash_flow' => [
                    'score' => $cashFlow,
                    'grade' => $this->getGrade($cashFlow),
                ],
                'growth' => [
                    'score' => $growth,
                    'grade' => $this->getGrade($growth),
                ],
                'efficiency' => [
                    'score' => $efficiency,
                    'grade' => $this->getGrade($efficiency),
                ],
            ],
            'recommendations' => $this->getHealthRecommendations($overallScore, [
                'profitability' => $profitability,
                'cash_flow' => $cashFlow,
                'growth' => $growth,
                'efficiency' => $efficiency,
            ]),
        ];
    }

    protected function calculateProfitabilityScore(): float
    {
        $lastMonth = Carbon::now()->subMonth();
        $income = Transaction::income()->completed()
            ->where('transaction_date', '>=', $lastMonth)
            ->sum('amount');
        $expenses = Transaction::expense()->completed()
            ->where('transaction_date', '>=', $lastMonth)
            ->sum('amount');

        if ($income == 0) {
            return 0;
        }

        $profitMargin = (($income - $expenses) / $income) * 100;

        return min(100, max(0, $profitMargin));
    }

    protected function calculateCashFlowScore(): float
    {
        $cashFlowAnalysis = $this->analyzeCashFlowPattern(6);
        $avgNetCashFlow = $cashFlowAnalysis['patterns']['average_net_cash_flow'];
        $avgIncome = $cashFlowAnalysis['patterns']['average_income'];

        if ($avgIncome == 0) {
            return 50;
        }

        $cashFlowRatio = ($avgNetCashFlow / $avgIncome) * 100;

        return min(100, max(0, $cashFlowRatio + 50));
    }

    protected function calculateGrowthScore(): float
    {
        $last6Months = Transaction::income()->completed()
            ->where('transaction_date', '>=', Carbon::now()->subMonths(6))
            ->sum('amount');
        $previous6Months = Transaction::income()->completed()
            ->whereBetween('transaction_date', [Carbon::now()->subMonths(12), Carbon::now()->subMonths(6)])
            ->sum('amount');

        if ($previous6Months == 0) {
            return $last6Months > 0 ? 75 : 50;
        }

        $growthRate = (($last6Months - $previous6Months) / $previous6Months) * 100;

        return min(100, max(0, 50 + $growthRate));
    }

    protected function calculateEfficiencyScore(): float
    {
        $lastMonth = Carbon::now()->subMonth();
        $income = Transaction::income()->completed()
            ->where('transaction_date', '>=', $lastMonth)
            ->sum('amount');
        $expenses = Transaction::expense()->completed()
            ->where('transaction_date', '>=', $lastMonth)
            ->sum('amount');

        if ($income == 0) {
            return 50;
        }

        $expenseRatio = ($expenses / $income) * 100;

        // Lower expense ratio = higher efficiency score
        return min(100, max(0, 100 - $expenseRatio));
    }

    protected function getGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }

    protected function getHealthRecommendations(float $overallScore, array $scores): array
    {
        $recommendations = [];

        if ($scores['profitability'] < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'Profitability',
                'message' => 'Profit margins are low. Focus on increasing revenue or reducing costs.',
            ];
        }

        if ($scores['cash_flow'] < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'Cash Flow',
                'message' => 'Cash flow needs improvement. Review payment collection and expense timing.',
            ];
        }

        if ($scores['growth'] < 50) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'Growth',
                'message' => 'Revenue growth is stagnant. Consider marketing initiatives or new products/services.',
            ];
        }

        if ($scores['efficiency'] < 70) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'Efficiency',
                'message' => 'Operating efficiency could be improved. Look for ways to reduce operational costs.',
            ];
        }

        if ($overallScore >= 80) {
            $recommendations[] = [
                'priority' => 'low',
                'area' => 'Overall',
                'message' => 'Financial health is excellent. Continue current practices and look for growth opportunities.',
            ];
        }

        return $recommendations;
    }
}




