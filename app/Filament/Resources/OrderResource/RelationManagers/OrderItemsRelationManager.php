<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use Filament\Forms\Form;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items'; // matches Order::items() relationship

    protected static ?string $title = 'Order Items';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('item_name')->required(),
            TextInput::make('price')->numeric()->required(),
            TextInput::make('quantity')->numeric()->required(),
            TextInput::make('amount')->numeric()->required(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('item_name')->sortable()->searchable(),
                TextColumn::make('price'),
                TextColumn::make('quantity'),
                TextColumn::make('amount'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
