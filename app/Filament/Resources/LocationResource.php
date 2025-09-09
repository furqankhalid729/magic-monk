<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;


class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->options([
                        'FastMovers' => 'FastMovers',
                        'Residential' => 'Residential',
                    ])
                    ->required(),

                TextInput::make('building_name')
                    ->maxLength(20)
                    ->required(),

                TextInput::make('google_map_url')
                    ->url()
                    ->required(),

                TextInput::make('latitude')
                    ->numeric()
                    ->nullable(),

                TextInput::make('longitude')
                    ->numeric()
                    ->nullable(),

                // 👉 New fields
                TextInput::make('reach_or_flats')
                    ->label('Reach / No. of Flats')
                    ->numeric()
                    ->nullable(),

                TextInput::make('road_name')
                    ->label('Road Name')
                    ->required(),

                TextInput::make('sub_locality')
                    ->label('Sub Locality')
                    ->nullable(),

                TextInput::make('city')
                    ->label('City')
                    ->required(),

                TextInput::make('state')
                    ->label('State')
                    ->required(),

                TextInput::make('pincode')
                    ->label('Pincode')
                    ->required(),

                Select::make('agent_id')
                    ->relationship('agent', 'name')
                    ->searchable()
                    ->required(),

                Toggle::make('agent_logged_in')
                    ->label('Agent Logged In'),

                Toggle::make('is_offer_live')
                    ->label('Is Offer LIVE'),

                DatePicker::make('offer_live_until')
                    ->label('Offer LIVE Until')
                    ->nullable(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('building_name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_offer_live')
                    ->label('Offer Live')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),

                TextColumn::make('agent.name')
                    ->label('Agent Assigned')
                    ->sortable()
                    ->searchable(),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
