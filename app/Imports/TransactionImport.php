<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class TransactionImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow, WithProgressBar, WithValidation
{
    use Importable;

    protected array $errors = [];

    protected int $successfulImports = 0;

    protected array $categoriesCache = [];

    protected array $paymentMethodsCache = [];

    public function __construct()
    {
        $this->cacheCategories();
        $this->cachePaymentMethods();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row->toArray(), $index);
            } catch (\Exception $e) {
                $this->errors[] = 'Row '.($index + 2).': '.$e->getMessage();
            }
        }
    }

    protected function processRow(array $row, int $index): void
    {
        // Clean and prepare data
        $data = $this->prepareRowData($row, $index);

        // Validate the prepared data
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->errors[] = 'Row '.($index + 2).': '.$error;
            }

            return;
        }

        // Create the transaction
        $transaction = Transaction::create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'transaction_date' => Carbon::parse($data['transaction_date']),
            'category_id' => $this->getCategoryId($data['category']),
            'payment_method_id' => $this->getPaymentMethodId($data['payment_method']),
            'status' => $data['status'] ?? 'completed',
            'processed_at' => now(),
            'created_by' => Auth::id(),
            'external_reference' => $data['external_reference'] ?? null,
            'metadata' => $this->prepareMetadata($data),
        ]);

        $this->successfulImports++;
    }

    protected function prepareRowData(array $row, int $index): array
    {
        return [
            'type' => $this->cleanValue($row['type'] ?? $row['transaction_type'] ?? ''),
            'amount' => $this->parseAmount($row['amount'] ?? '0'),
            'currency' => strtoupper($this->cleanValue($row['currency'] ?? 'USD')),
            'title' => $this->cleanValue($row['title'] ?? $row['description'] ?? ''),
            'description' => $this->cleanValue($row['description'] ?? $row['notes'] ?? ''),
            'transaction_date' => $this->parseDate($row['date'] ?? $row['transaction_date'] ?? now()),
            'category' => $this->cleanValue($row['category'] ?? 'General'),
            'payment_method' => $this->cleanValue($row['payment_method'] ?? 'Cash'),
            'status' => $this->cleanValue($row['status'] ?? 'completed'),
            'external_reference' => $this->cleanValue($row['reference'] ?? $row['external_reference'] ?? ''),
        ];
    }

    protected function prepareMetadata(array $data): array
    {
        return [
            'imported_at' => now()->toDateTimeString(),
            'imported_by' => Auth::user()?->name ?? 'System',
            'source' => 'csv_import',
        ];
    }

    protected function cleanValue(?string $value): string
    {
        return trim($value ?? '');
    }

    protected function parseAmount($value): float
    {
        // Remove currency symbols and clean the value
        $cleaned = preg_replace('/[^\d.-]/', '', (string) $value);

        return (float) $cleaned;
    }

    protected function parseDate($value): Carbon
    {
        if (empty($value)) {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: {$value}");
        }
    }

    protected function getCategoryId(string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $key = strtolower($categoryName);

        if (isset($this->categoriesCache[$key])) {
            return $this->categoriesCache[$key];
        }

        // Try to find existing category
        $category = Category::where('name', 'LIKE', '%'.$categoryName.'%')->first();

        if (! $category) {
            // Create new category if it doesn't exist
            $category = Category::create([
                'name' => $categoryName,
                'type' => 'expense', // Default to expense, user can change later
                'color' => '#3B82F6',
                'is_active' => true,
            ]);
        }

        $this->categoriesCache[$key] = $category->id;

        return $category->id;
    }

    protected function getPaymentMethodId(string $paymentMethodName): ?int
    {
        if (empty($paymentMethodName)) {
            return null;
        }

        $key = strtolower($paymentMethodName);

        if (isset($this->paymentMethodsCache[$key])) {
            return $this->paymentMethodsCache[$key];
        }

        // Try to find existing payment method
        $paymentMethod = PaymentMethod::where('name', 'LIKE', '%'.$paymentMethodName.'%')->first();

        if (! $paymentMethod) {
            // Create new payment method if it doesn't exist
            $paymentMethod = PaymentMethod::create([
                'name' => $paymentMethodName,
                'type' => 'other',
            ]);
        }

        $this->paymentMethodsCache[$key] = $paymentMethod->id;

        return $paymentMethod->id;
    }

    protected function cacheCategories(): void
    {
        Category::all()->each(function ($category) {
            $this->categoriesCache[strtolower($category->name)] = $category->id;
        });
    }

    protected function cachePaymentMethods(): void
    {
        PaymentMethod::all()->each(function ($paymentMethod) {
            $this->paymentMethodsCache[strtolower($paymentMethod->name)] = $paymentMethod->id;
        });
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:3'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'completed', 'cancelled', 'refunded'])],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'type.required' => 'Transaction type is required (income/expense)',
            'type.in' => 'Transaction type must be either "income" or "expense"',
            'amount.required' => 'Amount is required',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Amount must be greater than 0',
            'title.required' => 'Title/description is required',
            'transaction_date.required' => 'Transaction date is required',
            'transaction_date.date' => 'Transaction date must be a valid date',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessfulImports(): int
    {
        return $this->successfulImports;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function getSummary(): array
    {
        return [
            'successful_imports' => $this->successfulImports,
            'errors_count' => count($this->errors),
            'errors' => $this->errors,
            'has_errors' => $this->hasErrors(),
        ];
    }

    // Handle validation failures
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: ".implode(', ', $failure->errors());
        }
    }

    // Provide sample CSV headers for users
    public static function getSampleHeaders(): array
    {
        return [
            'type',
            'amount',
            'currency',
            'title',
            'description',
            'date',
            'category',
            'payment_method',
            'status',
            'reference',
        ];
    }

    public static function getSampleData(): array
    {
        return [
            [
                'type' => 'income',
                'amount' => '1500.00',
                'currency' => 'USD',
                'title' => 'Client Payment - Project ABC',
                'description' => 'Payment received for web development project',
                'date' => '2024-01-15',
                'category' => 'Consulting Revenue',
                'payment_method' => 'Bank Transfer',
                'status' => 'completed',
                'reference' => 'INV-2024-001',
            ],
            [
                'type' => 'expense',
                'amount' => '85.50',
                'currency' => 'USD',
                'title' => 'Office Supplies',
                'description' => 'Pens, paper, and other office materials',
                'date' => '2024-01-16',
                'category' => 'Office Expenses',
                'payment_method' => 'Credit Card',
                'status' => 'completed',
                'reference' => 'REC-2024-102',
            ],
        ];
    }
}

