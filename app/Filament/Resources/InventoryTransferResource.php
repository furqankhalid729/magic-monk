<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryTransferResource\Pages;
use App\Filament\Resources\InventoryTransferResource\RelationManagers;
use App\Models\InventoryTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryTransferResource extends Resource
{
    protected static ?string $model = InventoryTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('source_agent_id')
                ->label('Source Agent')
                ->relationship('sourceAgent', 'name')
                ->required(),

            Forms\Components\Select::make('destination_agent_id')
                ->label('Destination Agent')
                ->relationship('destinationAgent', 'name')
                ->required(),

            Forms\Components\Select::make('transfer_type')
                ->label('Type')
                ->options([
                    'borrow' => 'Borrow',
                    'return' => 'Return',
                    'buy' => 'Buy',
                    'adjustment' => 'Adjustment',
                ])
                ->required(),

            Forms\Components\Textarea::make('notes'),

            // Nested items
            Forms\Components\Repeater::make('items')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Product')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $product = \App\Models\Product::find($state);
                                if ($product) {
                                    $set('price', $product->price); // auto-fill price
                                }
                            }
                        }),

                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->prefix('$')
                        ->nullable(),
                ])
                ->columns(3)
                ->minItems(1)
                ->required()
                ->columnSpanFull()

        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('sourceAgent.name')->label('Source'),
                Tables\Columns\TextColumn::make('destinationAgent.name')->label('Destination'),
                Tables\Columns\TextColumn::make('transfer_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Date'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListInventoryTransfers::route('/'),
            'create' => Pages\CreateInventoryTransfer::route('/create'),
            'view' => Pages\ViewInventoryTransfer::route('/{record}'),
            'edit' => Pages\EditInventoryTransfer::route('/{record}/edit'),
        ];
    }
}
