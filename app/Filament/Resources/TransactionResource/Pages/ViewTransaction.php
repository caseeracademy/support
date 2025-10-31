<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class ViewTransaction extends Page
{
    protected static string $resource = TransactionResource::class;

    protected static string $view = 'filament.pages.transaction-details';

    public ?Transaction $transaction = null;

    public bool $loading = true;

    public ?string $error = null;

    public function mount($record): void
    {
        $this->loading = true;

        try {
            $this->transaction = Transaction::with([
                'category',
                'paymentMethod',
                'transactionable',
                'createdBy',
                'approvedBy',
            ])->findOrFail($record);
            $this->error = null;
        } catch (\Exception $e) {
            $this->error = 'Failed to load transaction: '.$e->getMessage();
        }

        $this->loading = false;
    }

    public function getTitle(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Transactions')
                ->icon('heroicon-o-arrow-left')
                ->url(TransactionResource::getUrl('index'))
                ->color('gray'),

            Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => TransactionResource::getUrl('edit', ['record' => $this->transaction->id]))
                ->color('primary')
                ->visible(fn (): bool => $this->transaction !== null),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->transaction && $this->transaction->status === 'pending')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->transaction->approve(auth()->user());
                    $this->mount($this->transaction->id);
                }),

            Actions\Action::make('delete')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->transaction->delete();
                    redirect(TransactionResource::getUrl('index'));
                })
                ->visible(fn (): bool => $this->transaction !== null),
        ];
    }
}
