<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Services\CaseerAcademyService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = \App\Models\Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Student Management';

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Note: This table will be populated via custom page
        // since we're fetching from external API
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_date')
                    ->label('Registered')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Model $record): string => static::getUrl('details', ['record' => $record->id])),

                Tables\Actions\Action::make('change_password')
                    ->label('Change Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form(function ($record) {
                        // Extract data from record without triggering database query
                        $email = is_array($record) ? $record['email'] : $record->email;
                        $name = is_array($record) ? $record['display_name'] : $record->display_name;

                        return [
                            Forms\Components\Placeholder::make('student_info')
                                ->label('Student')
                                ->content("{$name} ({$email})"),
                            Forms\Components\Hidden::make('student_email')
                                ->default($email),
                            Forms\Components\TextInput::make('new_password')
                                ->label('New Password')
                                ->password()
                                ->required()
                                ->minLength(8),
                            Forms\Components\TextInput::make('confirm_password')
                                ->label('Confirm Password')
                                ->password()
                                ->required()
                                ->same('new_password'),
                        ];
                    })
                    ->action(function (array $data): void {
                        $service = new CaseerAcademyService;
                        $result = $service->resetPasswordByEmail($data['student_email'], $data['new_password']);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Password changed successfully')
                                ->body("Password updated for {$data['student_email']}")
                                ->success()
                                ->duration(10000)
                                ->send();
                        } else {
                            static::showApiErrorNotification($result, 'Failed to change password');
                        }
                    }),

                Tables\Actions\Action::make('send_whatsapp')
                    ->label('Send WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(fn (Model $record): string => 'https://wa.me/'.($record->phone ?? ''))
                    ->openUrlInNewTab()
                    ->visible(fn (Model $record): bool => ! empty($record->phone)),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'details' => Pages\StudentDetails::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Will use custom action instead
    }

    /**
     * Display API error notification with "Go to Settings" action
     */
    private static function showApiErrorNotification(array $result, ?string $title = null): void
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
