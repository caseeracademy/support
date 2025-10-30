<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $incomeCategories = [
            [
                'name' => 'Course Sales',
                'slug' => 'course-sales',
                'description' => 'Revenue from course enrollments and sales',
                'type' => 'income',
                'color' => '#10B981',
            ],
            [
                'name' => 'Consulting Services',
                'slug' => 'consulting-services',
                'description' => 'Income from consulting and professional services',
                'type' => 'income',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Affiliate Commissions',
                'slug' => 'affiliate-commissions',
                'description' => 'Commissions from affiliate partnerships',
                'type' => 'income',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Subscription Revenue',
                'slug' => 'subscription-revenue',
                'description' => 'Recurring revenue from subscriptions',
                'type' => 'income',
                'color' => '#06B6D4',
            ],
            [
                'name' => 'Coaching Sessions',
                'slug' => 'coaching-sessions',
                'description' => 'Income from one-on-one coaching sessions',
                'type' => 'income',
                'color' => '#F59E0B',
            ],
        ];

        $expenseCategories = [
            [
                'name' => 'Marketing & Advertising',
                'slug' => 'marketing-advertising',
                'description' => 'Costs for marketing campaigns and advertising',
                'type' => 'expense',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Software & Tools',
                'slug' => 'software-tools',
                'description' => 'Subscriptions and licenses for business software',
                'type' => 'expense',
                'color' => '#F97316',
            ],
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'description' => 'Equipment and supplies for office operations',
                'type' => 'expense',
                'color' => '#84CC16',
            ],
            [
                'name' => 'Professional Services',
                'slug' => 'professional-services',
                'description' => 'Legal, accounting, and other professional fees',
                'type' => 'expense',
                'color' => '#6366F1',
            ],
            [
                'name' => 'Travel & Entertainment',
                'slug' => 'travel-entertainment',
                'description' => 'Business travel and client entertainment expenses',
                'type' => 'expense',
                'color' => '#EC4899',
            ],
            [
                'name' => 'Hosting & Infrastructure',
                'slug' => 'hosting-infrastructure',
                'description' => 'Web hosting, cloud services, and technical infrastructure',
                'type' => 'expense',
                'color' => '#14B8A6',
            ],
            [
                'name' => 'Content Creation',
                'slug' => 'content-creation',
                'description' => 'Costs for creating courses, videos, and educational content',
                'type' => 'expense',
                'color' => '#F472B6',
            ],
        ];

        foreach ($incomeCategories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true]
            );
        }

        foreach ($expenseCategories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true]
            );
        }
    }
}
