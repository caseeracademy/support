<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketNote;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class TicketDetails extends Page
{
    use WithFileUploads;

    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament.pages.ticket-details';

    public ?Ticket $ticket = null;

    public bool $loading = true;

    public ?string $error = null;

    public string $noteInput = '';

    public $newAttachment;

    public function mount($record): void
    {
        $this->loading = true;

        try {
            $this->ticket = Ticket::with(['customer', 'assignedTo', 'notes.user', 'attachments.uploadedBy', 'transactions'])
                ->findOrFail($record);
            $this->error = null;
        } catch (\Exception $e) {
            $this->error = 'Failed to load ticket: '.$e->getMessage();
        }

        $this->loading = false;
    }

    public function getTitle(): string
    {
        return ''; // Remove the title text before buttons
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Tickets')
                ->icon('heroicon-o-arrow-left')
                ->url(TicketResource::getUrl('index'))
                ->color('gray'),

            \Filament\Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => TicketResource::getUrl('edit', ['record' => $this->ticket->id]))
                ->color('primary')
                ->visible(fn (): bool => $this->ticket !== null),

            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->mount($this->ticket->id ?? null);
                    Notification::make()
                        ->title('Refreshed')
                        ->success()
                        ->send();
                })
                ->color('info')
                ->visible(fn (): bool => $this->ticket !== null),

            // Only one button: Mark as Resolved
            \Filament\Actions\Action::make('mark_resolved')
                ->label('Mark as Resolved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(fn () => $this->updateStatus('resolved'))
                ->visible(fn () => $this->ticket && $this->ticket->status !== 'resolved')
                ->requiresConfirmation(),
        ];
    }

    public function updateStatus(string $status): void
    {
        if (! $this->ticket) {
            return;
        }

        $this->ticket->update(['status' => $status]);

        // Auto-create a note for status change
        TicketNote::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'note' => 'Status changed to: '.ucfirst($status),
            'is_internal' => true,
        ]);

        $this->mount($this->ticket->id);

        Notification::make()
            ->title('Status Updated')
            ->body('Ticket status changed to '.ucfirst($status))
            ->success()
            ->send();
    }

    public function addNote(): void
    {
        if (! $this->ticket || empty($this->noteInput)) {
            return;
        }

        TicketNote::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'note' => $this->noteInput,
            'is_internal' => true,
        ]);

        // Clear the input
        $this->noteInput = '';

        // Refresh ticket data
        $this->mount($this->ticket->id);

        Notification::make()
            ->title('Note Added')
            ->success()
            ->send();
    }

    public function updatedNewAttachment(): void
    {
        if (! $this->newAttachment || ! $this->ticket) {
            return;
        }

        $this->validate([
            'newAttachment' => 'file|max:10240', // 10MB Max
        ]);

        $filename = uniqid().'_'.$this->newAttachment->getClientOriginalName();
        $filePath = $this->newAttachment->storeAs('ticket-attachments', $filename, 'public');

        TicketAttachment::create([
            'ticket_id' => $this->ticket->id,
            'uploaded_by' => Auth::id(),
            'filename' => $filename,
            'original_filename' => $this->newAttachment->getClientOriginalName(),
            'mime_type' => $this->newAttachment->getMimeType(),
            'file_size' => $this->newAttachment->getSize(),
            'file_path' => $filePath,
        ]);

        $this->newAttachment = null;
        $this->mount($this->ticket->id);

        Notification::make()
            ->title('File Uploaded')
            ->success()
            ->send();
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $attachment = TicketAttachment::find($attachmentId);

        if ($attachment && $attachment->ticket_id === $this->ticket->id) {
            // Delete file from storage
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }

            $attachment->delete();

            $this->mount($this->ticket->id);

            Notification::make()
                ->title('Attachment Deleted')
                ->success()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save Changes')
                ->action('save'),
        ];
    }
}
