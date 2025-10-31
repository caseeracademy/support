<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromCollection, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Carbon $startDate;

    protected Carbon $endDate;

    protected ?string $type;

    protected ?int $categoryId;

    protected ?int $paymentMethodId;

    public function __construct(
        Carbon $startDate,
        Carbon $endDate,
        ?string $type = null,
        ?int $categoryId = null,
        ?int $paymentMethodId = null
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->categoryId = $categoryId;
        $this->paymentMethodId = $paymentMethodId;
    }

    public function collection()
    {
        $query = Transaction::with(['category', 'paymentMethod', 'createdBy', 'transactionable'])
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate]);

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->paymentMethodId) {
            $query->where('payment_method_id', $this->paymentMethodId);
        }

        return $query->orderBy('transaction_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Reference Number',
            'Date',
            'Type',
            'Title',
            'Description',
            'Amount',
            'Currency',
            'Category',
            'Payment Method',
            'Status',
            'Related To',
            'Created By',
            'Processed At',
            'Notes',
        ];
    }

    public function map($transaction): array
    {
        $relatedTo = '';
        if ($transaction->transactionable) {
            if ($transaction->transactionable_type === 'App\\Models\\Customer') {
                $relatedTo = 'Customer: '.$transaction->transactionable->name;
            } elseif ($transaction->transactionable_type === 'App\\Models\\Ticket') {
                $relatedTo = 'Ticket #'.$transaction->transactionable->id.': '.$transaction->transactionable->subject;
            }
        }

        return [
            $transaction->reference_number,
            $transaction->transaction_date->format('Y-m-d'),
            ucfirst($transaction->type),
            $transaction->title,
            $transaction->description ?: '',
            $transaction->amount,
            $transaction->currency,
            $transaction->category?->name ?? 'Uncategorized',
            $transaction->paymentMethod?->name ?? 'Unknown',
            ucfirst($transaction->status),
            $relatedTo,
            $transaction->createdBy?->name ?? 'System',
            $transaction->processed_at?->format('Y-m-d H:i:s') ?? '',
            $transaction->metadata ? json_encode($transaction->metadata) : '',
        ];
    }

    public function startCell(): string
    {
        return 'A6'; // Start data after header info
    }

    public function title(): string
    {
        return 'Transaction Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header information styling
            'A1:N5' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA'],
                ],
            ],

            // Main headers styling
            6 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // Data styling
            'A7:N'.($this->collection()->count() + 6) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ],

            // Amount column formatting
            'F:F' => [
                'numberFormat' => [
                    'formatCode' => '$#,##0.00',
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Add header information
                $sheet->setCellValue('A1', 'Transaction Report');
                $sheet->setCellValue('A2', 'Generated: '.now()->format('F j, Y g:i A'));
                $sheet->setCellValue('A3', 'Period: '.$this->startDate->format('F j, Y').' - '.$this->endDate->format('F j, Y'));

                $filters = [];
                if ($this->type) {
                    $filters[] = 'Type: '.ucfirst($this->type);
                }
                if ($this->categoryId) {
                    $category = \App\Models\Category::find($this->categoryId);
                    $filters[] = 'Category: '.($category?->name ?? 'Unknown');
                }
                if ($this->paymentMethodId) {
                    $paymentMethod = \App\Models\PaymentMethod::find($this->paymentMethodId);
                    $filters[] = 'Payment Method: '.($paymentMethod?->name ?? 'Unknown');
                }

                if (! empty($filters)) {
                    $sheet->setCellValue('A4', 'Filters: '.implode(', ', $filters));
                }

                // Calculate and add summary
                $transactions = $this->collection();
                $totalAmount = $transactions->sum('amount');
                $incomeAmount = $transactions->where('type', 'income')->sum('amount');
                $expenseAmount = $transactions->where('type', 'expense')->sum('amount');

                $lastRow = $transactions->count() + 7;
                $sheet->setCellValue('A'.$lastRow, 'SUMMARY');
                $sheet->setCellValue('A'.($lastRow + 1), 'Total Transactions: '.$transactions->count());
                $sheet->setCellValue('A'.($lastRow + 2), 'Total Income: $'.number_format($incomeAmount, 2));
                $sheet->setCellValue('A'.($lastRow + 3), 'Total Expenses: $'.number_format($expenseAmount, 2));
                $sheet->setCellValue('A'.($lastRow + 4), 'Net Amount: $'.number_format($incomeAmount - $expenseAmount, 2));

                // Style the summary section
                $sheet->getStyle('A'.$lastRow.':A'.($lastRow + 4))->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Auto-size columns
                foreach (range('A', 'N') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Set minimum column widths
                $sheet->getColumnDimension('D')->setWidth(30); // Title
                $sheet->getColumnDimension('E')->setWidth(40); // Description
                $sheet->getColumnDimension('K')->setWidth(25); // Related To

                // Freeze the header row
                $sheet->freezePane('A7');

                // Add conditional formatting for transaction types
                $dataRange = 'C7:C'.($transactions->count() + 6);
                $conditionalStyles = $sheet->getStyle($dataRange)->getConditionalStyles();

                // Income rows - green background
                $incomeCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional;
                $incomeCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CONTAINSTEXT);
                $incomeCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_CONTAINSTEXT);
                $incomeCondition->setText('Income');
                $incomeCondition->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
                $incomeCondition->getStyle()->getFill()->getStartColor()->setRGB('DCFCE7');

                // Expense rows - red background
                $expenseCondition = new \PhpOffice\PhpSpreadsheet\Style\Conditional;
                $expenseCondition->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CONTAINSTEXT);
                $expenseCondition->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_CONTAINSTEXT);
                $expenseCondition->setText('Expense');
                $expenseCondition->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
                $expenseCondition->getStyle()->getFill()->getStartColor()->setRGB('FEE2E2');

                $conditionalStyles[] = $incomeCondition;
                $conditionalStyles[] = $expenseCondition;
                $sheet->getStyle($dataRange)->setConditionalStyles($conditionalStyles);
            },
        ];
    }
}

