<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected ?bool $createUserAccount = null;

    protected ?string $username = null;

    protected ?string $password = null;

    protected ?int $roleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Read form state BEFORE removing fields to get dehydrated=false fields
        // Use getRawState() which includes all fields regardless of dehydrated
        $rawState = $this->form->getRawState();

        // Store user-related data temporarily
        $this->createUserAccount = $rawState['create_user_account'] ?? false;
        $this->username = $rawState['username'] ?? null;
        $this->password = $rawState['password'] ?? null;
        $this->roleId = $rawState['role_id'] ?? null;

        // Remove user-related fields that shouldn't be saved to employee
        unset($data['create_user_account'], $data['username'], $data['password'], $data['password_confirmation'], $data['role_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create user account if requested
        if ($this->createUserAccount && $this->username && $this->password && $this->roleId) {
            $this->handleUserAccountCreation($this->record, $this->username, $this->password, $this->roleId);
        } else {
            // Log what's missing for debugging
            \Log::info('User account not created', [
                'createUserAccount' => $this->createUserAccount,
                'hasUsername' => ! empty($this->username),
                'hasPassword' => ! empty($this->password),
                'hasRoleId' => ! empty($this->roleId),
            ]);
        }
    }

    protected function handleUserAccountCreation(Model $employee, string $username, string $password, int $roleId): void
    {
        try {
            // Create user account
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $username.'@caseer.academy',
                'password' => $password,
            ]);

            // Assign role
            $user->assignRole(Role::find($roleId));

            // Link employee to user
            $employee->update(['user_id' => $user->id]);

            Notification::make()
                ->title('Login Account Created')
                ->body("User account created for {$employee->full_name} with email: {$user->email}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Create Login Account')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
