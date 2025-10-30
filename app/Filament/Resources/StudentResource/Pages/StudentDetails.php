<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Services\CaseerAcademyService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class StudentDetails extends Page
{
    protected static string $resource = StudentResource::class;

    protected static string $view = 'filament.pages.student-details';

    public ?array $student = null;

    public bool $loading = true;

    public ?string $error = null;

    public function mount($record): void
    {
        $this->loading = true;

        try {
            $service = new CaseerAcademyService;
            $result = $service->getStudentDetails($record);

            if ($result['success']) {
                $this->student = $result['student'];
                $this->error = null;
            } else {
                $this->error = $result['error'];
                $this->showApiErrorNotification($result, 'Failed to load student details');
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to load student details: '.$e->getMessage();
            $this->showApiErrorNotification([
                'error' => $e->getMessage(),
                'is_auth_error' => false,
            ], 'Failed to load student details');
        }

        $this->loading = false;
    }

    public function getTitle(): string
    {
        return $this->student['display_name'] ?? 'Student Details';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Students')
                ->icon('heroicon-o-arrow-left')
                ->url(StudentResource::getUrl('index'))
                ->color('gray'),

            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->mount($this->student['id'] ?? null);
                })
                ->color('info'),
        ];
    }

    /**
     * Display API error notification with "Go to Settings" action
     */
    private function showApiErrorNotification(array $result, ?string $title = null): void
    {
        $isAuthError = $result['is_auth_error'] ?? false;
        $error = $result['error'] ?? 'Unknown error occurred';

        $notification = Notification::make()
            ->title($title ?? ($isAuthError ? '🔒 API Authentication Error' : '⚠️ API Connection Error'))
            ->body($isAuthError
                ? "**Authentication failed!** Please check your API Secret Key in Settings.\n\n**Error Details:** {$error}"
                : "**Unable to connect to Caseer Academy API.**\n\n**Error Details:** {$error}\n\n**Solution:** Check your API credentials in the Settings page."
            )
            ->danger()
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('go_to_settings')
                    ->label('Go to Settings')
                    ->url(route('filament.admin.pages.settings'))
                    ->button()
                    ->color('primary')
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);

        $notification->send();
    }
}
