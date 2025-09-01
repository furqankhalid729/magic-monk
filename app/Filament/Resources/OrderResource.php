<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderByDesc('order_time');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('order_id')->required(),
            Forms\Components\TextInput::make('customer_name')->required(),
            Forms\Components\TextInput::make('customer_phone'),
            Forms\Components\TextInput::make('building'),
            Forms\Components\DateTimePicker::make('order_time'),
            Forms\Components\DateTimePicker::make('delivery_time'),
            Forms\Components\TextInput::make('agent_number'),
            Forms\Components\TextInput::make('message_id'),
            Forms\Components\TextInput::make('total_amount'),
            Forms\Components\DateTimePicker::make('delivered_on'),
            Forms\Components\TextInput::make('status'),
            Forms\Components\TextInput::make('review'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')->label('Order Number')->sortable()->searchable(),
                TextColumn::make('customer_name')->sortable()->searchable(),
                TextColumn::make('agent_number')->sortable()->searchable(),
                TextColumn::make('order_time')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('date_range')
                ->form([
                    DatePicker::make('fromDate')->label('From'),
                    DatePicker::make('toDate')->label('To'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['fromDate'], fn (Builder $q, $date) => $q->whereDate('order_time', '>=', $date))
                        ->when($data['toDate'], fn (Builder $q, $date) => $q->whereDate('order_time', '<=', $date));
                }),
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
            RelationManagers\OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
