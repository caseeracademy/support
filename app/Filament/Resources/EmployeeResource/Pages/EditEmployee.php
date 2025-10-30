<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manage_login')
                ->label('Manage Login')
                ->icon('heroicon-o-key')
                ->color('info')
                ->visible(fn () => $this->record->user_id)
                ->url(fn () => "/admin/users/{$this->record->user_id}/edit"),

            Actions\Action::make('create_login')
                ->label('Create Login')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => ! $this->record->user_id)
                ->form([
                    Forms\Components\TextInput::make('username')
                        ->required()
                        ->placeholder('john.doe'),

                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->required()
                        ->minLength(8),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->password()
                        ->required()
                        ->same('password'),

                    Forms\Components\Select::make('role_id')
                        ->label('System Role')
                        ->options(Role::orderBy('sort_order')->pluck('display_name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $user = User::create([
                            'name' => $this->record->full_name,
                            'email' => $data['username'].'@caseer.academy',
                            'password' => $data['password'],
                        ]);

                        $user->assignRole(Role::find($data['role_id']));

                        $this->record->update(['user_id' => $user->id]);

                        Notification::make()
                            ->title('Login Account Created')
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Create Login')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('terminate')
                ->label('Terminate')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'active')
                ->form([
                    Forms\Components\DatePicker::make('termination_date')
                        ->required()
                        ->default(now()),

                    Forms\Components\Textarea::make('reason')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->terminate(\Carbon\Carbon::parse($data['termination_date']), $data['reason']);

                    Notification::make()
                        ->title('Employee Terminated')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove user-related fields - login is managed via header actions
        unset($data['create_user_account'], $data['username'], $data['password'], $data['password_confirmation'], $data['role_id']);

        return $data;
    }
}
