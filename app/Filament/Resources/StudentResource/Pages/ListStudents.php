<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Services\CaseerAcademyService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\Action::make('search_users')
                ->label('Search Users')
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('search_term')
                        ->label('Search Term')
                        ->placeholder('Enter name, email, or username...')
                        ->required()
                        ->minLength(2),
                ])
                ->action(function (array $data): void {
                    $service = new CaseerAcademyService;
                    $result = $service->searchUsers($data['search_term']);

                    if ($result['success'] && count($result['users']) > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('Search Results')
                            ->body('Found '.count($result['users'])." user(s). Click 'Clear Search' to return to latest students.")
                            ->success()
                            ->duration(10000) // Show for 10 seconds
                            ->send();

                        // Store search results with flag
                        session([
                            'student_search_results' => $result['users'],
                            'student_search_active' => true,
                        ]);
                    } else {
                        if (isset($result['users']) && count($result['users']) === 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('No results found')
                                ->body('No users matched your search')
                                ->warning()
                                ->send();
                        } else {
                            $this->showApiErrorNotification($result, 'Search failed');
                        }
                    }
                }),

            Actions\Action::make('reset_password')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('Student Email')
                        ->email()
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->minLength(8),
                    \Filament\Forms\Components\TextInput::make('confirm_password')
                        ->label('Confirm Password')
                        ->password()
                        ->required()
                        ->same('new_password'),
                ])
                ->action(function (array $data): void {
                    $service = new CaseerAcademyService;
                    $result = $service->resetPasswordByEmail($data['email'], $data['new_password']);

                    if ($result['success']) {
                        \Filament\Notifications\Notification::make()
                            ->title('Password Reset Successful')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        $this->showApiErrorNotification($result, 'Password Reset Failed');
                    }
                }),

            Actions\Action::make('create_student')
                ->label('Create Student')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('username')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('first_name')
                        ->label('First Name')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('last_name')
                        ->label('Last Name')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $service = new CaseerAcademyService;
                    $result = $service->createStudent($data);

                    if ($result['success']) {
                        \Filament\Notifications\Notification::make()
                            ->title('Student created successfully')
                            ->body("Student ID: {$result['student_id']}")
                            ->success()
                            ->send();

                        $this->refreshStudents();
                    } else {
                        $this->showApiErrorNotification($result, 'Failed to create student');
                    }
                }),
        ];

        // Add clear search button if search is active
        if (session('student_search_active')) {
            array_unshift($actions, Actions\Action::make('clear_search')
                ->label('Clear Search')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(function (): void {
                    session()->forget(['student_search_results', 'student_search_active']);
                    \Filament\Notifications\Notification::make()
                        ->title('Search cleared')
                        ->body('Showing latest students')
                        ->info()
                        ->send();
                })
            );
        }

        return $actions;
    }

    public function refreshStudents(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Override to fetch students from API instead of database
     */
    public function getTableRecords(): EloquentCollection
    {
        // Check if we have search results in session (don't clear, keep them!)
        if (session()->has('student_search_results') && session('student_search_active')) {
            $searchResults = session('student_search_results');

            $students = collect($searchResults)->map(function ($studentData) {
                return \App\Models\Student::fromApiData($studentData);
            });

            return new EloquentCollection($students);
        }

        // Otherwise fetch latest students
        $service = new CaseerAcademyService;
        $result = $service->getLatestStudents();

        if ($result['success']) {
            // Convert API data to Student models
            $students = collect($result['students'])->map(function ($studentData) {
                return \App\Models\Student::fromApiData($studentData);
            });

            return new EloquentCollection($students);
        }

        // Show error notification if API fails
        $this->showApiErrorNotification($result, 'Failed to load students');

        // Return empty Eloquent collection if API fails
        return new EloquentCollection([]);
    }

    /**
     * Display API error notification with "Go to Settings" action
     */
    private function showApiErrorNotification(array $result, ?string $title = null): void
    {
        $isAuthError = $result['is_auth_error'] ?? false;
        $error = $result['error'] ?? 'Unknown error occurred';

        $notification = \Filament\Notifications\Notification::make()
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

    /**
     * Override record key since we're not using Eloquent
     */
    public function getTableRecordKey($record): string
    {
        return is_array($record) ? (string) $record['id'] : (string) $record->getKey();
    }

    /**
     * Override to prevent Filament from trying to fetch record from database
     */
    protected function resolveTableRecord(?string $key): ?Model
    {
        if (! $key) {
            return null;
        }

        // Get all current records
        $records = $this->getTableRecords();

        // Find the record by key
        return $records->first(function ($record) use ($key) {
            return $this->getTableRecordKey($record) === $key;
        });
    }

    /**
     * Override getTableRecord to use our custom resolution
     */
    public function getTableRecord(?string $key): ?Model
    {
        return $this->resolveTableRecord($key);
    }

    /**
     * Override to prevent database query - return a dummy query builder
     */
    public function getFilteredTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Return a custom query builder that doesn't hit the database
        // This prevents Filament from trying to query the students table
        return \App\Models\Student::query()->whereRaw('1 = 0'); // Always returns empty, never executes
    }
}
