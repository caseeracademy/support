<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class InvoicePdfService
{
    protected array $companyInfo = [
        'name' => 'Caseer Academy',
        'address' => '123 Education Street',
        'city' => 'Knowledge City',
        'postal_code' => '12345',
        'country' => 'United States',
        'phone' => '+1 (555) 123-4567',
        'email' => 'billing@caseer.academy',
        'website' => 'https://caseer.academy',
        'tax_id' => 'TAX-123456789',
    ];

    public function generatePdf(Invoice $invoice, string $template = 'standard'): string
    {
        $data = $this->prepareInvoiceData($invoice);

        $pdf = Pdf::view("invoices.templates.{$template}", $data)
            ->format('a4')
            ->margins(15, 15, 15, 15)
            ->headerView('invoices.header', $data)
            ->footerView('invoices.footer', $data);

        // Generate filename
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = "invoices/{$invoice->id}/{$filename}";

        // Save to storage
        Storage::put($path, $pdf->output());

        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    public function downloadPdf(Invoice $invoice, string $template = 'standard'): \Symfony\Component\HttpFoundation\Response
    {
        $data = $this->prepareInvoiceData($invoice);

        $pdf = Pdf::view("invoices.templates.{$template}", $data)
            ->format('a4')
            ->margins(15, 15, 15, 15)
            ->headerView('invoices.header', $data)
            ->footerView('invoices.footer', $data);

        $filename = "invoice-{$invoice->invoice_number}.pdf";

        return $pdf->download($filename);
    }

    public function streamPdf(Invoice $invoice, string $template = 'standard'): \Symfony\Component\HttpFoundation\Response
    {
        $data = $this->prepareInvoiceData($invoice);

        $pdf = Pdf::view("invoices.templates.{$template}", $data)
            ->format('a4')
            ->margins(15, 15, 15, 15)
            ->headerView('invoices.header', $data)
            ->footerView('invoices.footer', $data);

        return $pdf->stream();
    }

    protected function prepareInvoiceData(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'company' => $this->companyInfo,
            'customer' => $invoice->customer,
            'ticket' => $invoice->ticket,
            'items' => $this->prepareInvoiceItems($invoice),
            'totals' => $this->calculateTotals($invoice),
            'payment_info' => $this->getPaymentInformation(),
            'terms' => $this->getPaymentTerms(),
            'notes' => $this->formatNotes($invoice->notes),
            'generated_at' => now(),
        ];
    }

    protected function prepareInvoiceItems(Invoice $invoice): array
    {
        $items = $invoice->items ?? [];

        return collect($items)->map(function ($item, $index) {
            return [
                'index' => $index + 1,
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'total' => (float) ($item['total'] ?? ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0)),
                'formatted_unit_price' => '$'.number_format((float) ($item['unit_price'] ?? 0), 2),
                'formatted_total' => '$'.number_format((float) ($item['total'] ?? 0), 2),
            ];
        })->toArray();
    }

    protected function calculateTotals(Invoice $invoice): array
    {
        return [
            'subtotal' => [
                'amount' => $invoice->subtotal,
                'formatted' => '$'.number_format($invoice->subtotal, 2),
            ],
            'tax' => [
                'rate' => $invoice->tax_rate,
                'amount' => $invoice->tax_amount,
                'formatted' => '$'.number_format($invoice->tax_amount, 2),
                'label' => $invoice->tax_rate > 0 ? "Tax ({$invoice->tax_rate}%)" : 'Tax',
            ],
            'discount' => [
                'amount' => $invoice->discount_amount,
                'formatted' => '$'.number_format($invoice->discount_amount, 2),
                'has_discount' => $invoice->discount_amount > 0,
            ],
            'total' => [
                'amount' => $invoice->total_amount,
                'formatted' => '$'.number_format($invoice->total_amount, 2),
            ],
            'paid' => [
                'amount' => $invoice->paid_amount,
                'formatted' => '$'.number_format($invoice->paid_amount, 2),
            ],
            'remaining' => [
                'amount' => $invoice->remaining_amount,
                'formatted' => '$'.number_format($invoice->remaining_amount, 2),
                'has_balance' => $invoice->remaining_amount > 0,
            ],
            'currency' => $invoice->currency,
        ];
    }

    protected function getPaymentInformation(): array
    {
        return [
            'methods' => [
                'Bank Transfer' => [
                    'bank_name' => 'First National Bank',
                    'account_name' => 'Caseer Academy LLC',
                    'account_number' => '1234567890',
                    'routing_number' => '123456789',
                    'swift_code' => 'FNBNUS33',
                ],
                'Online Payment' => [
                    'url' => 'https://pay.caseer.academy',
                    'instructions' => 'Click the link below to pay online with credit card or PayPal',
                ],
                'Check' => [
                    'payable_to' => 'Caseer Academy LLC',
                    'address' => '123 Education Street, Knowledge City, 12345',
                ],
            ],
            'preferred_method' => 'Online Payment',
        ];
    }

    protected function getPaymentTerms(): array
    {
        return [
            'net_days' => 30,
            'late_fee_rate' => 1.5,
            'late_fee_description' => 'A late fee of 1.5% per month will be applied to overdue balances.',
            'terms' => [
                'Payment is due within 30 days of invoice date.',
                'Late payments may incur additional fees.',
                'All payments should reference the invoice number.',
                'For questions about this invoice, please contact our billing department.',
            ],
        ];
    }

    protected function formatNotes(?string $notes): array
    {
        if (empty($notes)) {
            return [];
        }

        return array_filter(explode("\n", $notes));
    }

    public function createInvoiceTemplate(string $templateName, array $customization = []): void
    {
        $templatePath = resource_path("views/invoices/templates/{$templateName}.blade.php");
        $headerPath = resource_path('views/invoices/header.blade.php');
        $footerPath = resource_path('views/invoices/footer.blade.php');

        // Create directories if they don't exist
        if (! is_dir(dirname($templatePath))) {
            mkdir(dirname($templatePath), 0755, true);
        }

        // Create the main template
        $this->createMainTemplate($templatePath, $customization);

        // Create header template if it doesn't exist
        if (! file_exists($headerPath)) {
            $this->createHeaderTemplate($headerPath);
        }

        // Create footer template if it doesn't exist
        if (! file_exists($footerPath)) {
            $this->createFooterTemplate($footerPath);
        }
    }

    protected function createMainTemplate(string $path, array $customization): void
    {
        $template = view('invoices.templates.base', [
            'customization' => $customization,
            'company' => $this->companyInfo,
        ])->render();

        file_put_contents($path, $template);
    }

    protected function createHeaderTemplate(string $path): void
    {
        $header = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #4F46E5; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #4F46E5; }
        .company-details { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-details">
            {{ $company['address'] }}, {{ $company['city'] }} {{ $company['postal_code'] }}<br>
            Phone: {{ $company['phone'] }} | Email: {{ $company['email'] }}
        </div>
    </div>
HTML;

        file_put_contents($path, $header);
    }

    protected function createFooterTemplate(string $path): void
    {
        $footer = <<<'HTML'
    <div style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
        <p>{{ $company['name'] }} | {{ $company['email'] }} | {{ $company['website'] }}</p>
        <p>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
HTML;

        file_put_contents($path, $footer);
    }

    public function getTemplate(string $templateName): string
    {
        $templatePath = resource_path("views/invoices/templates/{$templateName}.blade.php");

        if (! file_exists($templatePath)) {
            // Create default template if it doesn't exist
            $this->createInvoiceTemplate($templateName);
        }

        return $templatePath;
    }

    public function getAvailableTemplates(): array
    {
        $templatesPath = resource_path('views/invoices/templates');

        if (! is_dir($templatesPath)) {
            return ['standard'];
        }

        $templates = [];
        foreach (glob($templatesPath.'/*.blade.php') as $file) {
            $templates[] = basename($file, '.blade.php');
        }

        return empty($templates) ? ['standard'] : $templates;
    }

    public function setCompanyInfo(array $companyInfo): void
    {
        $this->companyInfo = array_merge($this->companyInfo, $companyInfo);
    }

    public function getCompanyInfo(): array
    {
        return $this->companyInfo;
    }
}

