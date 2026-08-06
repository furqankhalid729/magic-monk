<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Services\OdooService;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('sku')->required(),
                Select::make('odoo_product_id')
                    ->label('Odoo Product')
                    ->searchable()
                    ->placeholder('Search Odoo POS products')
                    ->getSearchResultsUsing(fn (string $search): array => rescue(
                        fn () => self::getOdooService()->getProductMappingOptions($search),
                        [],
                        report: false,
                    ))
                    ->getOptionLabelUsing(fn ($value): ?string => blank($value)
                        ? null
                        : rescue(
                            fn () => self::getOdooService()->getProductMappingLabel((int) $value),
                            "Mapped Odoo Product #{$value}",
                            report: false,
                        ))
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state): void {
                        if (blank($state)) {
                            $set('odoo_product_sku', null);
                            $set('odoo_product_name', null);

                            return;
                        }

                        $product = rescue(
                            fn () => self::getOdooService()->findProductForMapping((int) $state),
                            null,
                            report: false,
                        );

                        $set('odoo_product_sku', $product['default_code'] ?? null);
                        $set('odoo_product_name', $product['name'] ?? null);
                    })
                    ->helperText('Maps this local product to an Odoo POS product for order sync.'),
                Hidden::make('odoo_product_sku'),
                Hidden::make('odoo_product_name'),
                TextInput::make('price')
                    ->numeric()
                    ->rules(['numeric', 'min:0'])
                    ->prefix('₹')
                    ->required(),
                TextInput::make('inventory')
                    ->numeric()
                    ->rules(['integer', 'min:0'])
                    ->required(),
                TextInput::make('image')->url()->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('odoo_product_name')
                    ->label('Odoo Product')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('odoo_product_sku')
                    ->label('Odoo SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('INR', true) 
                    ->sortable(),

                TextColumn::make('inventory')
                    ->label('Inventory')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    protected static function getOdooService(): OdooService
    {
        return app(OdooService::class);
    }
}
