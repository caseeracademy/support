<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Services\CaseerAcademyService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CreateStudent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = StudentResource::class;

    protected static string $view = 'filament.pages.create-student';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $service = new CaseerAcademyService;
        $result = $service->createStudent($data);

        if ($result['success']) {
            Notification::make()
                ->title('Student created successfully')
                ->body("Student ID: {$result['student_id']}")
                ->success()
                ->send();

            $this->redirect(StudentResource::getUrl('index'));
        } else {
            $this->showApiErrorNotification($result, 'Failed to create student');
        }
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
