<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_invoice')
                ->label('Generate Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(fn () => $this->record->total_amount && ! $this->record->invoices()->exists())
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('Invoice Title')
                        ->default(fn () => "Support Services - Ticket #{$this->record->id}")
                        ->required(),

                    Forms\Components\Textarea::make('description')
                        ->label('Invoice Description')
                        ->default(fn () => $this->record->subject)
                        ->maxLength(65535),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date')
                        ->default(now()->addDays(30))
                        ->required(),

                    Forms\Components\TextInput::make('tax_rate')
                        ->label('Tax Rate (%)')
                        ->numeric()
                        ->default(0)
                        ->step(0.01)
                        ->suffix('%'),
                ])
                ->action(function (array $data): void {
                    $invoice = $this->record->generateInvoice($data);

                    Notification::make()
                        ->title('Invoice Generated')
                        ->body("Invoice {$invoice->invoice_number} created successfully")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View Invoice')
                                ->url("/admin/invoices/{$invoice->id}/edit")
                                ->button(),
                        ])
                        ->send();
                }),

            Actions\Action::make('record_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->visible(fn () => $this->record->payment_status !== 'paid' && $this->record->total_amount)
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Payment Amount')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->required()
                        ->default(fn () => $this->record->remaining_amount),

                    Forms\Components\Select::make('payment_method_id')
                        ->label('Payment Method')
                        ->relationship('paymentMethod', 'name')
                        ->required()
                        ->searchable(),

                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->placeholder('Transaction ID or reference number'),

                    Forms\Components\Toggle::make('create_transaction')
                        ->label('Create Transaction Record')
                        ->default(true)
                        ->helperText('Automatically create a transaction record for this payment'),
                ])
                ->action(function (array $data): void {
                    $this->record->addPayment($data['amount'], Auth::user());

                    if ($data['create_transaction'] ?? true) {
                        $this->record->createTransactionFromPayment($data['amount'], [
                            'payment_method_id' => $data['payment_method_id'],
                            'transaction_date' => $data['payment_date'],
                            'external_reference' => $data['payment_reference'] ?? null,
                        ]);
                    }

                    Notification::make()
                        ->title('Payment Recorded')
                        ->body('Payment of $'.number_format($data['amount'], 2).' recorded successfully')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
