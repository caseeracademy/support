<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Cash',
                'slug' => 'cash',
                'description' => 'Physical cash payments',
                'type' => 'cash',
                'metadata' => null,
            ],
            [
                'name' => 'Business Bank Account',
                'slug' => 'business-bank-account',
                'description' => 'Main business checking account',
                'type' => 'bank_transfer',
                'metadata' => [
                    'account_type' => 'checking',
                    'institution' => 'Business Bank',
                ],
            ],
            [
                'name' => 'PayPal Business',
                'slug' => 'paypal-business',
                'description' => 'PayPal business account for online payments',
                'type' => 'digital_wallet',
                'metadata' => [
                    'provider' => 'PayPal',
                    'account_type' => 'business',
                ],
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'description' => 'Stripe payment processor for credit cards',
                'type' => 'card',
                'metadata' => [
                    'provider' => 'Stripe',
                    'processor_type' => 'gateway',
                ],
            ],
            [
                'name' => 'Square',
                'slug' => 'square',
                'description' => 'Square payment system for in-person transactions',
                'type' => 'card',
                'metadata' => [
                    'provider' => 'Square',
                    'processor_type' => 'terminal',
                ],
            ],
            [
                'name' => 'Wise Business',
                'slug' => 'wise-business',
                'description' => 'Wise (formerly TransferWise) for international payments',
                'type' => 'bank_transfer',
                'metadata' => [
                    'provider' => 'Wise',
                    'supports_multi_currency' => true,
                ],
            ],
            [
                'name' => 'Business Credit Card',
                'slug' => 'business-credit-card',
                'description' => 'Primary business credit card',
                'type' => 'card',
                'metadata' => [
                    'card_type' => 'credit',
                    'usage' => 'business_expenses',
                ],
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::firstOrCreate(
                ['slug' => $method['slug']],
                $method + ['is_active' => true]
            );
        }
    }
}
