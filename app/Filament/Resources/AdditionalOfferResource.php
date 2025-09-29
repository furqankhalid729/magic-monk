<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdditionalOfferResource\Pages;
use App\Filament\Resources\AdditionalOfferResource\RelationManagers;
use App\Models\AdditionalOffer;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;


class AdditionalOfferResource extends Resource
{
    protected static ?string $model = AdditionalOffer::class;

    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $navigationLabel = 'Additional Offers';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->relationship('location', 'building_name')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('discount_type')
                ->options([
                    'percentage' => 'Percentage',
                    'fixed'      => 'Fixed',
                ])
                ->default('percentage')
                ->required(),

            Forms\Components\TextInput::make('discount_value')
                ->numeric()
                ->required()
                ->prefix(fn($get) => $get('discount_type') === 'fixed' ? '$' : '%'),

            Forms\Components\DatePicker::make('expire_date')
                ->label('Expire Date')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),

            Tables\Columns\TextColumn::make('location.building_name')
                ->label('Location')
                ->sortable()
                ->searchable(),

            Tables\Columns\BadgeColumn::make('discount_type')
                ->colors([
                    'success' => 'percentage',
                    'warning' => 'fixed',
                ]),

            Tables\Columns\TextColumn::make('discount_value')
                ->numeric(decimalPlaces: 2),

            Tables\Columns\TextColumn::make('expire_date')
                ->date(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Active Offers')
                    ->query(fn($query) => $query->whereNull('expire_date')->orWhere('expire_date', '>=', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdditionalOffers::route('/'),
            'create' => Pages\CreateAdditionalOffer::route('/create'),
            'edit' => Pages\EditAdditionalOffer::route('/{record}/edit'),
        ];
    }
}
