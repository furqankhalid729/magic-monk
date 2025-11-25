<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionOfferResource\Pages;
use App\Models\SubscriptionOffer;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class SubscriptionOfferResource extends Resource
{
    protected static ?string $model = SubscriptionOffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Subscriptions';
    protected static ?string $navigationLabel = 'Subscription Offers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Offer Name')
                ->required()
                ->maxLength(255),

            TextInput::make('price')
                ->numeric()
                ->label('Price')
                ->required(),

            TextInput::make('discount_amount')
                ->numeric()
                ->label('Discount Amount')
                ->required(),

            FileUpload::make('image_url')
                ->label('Image')
                ->image()
                ->directory('subscription-offers')
                ->required(),

            TextInput::make('number_of_products')
                ->numeric()
                ->label('No. of Products')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('image_url')
                ->label('Image'),

            TextColumn::make('name')
                ->label('Offer Name')
                ->sortable()
                ->searchable(),

            TextColumn::make('price')
                ->label('Price')
                ->money('usd'),

            TextColumn::make('number_of_products')
                ->label('Products Count')
                ->sortable(),
        ])
        ->filters([])
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
            'index' => Pages\ListSubscriptionOffers::route('/'),
            'create' => Pages\CreateSubscriptionOffer::route('/create'),
            'edit' => Pages\EditSubscriptionOffer::route('/{record}/edit'),
        ];
    }
}
