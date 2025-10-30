<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BudgetTrackingService
{
    public function updateAllBudgetSpending(): int
    {
        $count = 0;

        Budget::active()->chunk(20, function ($budgets) use (&$count) {
            foreach ($budgets as $budget) {
                $budget->updateSpentAmounts();
                $count++;
            }
        });

        return $count;
    }

    public function checkAllBudgetAlerts(): array
    {
        $allAlerts = [];

        Budget::active()->current()->with(['budgetCategories.category'])->chunk(20, function ($budgets) use (&$allAlerts) {
            foreach ($budgets as $budget) {
                $alerts = $budget->checkBudgetAlerts();
                if (! empty($alerts)) {
                    $allAlerts[$budget->id] = [
                        'budget' => $budget,
                        'alerts' => $alerts,
                    ];
                }
            }
        });

        return $allAlerts;
    }

    public function getBudgetPerformanceAnalysis(Budget $budget): array
    {
        $budget->load(['budgetCategories.category']);

        $analysis = [
            'overall' => $this->getOverallPerformance($budget),
            'categories' => $this->getCategoryPerformance($budget),
            'trends' => $this->getBudgetTrends($budget),
            'projections' => $this->getBudgetProjections($budget),
        ];

        return $analysis;
    }

    protected function getOverallPerformance(Budget $budget): array
    {
        $totalAllocated = $budget->total_allocated;
        $totalSpent = $budget->total_spent;
        $spentPercentage = $budget->spent_percentage;
        $timeElapsedPercentage = $budget->progress_percentage;

        // Calculate burn rate efficiency
        $burnRateEfficiency = $timeElapsedPercentage > 0 ?
            ($spentPercentage / $timeElapsedPercentage) * 100 : 0;

        return [
            'total_allocated' => $totalAllocated,
            'total_spent' => $totalSpent,
            'total_remaining' => $budget->total_remaining,
            'spent_percentage' => $spentPercentage,
            'time_elapsed_percentage' => $timeElapsedPercentage,
            'burn_rate_efficiency' => $burnRateEfficiency,
            'is_on_track' => $burnRateEfficiency <= 100,
            'days_remaining' => $budget->days_remaining,
            'projected_overspend' => $this->calculateProjectedOverspend($budget),
            'status' => $this->getBudgetHealthStatus($spentPercentage, $timeElapsedPercentage),
        ];
    }

    protected function getCategoryPerformance(Budget $budget): array
    {
        return $budget->budgetCategories->map(function ($budgetCategory) {
            $category = $budgetCategory->category;

            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_color' => $category->color,
                'allocated_amount' => $budgetCategory->allocated_amount,
                'spent_amount' => $budgetCategory->spent_amount,
                'remaining_amount' => $budgetCategory->remaining_amount,
                'percentage_used' => $budgetCategory->percentage_used,
                'variance' => $budgetCategory->variance,
                'variance_percentage' => $budgetCategory->variance_percentage,
                'is_over_budget' => $budgetCategory->is_over_budget,
                'daily_burn_rate' => $budgetCategory->getDailyBurnRate(),
                'projected_spend' => $budgetCategory->getProjectedSpend(),
                'is_on_track' => $budgetCategory->isOnTrack(),
                'status' => $budgetCategory->alert_status,
                'formatted_allocated' => $budgetCategory->formatted_allocated_amount,
                'formatted_spent' => $budgetCategory->formatted_spent_amount,
                'formatted_remaining' => $budgetCategory->formatted_remaining_amount,
                'formatted_variance' => $budgetCategory->formatted_variance,
            ];
        })->toArray();
    }

    protected function getBudgetTrends(Budget $budget): array
    {
        $trends = [];
        $periodDays = min(30, $budget->duration_in_days); // Look at last 30 days or budget duration

        for ($i = $periodDays - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            if ($date->lt($budget->start_date) || $date->gt($budget->end_date)) {
                continue;
            }

            $dailySpend = Transaction::where('type', 'expense')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $date)
                ->whereIn('category_id', $budget->budgetCategories->pluck('category_id'))
                ->sum('amount');

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'date_formatted' => $date->format('M j'),
                'daily_spend' => $dailySpend,
                'formatted_spend' => '$'.number_format($dailySpend, 2),
            ];
        }

        return $trends;
    }

    protected function getBudgetProjections(Budget $budget): array
    {
        $currentBurnRate = $this->calculateCurrentBurnRate($budget);
        $daysRemaining = $budget->days_remaining;
        $projectedTotalSpend = $budget->total_spent + ($currentBurnRate * $daysRemaining);

        return [
            'current_burn_rate' => $currentBurnRate,
            'formatted_burn_rate' => '$'.number_format($currentBurnRate, 2).'/day',
            'projected_total_spend' => $projectedTotalSpend,
            'formatted_projected_spend' => '$'.number_format($projectedTotalSpend, 2),
            'projected_variance' => $projectedTotalSpend - $budget->total_allocated,
            'formatted_projected_variance' => '$'.number_format(abs($projectedTotalSpend - $budget->total_allocated), 2),
            'will_exceed_budget' => $projectedTotalSpend > $budget->total_allocated,
            'projected_completion_date' => $this->calculateProjectedCompletionDate($budget, $currentBurnRate),
        ];
    }

    protected function calculateCurrentBurnRate(Budget $budget): float
    {
        $daysElapsed = $budget->start_date->diffInDays(min(now(), $budget->end_date)) + 1;

        if ($daysElapsed <= 0) {
            return 0;
        }

        return $budget->total_spent / $daysElapsed;
    }

    protected function calculateProjectedOverspend(Budget $budget): float
    {
        $projectedSpend = $this->getBudgetProjections($budget)['projected_total_spend'];

        return max(0, $projectedSpend - $budget->total_allocated);
    }

    protected function calculateProjectedCompletionDate(Budget $budget, float $burnRate): ?Carbon
    {
        if ($burnRate <= 0 || $budget->total_remaining <= 0) {
            return null;
        }

        $daysToCompletion = $budget->total_remaining / $burnRate;

        return now()->addDays((int) $daysToCompletion);
    }

    protected function getBudgetHealthStatus(float $spentPercentage, float $timeElapsedPercentage): string
    {
        $efficiency = $timeElapsedPercentage > 0 ? ($spentPercentage / $timeElapsedPercentage) : 0;

        if ($spentPercentage >= 100) {
            return 'over_budget';
        } elseif ($efficiency > 1.2) {
            return 'burning_fast';
        } elseif ($efficiency > 1.0) {
            return 'on_pace';
        } elseif ($efficiency > 0.8) {
            return 'under_pace';
        } else {
            return 'well_under_budget';
        }
    }

    public function generateBudgetReport(Budget $budget): array
    {
        $analysis = $this->getBudgetPerformanceAnalysis($budget);

        return [
            'budget' => $budget,
            'analysis' => $analysis,
            'recommendations' => $this->generateRecommendations($budget, $analysis),
            'generated_at' => now(),
        ];
    }

    protected function generateRecommendations(Budget $budget, array $analysis): array
    {
        $recommendations = [];

        $overall = $analysis['overall'];

        // Overall budget recommendations
        if ($overall['status'] === 'over_budget') {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Budget Exceeded',
                'message' => 'This budget has exceeded its allocated amount. Consider revising category allocations or extending the budget.',
                'action' => 'Review and adjust budget allocations immediately.',
            ];
        } elseif ($overall['status'] === 'burning_fast') {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'High Burn Rate',
                'message' => 'Current spending pace is higher than planned. Monitor closely to avoid overspending.',
                'action' => 'Review recent expenses and consider slowing discretionary spending.',
            ];
        } elseif ($overall['status'] === 'well_under_budget') {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Under Budget',
                'message' => 'Spending is well below allocated amounts. This could indicate unused opportunities.',
                'action' => 'Consider reallocating unused funds to growth initiatives.',
            ];
        }

        // Category-specific recommendations
        foreach ($analysis['categories'] as $category) {
            if ($category['is_over_budget']) {
                $recommendations[] = [
                    'type' => 'warning',
                    'title' => 'Category Over Budget',
                    'message' => "'{$category['category_name']}' has exceeded its allocation by ".$category['formatted_variance'],
                    'action' => 'Review expenses in this category and consider budget reallocation.',
                ];
            } elseif (! $category['is_on_track']) {
                $overspend = $category['projected_spend'] - $category['allocated_amount'];
                $recommendations[] = [
                    'type' => 'caution',
                    'title' => 'Category Trending Over',
                    'message' => "'{$category['category_name']}' is projected to exceed budget by $".number_format($overspend, 2),
                    'action' => 'Monitor this category closely and reduce spending if possible.',
                ];
            }
        }

        return $recommendations;
    }

    public function createBudgetFromTemplate(string $templateType, Carbon $startDate, array $customizations = []): array
    {
        $templates = [
            'startup_monthly' => [
                'name' => 'Monthly Startup Budget',
                'categories' => [
                    'Office Expenses' => 500,
                    'Marketing' => 1000,
                    'Software & Tools' => 300,
                    'Professional Services' => 800,
                    'Travel' => 200,
                ],
                'total' => 2800,
            ],
            'small_business_quarterly' => [
                'name' => 'Quarterly Small Business Budget',
                'categories' => [
                    'Office Expenses' => 1500,
                    'Marketing' => 3000,
                    'Software & Tools' => 1200,
                    'Professional Services' => 2500,
                    'Equipment' => 2000,
                    'Travel' => 800,
                    'Utilities' => 900,
                ],
                'total' => 11900,
            ],
            'annual_operations' => [
                'name' => 'Annual Operations Budget',
                'categories' => [
                    'Salaries & Benefits' => 50000,
                    'Office Rent' => 24000,
                    'Marketing' => 15000,
                    'Software & Tools' => 6000,
                    'Equipment' => 10000,
                    'Professional Services' => 8000,
                    'Travel' => 4000,
                    'Utilities' => 3600,
                    'Insurance' => 2400,
                ],
                'total' => 123000,
            ],
        ];

        $template = $templates[$templateType] ?? $templates['startup_monthly'];

        // Apply customizations
        $budgetData = array_merge($template, $customizations);

        return [
            'name' => $budgetData['name'],
            'period_type' => $this->determinePeriodType($templateType),
            'start_date' => $startDate,
            'end_date' => $this->calculateEndDate($startDate, $this->determinePeriodType($templateType)),
            'total_amount' => $budgetData['total'],
            'categories' => $budgetData['categories'],
        ];
    }

    protected function determinePeriodType(string $templateType): string
    {
        if (str_contains($templateType, 'monthly')) {
            return 'monthly';
        } elseif (str_contains($templateType, 'quarterly')) {
            return 'quarterly';
        } elseif (str_contains($templateType, 'annual')) {
            return 'yearly';
        }

        return 'monthly';
    }

    protected function calculateEndDate(Carbon $startDate, string $periodType): Carbon
    {
        return match ($periodType) {
            'monthly' => $startDate->copy()->endOfMonth(),
            'quarterly' => $startDate->copy()->addMonths(3)->subDay(),
            'yearly' => $startDate->copy()->endOfYear(),
            default => $startDate->copy()->endOfMonth(),
        };
    }

    public function getBudgetVarianceReport(Budget $budget): array
    {
        $budget->load(['budgetCategories.category']);

        $categories = $budget->budgetCategories->map(function ($budgetCategory) {
            $variance = $budgetCategory->variance;
            $variancePercentage = $budgetCategory->variance_percentage;

            return [
                'category' => $budgetCategory->category->name,
                'allocated' => $budgetCategory->allocated_amount,
                'spent' => $budgetCategory->spent_amount,
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
                'status' => $this->getVarianceStatus($variancePercentage),
                'formatted_allocated' => $budgetCategory->formatted_allocated_amount,
                'formatted_spent' => $budgetCategory->formatted_spent_amount,
                'formatted_variance' => $budgetCategory->formatted_variance,
            ];
        });

        return [
            'budget_name' => $budget->name,
            'period' => $budget->start_date->format('M j, Y').' - '.$budget->end_date->format('M j, Y'),
            'overall_variance' => $budget->total_spent - $budget->total_allocated,
            'overall_variance_percentage' => $budget->total_allocated > 0 ?
                (($budget->total_spent - $budget->total_allocated) / $budget->total_allocated) * 100 : 0,
            'categories' => $categories->toArray(),
            'summary' => [
                'total_categories' => $categories->count(),
                'over_budget_categories' => $categories->where('variance', '>', 0)->count(),
                'under_budget_categories' => $categories->where('variance', '<', 0)->count(),
                'on_budget_categories' => $categories->where('variance', '=', 0)->count(),
            ],
        ];
    }

    protected function getVarianceStatus(float $variancePercentage): string
    {
        if ($variancePercentage > 10) {
            return 'significantly_over';
        } elseif ($variancePercentage > 0) {
            return 'over';
        } elseif ($variancePercentage < -10) {
            return 'significantly_under';
        } elseif ($variancePercentage < 0) {
            return 'under';
        } else {
            return 'on_target';
        }
    }

    public function forecastNextPeriodBudget(Budget $currentBudget): array
    {
        $currentAnalysis = $this->getBudgetPerformanceAnalysis($currentBudget);
        $categoryData = collect($currentAnalysis['categories']);

        $nextPeriodStart = $currentBudget->end_date->copy()->addDay();
        $nextPeriodEnd = $this->calculateEndDate($nextPeriodStart, $currentBudget->period_type);

        $forecast = [
            'period' => [
                'start' => $nextPeriodStart->format('M j, Y'),
                'end' => $nextPeriodEnd->format('M j, Y'),
                'type' => $currentBudget->period_type,
            ],
            'recommendations' => [],
            'suggested_allocations' => [],
        ];

        // Generate recommendations based on current performance
        foreach ($categoryData as $category) {
            $currentSpend = $category['spent_amount'];
            $projectedSpend = $category['projected_spend'];
            $suggestedAmount = max($projectedSpend * 1.1, $currentSpend); // 10% buffer

            $forecast['suggested_allocations'][] = [
                'category_name' => $category['category_name'],
                'current_allocated' => $category['allocated_amount'],
                'current_spent' => $currentSpend,
                'suggested_allocation' => $suggestedAmount,
                'change_amount' => $suggestedAmount - $category['allocated_amount'],
                'change_percentage' => $category['allocated_amount'] > 0 ?
                    (($suggestedAmount - $category['allocated_amount']) / $category['allocated_amount']) * 100 : 0,
                'reasoning' => $this->getRecommendationReasoning($category),
            ];
        }

        $forecast['suggested_total'] = collect($forecast['suggested_allocations'])->sum('suggested_allocation');
        $forecast['total_change'] = $forecast['suggested_total'] - $currentBudget->total_allocated;

        return $forecast;
    }

    protected function getRecommendationReasoning(array $category): string
    {
        if ($category['is_over_budget']) {
            return 'Increase due to overspending in current period';
        } elseif (! $category['is_on_track']) {
            return 'Increase due to projected overspend based on current trends';
        } elseif ($category['percentage_used'] < 50) {
            return 'Consider reducing allocation due to low usage';
        } else {
            return 'Maintain similar allocation based on current performance';
        }
    }

    public function getBudgetsByTimeframe(string $timeframe = 'current'): Collection
    {
        return match ($timeframe) {
            'current' => Budget::active()->current()->get(),
            'upcoming' => Budget::where('start_date', '>', now())->get(),
            'past' => Budget::where('end_date', '<', now())->get(),
            'all_active' => Budget::active()->get(),
            default => Budget::active()->current()->get(),
        };
    }

    public function compareBudgets(Budget $budget1, Budget $budget2): array
    {
        return [
            'budget_1' => [
                'name' => $budget1->name,
                'period' => $budget1->start_date->format('M Y').' - '.$budget1->end_date->format('M Y'),
                'total_allocated' => $budget1->total_allocated,
                'total_spent' => $budget1->total_spent,
                'spent_percentage' => $budget1->spent_percentage,
            ],
            'budget_2' => [
                'name' => $budget2->name,
                'period' => $budget2->start_date->format('M Y').' - '.$budget2->end_date->format('M Y'),
                'total_allocated' => $budget2->total_allocated,
                'total_spent' => $budget2->total_spent,
                'spent_percentage' => $budget2->spent_percentage,
            ],
            'comparison' => [
                'allocation_difference' => $budget2->total_allocated - $budget1->total_allocated,
                'spending_difference' => $budget2->total_spent - $budget1->total_spent,
                'efficiency_difference' => $budget2->spent_percentage - $budget1->spent_percentage,
            ],
        ];
    }
}




