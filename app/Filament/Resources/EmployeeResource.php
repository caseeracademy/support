<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->description('Employee personal details')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->placeholder('+1 (555) 123-4567'),

                        Forms\Components\DatePicker::make('date_of_birth')
                            ->maxDate(now()->subYears(16)), // Minimum 16 years old

                        Forms\Components\TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generated if left blank'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Employment Details')
                    ->description('Job position and employment terms')
                    ->schema([
                        Forms\Components\DatePicker::make('hire_date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('employment_type')
                            ->options([
                                'full_time' => 'Full Time',
                                'part_time' => 'Part Time',
                                'contract' => 'Contract',
                                'intern' => 'Intern',
                            ])
                            ->required()
                            ->default('full_time'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'on_leave' => 'On Leave',
                                'terminated' => 'Terminated',
                                'suspended' => 'Suspended',
                            ])
                            ->required()
                            ->default('active')
                            ->reactive(),

                        Forms\Components\DatePicker::make('termination_date')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'terminated'),

                        Forms\Components\Select::make('department')
                            ->label('Department')
                            ->options(function () {
                                $departments = Employee::getDepartments();

                                return array_combine($departments, $departments);
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select department'),

                        Forms\Components\Select::make('position')
                            ->label('Position')
                            ->required()
                            ->options(function () {
                                $positions = Employee::getPositions();

                                return array_combine($positions, $positions);
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select position'),

                        Forms\Components\Select::make('reports_to')
                            ->label('Reports To')
                            ->options(function () {
                                return \App\Models\User::whereHas('roles', function ($query) {
                                    $query->where('name', 'admin');
                                })
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [$user->id => "{$user->name} ({$user->email})"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Select admin manager'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Compensation')
                    ->description('Salary and payment details')
                    ->schema([
                        Forms\Components\TextInput::make('base_salary')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->placeholder('50000.00'),

                        Forms\Components\Select::make('salary_currency')
                            ->options([
                                'USD' => 'USD ($)',
                                'EUR' => 'EUR (€)',
                                'GBP' => 'GBP (£)',
                            ])
                            ->default('USD')
                            ->required(),

                        Forms\Components\Select::make('pay_frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-Weekly (Every 2 weeks)',
                                'semimonthly' => 'Semi-Monthly (Twice a month)',
                                'monthly' => 'Monthly',
                            ])
                            ->required()
                            ->default('monthly'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contact & Address')
                    ->description('Contact information and address')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(20),

                        Forms\Components\Select::make('country')
                            ->options([
                                'US' => 'United States',
                                'CA' => 'Canada',
                                'GB' => 'United Kingdom',
                                'AU' => 'Australia',
                            ])
                            ->default('US'),

                        Forms\Components\TextInput::make('emergency_contact_name')
                            ->maxLength(255)
                            ->placeholder('Full name of emergency contact'),

                        Forms\Components\TextInput::make('emergency_contact_phone')
                            ->tel()
                            ->placeholder('+1 (555) 987-6543'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Login Credentials & Role')
                    ->description('System access and permissions for employee')
                    ->schema([
                        Forms\Components\Placeholder::make('existing_account_info')
                            ->label('Account Status')
                            ->content(function (?Employee $record) {
                                if (! $record?->user_id) {
                                    return '❌ No login account created yet. Use the "Create Login" button to set up access.';
                                }

                                $user = $record->user;
                                $roles = $user->roles->pluck('display_name')->implode(', ');

                                return "✅ Account Active\nEmail: {$user->email}\nRoles: {$roles}";
                            })
                            ->visible(fn (?Employee $record) => $record?->user_id)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('create_user_account')
                            ->label('Create Login Account')
                            ->default(fn (?Employee $record) => ! $record?->user_id)
                            ->live()
                            ->dehydrated(false)
                            ->disabled(fn (?Employee $record) => $record?->user_id !== null)
                            ->helperText(fn (?Employee $record) => $record?->user_id ? 'Account already exists. Use header action to manage.' : 'Allow this employee to login to the system')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username')
                                    ->required(fn (Forms\Get $get) => $get('create_user_account') === true)
                                    ->visible(fn (Forms\Get $get, ?Employee $record) => $get('create_user_account') === true && ! $record?->user_id)
                                    ->placeholder('john.doe')
                                    ->helperText('Will be used as email: username@caseer.academy')
                                    ->live()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->required(fn (Forms\Get $get) => $get('create_user_account') === true)
                                    ->visible(fn (Forms\Get $get, ?Employee $record) => $get('create_user_account') === true && ! $record?->user_id)
                                    ->minLength(8)
                                    ->same('password_confirmation')
                                    ->dehydrated(false)
                                    ->live()
                                    ->helperText('Minimum 8 characters'),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->password()
                                    ->label('Confirm Password')
                                    ->required(fn (Forms\Get $get) => $get('create_user_account') === true && filled($get('password')))
                                    ->visible(fn (Forms\Get $get, ?Employee $record) => $get('create_user_account') === true && ! $record?->user_id)
                                    ->dehydrated(false)
                                    ->live(),

                                Forms\Components\Select::make('role_id')
                                    ->label('System Role')
                                    ->options(Role::orderBy('sort_order')->pluck('display_name', 'id'))
                                    ->required(fn (Forms\Get $get) => $get('create_user_account') === true)
                                    ->visible(fn (Forms\Get $get, ?Employee $record) => $get('create_user_account') === true && ! $record?->user_id)
                                    ->searchable()
                                    ->helperText('Select the role that determines system permissions')
                                    ->live()
                                    ->dehydrated(false),

                                Forms\Components\Placeholder::make('role_description')
                                    ->label('Role Permissions')
                                    ->visible(fn (Forms\Get $get, ?Employee $record) => $get('create_user_account') === true && filled($get('role_id')) && ! $record?->user_id)
                                    ->content(function (Forms\Get $get) {
                                        $roleId = $get('role_id');
                                        if (! filled($roleId)) {
                                            return '';
                                        }

                                        $role = Role::find($roleId);

                                        return $role?->description ?? 'No description available';
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('salary_role_connection')
                                    ->label('💡 Salary & Role Connection')
                                    ->content(function (Forms\Get $get, ?Employee $record) {
                                        $salary = $get('base_salary') ?? $record?->base_salary ?? 0;
                                        $roleId = $get('role_id');

                                        if (! filled($roleId) && $record) {
                                            $roleId = $record->user?->roles?->first()?->id;
                                        }

                                        if (! filled($roleId) || ! $salary) {
                                            return 'Set both salary and role to see recommended salary ranges per role.';
                                        }

                                        $role = Role::find($roleId);
                                        $roleName = $role?->display_name ?? 'N/A';

                                        // Provide context about typical salary expectations based on role
                                        $recommendations = match ($role?->name) {
                                            'admin' => 'Typical range: $60k-$120k+',
                                            'hr' => 'Typical range: $40k-$80k',
                                            'accounting' => 'Typical range: $50k-$90k',
                                            'sales' => 'Typical range: $40k-$100k (with commissions)',
                                            'marketing' => 'Typical range: $45k-$85k',
                                            'support' => 'Typical range: $35k-$60k',
                                            'viewer' => 'Typical range: $30k-$50k',
                                            default => 'Check industry standards',
                                        };

                                        return "Role: {$roleName}\nSalary: \${$salary}\n{$recommendations}";
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Forms\Get $get, ?Employee $record) => $get('create_user_account') && ! $record?->user_id),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Benefits & Documents')
                    ->description('Employee benefits and document tracking')
                    ->schema([
                        Forms\Components\KeyValue::make('benefits')
                            ->label('Benefits')
                            ->keyLabel('Benefit Type')
                            ->valueLabel('Details')
                            ->reorderable()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('documents')
                            ->label('Documents')
                            ->keyLabel('Document Type')
                            ->valueLabel('File Path / Reference')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('employment_type')
                    ->colors([
                        'success' => 'full_time',
                        'info' => 'part_time',
                        'warning' => 'contract',
                        'gray' => 'intern',
                    ]),

                Tables\Columns\TextColumn::make('formatted_base_salary')
                    ->label('Salary')
                    ->weight(FontWeight::Medium),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'on_leave',
                        'danger' => 'terminated',
                        'gray' => 'suspended',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-clock' => 'on_leave',
                        'heroicon-o-x-circle' => 'terminated',
                        'heroicon-o-pause-circle' => 'suspended',
                    ]),

                Tables\Columns\TextColumn::make('hire_date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenure_in_years')
                    ->label('Tenure')
                    ->getStateUsing(fn (Employee $record) => $record->tenure_in_years.' yrs')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('hire_date', $direction === 'asc' ? 'desc' : 'asc')
                    ),

                Tables\Columns\TextColumn::make('user.role_names')
                    ->label('System Role')
                    ->badge()
                    ->separator(',')
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('user_id')
                    ->label('Has Login')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->options(fn () => array_combine(Employee::getDepartments(), Employee::getDepartments())),

                Tables\Filters\SelectFilter::make('employment_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'contract' => 'Contract',
                        'intern' => 'Intern',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'on_leave' => 'On Leave',
                        'terminated' => 'Terminated',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('terminate')
                    ->label('Terminate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Employee $record) => $record->status === 'active')
                    ->form([
                        Forms\Components\DatePicker::make('termination_date')
                            ->label('Termination Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Textarea::make('reason')
                            ->label('Termination Reason')
                            ->required(),
                    ])
                    ->action(function (Employee $record, array $data): void {
                        $record->terminate(\Carbon\Carbon::parse($data['termination_date']), $data['reason']);

                        \Filament\Notifications\Notification::make()
                            ->title('Employee Terminated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('hire_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
