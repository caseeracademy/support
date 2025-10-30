<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
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

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Budget Details')
                    ->description('Basic budget information and period settings')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Q1 2024 Operations Budget')
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->placeholder('Detailed description of the budget purpose and scope')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('period_type')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                $startDate = $get('start_date');
                                if ($startDate) {
                                    $start = Carbon::parse($startDate);
                                    $endDate = match ($get('period_type')) {
                                        'monthly' => $start->copy()->endOfMonth(),
                                        'quarterly' => $start->copy()->addMonths(3)->subDay(),
                                        'yearly' => $start->copy()->endOfYear(),
                                        default => $start->copy()->endOfMonth(),
                                    };
                                    $set('end_date', $endDate->format('Y-m-d'));
                                }
                            }),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                $periodType = $get('period_type');
                                if ($periodType) {
                                    $start = Carbon::parse($get('start_date'));
                                    $endDate = match ($periodType) {
                                        'monthly' => $start->copy()->endOfMonth(),
                                        'quarterly' => $start->copy()->addMonths(3)->subDay(),
                                        'yearly' => $start->copy()->endOfYear(),
                                        default => $start->copy()->endOfMonth(),
                                    };
                                    $set('end_date', $endDate->format('Y-m-d'));
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->after('start_date'),

                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(9999999.99)
                            ->placeholder('10000.00'),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD ($)',
                                'EUR' => 'EUR (€)',
                                'GBP' => 'GBP (£)',
                            ])
                            ->default('USD')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Category Allocations')
                    ->description('Allocate budget amounts to specific expense categories')
                    ->schema([
                        Forms\Components\Repeater::make('budgetCategories')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name', fn (Builder $query) => $query->where('type', 'expense')->where('is_active', true)
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'expense' => 'Expense',
                                            ])
                                            ->default('expense')
                                            ->required(),
                                        Forms\Components\TextInput::make('color')
                                            ->default('#EF4444'),
                                    ])
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('allocated_amount')
                                    ->label('Allocated Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0),

                                Forms\Components\Toggle::make('alert_at_80_percent')
                                    ->label('80% Alert')
                                    ->default(true),

                                Forms\Components\Toggle::make('alert_at_100_percent')
                                    ->label('100% Alert')
                                    ->default(true),
                            ])
                            ->columns(5)
                            ->reorderable(false)
                            ->addActionLabel('Add Category')
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\BadgeColumn::make('period_type')
                    ->colors([
                        'info' => 'monthly',
                        'warning' => 'quarterly',
                        'success' => 'yearly',
                    ])
                    ->icons([
                        'heroicon-o-calendar' => 'monthly',
                        'heroicon-o-calendar-days' => 'quarterly',
                        'heroicon-o-calendar-date-range' => 'yearly',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Days Left')
                    ->getStateUsing(fn (Budget $record) => $record->days_remaining)
                    ->color(fn (Budget $record): string => match (true) {
                        $record->is_expired => 'danger',
                        $record->days_remaining <= 7 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('formatted_total_amount')
                    ->label('Total Budget')
                    ->weight(FontWeight::SemiBold)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('formatted_total_spent')
                    ->label('Spent')
                    ->weight(FontWeight::Medium)
                    ->color(fn (Budget $record): string => match (true) {
                        $record->spent_percentage >= 100 => 'danger',
                        $record->spent_percentage >= 80 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('spent_percentage')
                    ->label('Usage')
                    ->getStateUsing(fn (Budget $record) => number_format($record->spent_percentage, 1).'%')
                    ->badge()
                    ->color(fn (Budget $record): string => match (true) {
                        $record->spent_percentage >= 100 => 'danger',
                        $record->spent_percentage >= 80 => 'warning',
                        $record->spent_percentage >= 50 => 'info',
                        default => 'success',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                        'info' => 'completed',
                        'warning' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-check-badge' => 'completed',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('period_type')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'yearly' => 'Yearly',
                    ]),

                Tables\Filters\Filter::make('current')
                    ->query(fn (Builder $query): Builder => $query->current())
                    ->label('Current Budgets')
                    ->toggle(),

                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->expired())
                    ->label('Expired Budgets')
                    ->toggle(),

                Tables\Filters\Filter::make('over_budget')
                    ->query(fn (Builder $query): Builder => $query->whereHas('budgetCategories', fn ($q) => $q->whereRaw('spent_amount > allocated_amount')
                    )
                    )
                    ->label('Over Budget')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('update_spent')
                    ->label('Update Spent')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Budget $record): void {
                        $record->updateSpentAmounts();

                        Notification::make()
                            ->title('Spent amounts updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Budget $record) => $record->status === 'draft')
                    ->action(function (Budget $record): void {
                        $record->activate();

                        Notification::make()
                            ->title('Budget activated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Budget $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (Budget $record): void {
                        $record->complete();

                        Notification::make()
                            ->title('Budget marked as completed')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('check_alerts')
                    ->label('Check Alerts')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->action(function (Budget $record): void {
                        $alerts = $record->checkBudgetAlerts();

                        if (empty($alerts)) {
                            Notification::make()
                                ->title('No alerts needed')
                                ->body('All categories are within their budget limits.')
                                ->success()
                                ->send();
                        } else {
                            $alertCount = count($alerts);
                            $message = "Found {$alertCount} budget alert(s):";
                            foreach ($alerts as $alert) {
                                $message .= "\n• {$alert['category']}: {$alert['percentage']}% used";
                            }

                            Notification::make()
                                ->title('Budget Alerts')
                                ->body($message)
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_spent_bulk')
                        ->label('Update Spent Amounts')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->updateSpentAmounts();
                            }

                            $count = count($records);
                            Notification::make()
                                ->title("Updated spent amounts for {$count} budgets")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('activate_bulk')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'draft') {
                                    $record->activate();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Activated {$count} budgets")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
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
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
