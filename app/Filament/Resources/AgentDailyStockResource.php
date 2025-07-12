<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentDailyStockResource\Pages;
use App\Filament\Resources\AgentDailyStockResource\RelationManagers;
use App\Models\AgentDailyStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;

class AgentDailyStockResource extends Resource
{
    protected static ?string $model = AgentDailyStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('agent_id')->relationship('agent', 'name')->required(),
                Select::make('product_id')->relationship('product', 'name')->required(),
                TextInput::make('picked_qty')->numeric()->minValue(0)->required(),
                TextInput::make('returned_qty')->numeric()->minValue(0),
                DatePicker::make('date')->required(),
                DateTimePicker::make('picked_at')->default(now()),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent.name')
                    ->label('Agent Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('picked_at')
                    ->label('Picked Time')
                    ->dateTime('d M Y, h:i A')
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
            'index' => Pages\ListAgentDailyStocks::route('/'),
            'create' => Pages\CreateAgentDailyStock::route('/create'),
            'edit' => Pages\EditAgentDailyStock::route('/{record}/edit'),
        ];
    }
}
