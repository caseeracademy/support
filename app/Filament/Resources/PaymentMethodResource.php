<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Accounts';

    protected static ?int $navigationSort = 4;

    protected static ?string $pluralModelLabel = 'Accounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Account Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Business Bank Account, Cash, PayPal, etc.')
                            ->helperText('Display name for this account'),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->placeholder('business-bank-account')
                            ->helperText('URL-friendly identifier (auto-generated from name if left empty)')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state, Forms\Get $get) {
                                if (empty($state) && $get('name')) {
                                    $set('slug', \Illuminate\Support\Str::slug($get('name')));
                                }
                            }),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->placeholder('Brief description of this account')
                            ->helperText('Optional description of the account and its purpose'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Type & Usage')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Payment Type')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'card' => 'Card (Credit/Debit)',
                                'digital_wallet' => 'Digital Wallet (PayPal, Stripe, etc.)',
                                'cryptocurrency' => 'Cryptocurrency',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('bank_transfer')
                            ->helperText('The method/medium of payment'),

                        Forms\Components\Select::make('transaction_type')
                            ->label('Used For')
                            ->options([
                                'income' => 'Income Only (Money coming in)',
                                'expense' => 'Expenses Only (Money going out)',
                                'both' => 'Both Income & Expenses',
                            ])
                            ->required()
                            ->default('both')
                            ->helperText('Select whether this account is used for income, expenses, or both'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive accounts will not appear in dropdowns'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->helperText('Optional: Additional configuration data (e.g., account numbers, routing info)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Account Name')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('type')
                    ->label('Payment Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'card' => 'Card',
                        'digital_wallet' => 'Digital Wallet',
                        'cryptocurrency' => 'Crypto',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'gray',
                        'bank_transfer' => 'success',
                        'card' => 'info',
                        'digital_wallet' => 'warning',
                        'cryptocurrency' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Used For')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'income' => 'Income',
                        'expense' => 'Expenses',
                        'both' => 'Both',
                        default => 'Both',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'both' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Used For')
                    ->options([
                        'income' => 'Income Only',
                        'expense' => 'Expenses Only',
                        'both' => 'Both',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Payment Type')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'card' => 'Card',
                        'digital_wallet' => 'Digital Wallet',
                        'cryptocurrency' => 'Cryptocurrency',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All accounts')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
