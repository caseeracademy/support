<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->description('Basic category details for organizing transactions')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, Forms\Set $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null
                            )
                            ->placeholder('e.g., Office Supplies, Consulting Revenue'),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('URL-friendly identifier (auto-generated from name)'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'income' => 'Income',
                                'expense' => 'Expense',
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Choose whether this category is for income or expense transactions'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Display & Description')
                    ->description('Visual appearance and additional details')
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->default('#3B82F6')
                            ->required()
                            ->helperText('Color used for visual identification in charts and badges'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive categories cannot be used for new transactions'),

                        Forms\Components\Textarea::make('description')
                            ->placeholder('Optional description for this category...')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('')
                    ->width('8px'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ])
                    ->icons([
                        'heroicon-o-arrow-trending-up' => 'income',
                        'heroicon-o-arrow-trending-down' => 'expense',
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->placeholder('No description')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->counts('transactions')
                    ->label('Transactions')
                    ->sortable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('transactions_sum_amount')
                    ->sum('transactions', 'amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All categories')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('has_transactions')
                    ->query(fn (Builder $query): Builder => $query->has('transactions'))
                    ->label('Has transactions'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Category $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Category $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Category $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Category $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No transaction categories yet')
            ->emptyStateDescription('Create your first category to start organizing transactions.')
            ->emptyStateIcon('heroicon-o-tag');
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
