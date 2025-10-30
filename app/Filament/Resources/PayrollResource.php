<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payroll Period')
                    ->description('Select employee and payroll period')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Employee $record) => "{$record->full_name} ({$record->employee_id}) - {$record->position}"
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee) {
                                        $set('base_pay', $employee->base_salary);
                                        $set('currency', $employee->salary_currency);
                                    }
                                }
                            })
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('period_start_date')
                            ->required()
                            ->reactive(),

                        Forms\Components\DatePicker::make('period_end_date')
                            ->required()
                            ->after('period_start_date')
                            ->reactive(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(fn (Forms\Get $get) => $get('period_end_date') ?
                                Carbon::parse($get('period_end_date'))->addDays(5) :
                                now()->addDays(5)
                            ),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Earnings')
                    ->description('Base pay and additional earnings')
                    ->schema([
                        Forms\Components\TextInput::make('base_pay')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->reactive(),

                        Forms\Components\TextInput::make('overtime_pay')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('bonus')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('commission')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('allowances')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\Placeholder::make('gross_pay_display')
                            ->label('Gross Pay (Auto-calculated)')
                            ->content(function (Forms\Get $get) {
                                $gross = ($get('base_pay') ?? 0) +
                                        ($get('overtime_pay') ?? 0) +
                                        ($get('bonus') ?? 0) +
                                        ($get('commission') ?? 0) +
                                        ($get('allowances') ?? 0);

                                return '$'.number_format($gross, 2);
                            }),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Deductions')
                    ->description('Tax and other deductions')
                    ->schema([
                        Forms\Components\TextInput::make('tax_deduction')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('insurance_deduction')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('retirement_deduction')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('other_deductions')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->reactive(),

                        Forms\Components\Placeholder::make('total_deductions_display')
                            ->label('Total Deductions (Auto-calculated)')
                            ->content(function (Forms\Get $get) {
                                $total = ($get('tax_deduction') ?? 0) +
                                        ($get('insurance_deduction') ?? 0) +
                                        ($get('retirement_deduction') ?? 0) +
                                        ($get('other_deductions') ?? 0);

                                return '$'.number_format($total, 2);
                            })
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Hours & Status')
                    ->description('Work hours and payroll status')
                    ->schema([
                        Forms\Components\TextInput::make('hours_worked')
                            ->numeric()
                            ->step(0.5)
                            ->suffix('hrs')
                            ->placeholder('160'),

                        Forms\Components\TextInput::make('overtime_hours')
                            ->numeric()
                            ->step(0.5)
                            ->default(0)
                            ->suffix('hrs'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('draft'),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Summary')
                    ->description('Payroll summary')
                    ->schema([
                        Forms\Components\Placeholder::make('net_pay_display')
                            ->label('NET PAY (Take Home)')
                            ->content(function (Forms\Get $get) {
                                $gross = ($get('base_pay') ?? 0) +
                                        ($get('overtime_pay') ?? 0) +
                                        ($get('bonus') ?? 0) +
                                        ($get('commission') ?? 0) +
                                        ($get('allowances') ?? 0);

                                $deductions = ($get('tax_deduction') ?? 0) +
                                             ($get('insurance_deduction') ?? 0) +
                                             ($get('retirement_deduction') ?? 0) +
                                             ($get('other_deductions') ?? 0);

                                $net = $gross - $deductions;

                                return '$'.number_format($net, 2);
                            })
                            ->extraAttributes(['class' => 'text-2xl font-bold text-green-600']),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('employee.employee_id')
                    ->label('Emp ID')
                    ->searchable()
                    ->fontFamily('mono')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payroll_period')
                    ->label('Period')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Pay Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_gross_pay')
                    ->label('Gross Pay')
                    ->weight(FontWeight::SemiBold)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('formatted_total_deductions')
                    ->label('Deductions')
                    ->color('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_net_pay')
                    ->label('Net Pay')
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_approval',
                        'info' => 'approved',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-clock' => 'pending_approval',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-banknotes' => 'paid',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                    ]),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->placeholder('From date'),
                        Forms\Components\DatePicker::make('until')
                            ->placeholder('Until date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_payroll')
                    ->label('Generate Payroll')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('employees')
                            ->label('Employees')
                            ->multiple()
                            ->options(Employee::active()->get()->mapWithKeys(fn ($employee) => [
                                $employee->id => "{$employee->full_name} ({$employee->position})",
                            ]))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select employees to generate payroll for'),

                        Forms\Components\DatePicker::make('period_start')
                            ->label('Period Start')
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Period End')
                            ->required()
                            ->default(Carbon::now()->endOfMonth())
                            ->after('period_start'),
                    ])
                    ->action(function (array $data): void {
                        $periodStart = Carbon::parse($data['period_start']);
                        $periodEnd = Carbon::parse($data['period_end']);
                        $count = 0;

                        foreach ($data['employees'] as $employeeId) {
                            $employee = Employee::find($employeeId);
                            if ($employee) {
                                Payroll::generateForEmployee($employee, $periodStart, $periodEnd);
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title('Payroll Generated')
                            ->body("Generated payroll for {$count} employees")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payroll $record) => in_array($record->status, ['draft', 'pending_approval']))
                    ->requiresConfirmation()
                    ->action(function (Payroll $record): void {
                        $record->approve(Auth::user());

                        Notification::make()
                            ->title('Payroll Approved')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Payroll $record) => $record->status === 'approved')
                    ->form([
                        Forms\Components\Toggle::make('create_transaction')
                            ->label('Create Expense Transaction')
                            ->default(true)
                            ->helperText('Create a transaction record in the Finance system'),
                    ])
                    ->action(function (Payroll $record, array $data): void {
                        if ($data['create_transaction'] ?? true) {
                            $transaction = $record->createPaymentTransaction();
                            Notification::make()
                                ->title('Payroll Paid & Transaction Created')
                                ->body("Payment of {$record->formatted_net_pay} recorded")
                                ->success()
                                ->send();
                        } else {
                            $record->markAsPaid();
                            Notification::make()
                                ->title('Payroll Marked as Paid')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Payroll $record) => $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_bulk')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $user = Auth::user();
                            $count = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, ['draft', 'pending_approval'])) {
                                    $record->approve($user);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Approved {$count} payroll records")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('mark_paid_bulk')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Toggle::make('create_transactions')
                                ->label('Create Expense Transactions')
                                ->default(true),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'approved') {
                                    if ($data['create_transactions'] ?? true) {
                                        $record->createPaymentTransaction();
                                    } else {
                                        $record->markAsPaid();
                                    }
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Marked {$count} payroll records as paid")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
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
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_approval')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
