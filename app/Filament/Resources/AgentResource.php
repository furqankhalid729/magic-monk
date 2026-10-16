<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Filament\Resources\AgentResource\RelationManagers;
use App\Models\Agent;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Str;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                FileUpload::make('photo_path')->image()->directory('agents'),
                TextInput::make('whatsapp_number')->tel()->maxLength(10),
                TextInput::make('pan_number')->required(),
                FileUpload::make('pan_card_path')->image()->directory('agents'),
                FileUpload::make('aadhar_card_path')->image()->directory('agents'),
                TextInput::make('upi_id'),
                Select::make('city')->options([
                    'Mumbai' => 'Mumbai',
                    'Delhi' => 'Delhi',
                ]),
                Select::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),
                Select::make('source_type')->options([
                    'agent' => 'Agent',
                    'store' => 'Store',
                ]),
                TextInput::make('source_pos'),
                Toggle::make('bypass_device_check')
                    ->label('Bypass device check')
                    ->helperText('On: the agent can log in from any device (devices are still recorded). Off: only the registered device is allowed.')
                    ->default(true),
                Section::make('Registered device')
                    ->visibleOn('edit')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('device_model')
                            ->label('Device')
                            ->content(fn (?Agent $record) => $record?->device_id
                                ? trim(data_get($record->device_info, 'brand') . ' ' . data_get($record->device_info, 'modelName'))
                                : 'No device registered yet'),
                        Placeholder::make('device_id_value')
                            ->label('Device ID')
                            ->content(fn (?Agent $record) => $record?->device_id ?? '—'),
                        Placeholder::make('device_os')
                            ->label('OS')
                            ->content(fn (?Agent $record) => data_get($record?->device_info, 'osName')
                                ? data_get($record->device_info, 'platform') . ' ' . data_get($record->device_info, 'osVersion')
                                : '—'),
                        Placeholder::make('device_registered')
                            ->label('Registered at')
                            ->content(fn (?Agent $record) => $record?->device_registered_at
                                ?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->size(40)
                    ->sortable(),
                    

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('whatsapp_number')
                    ->label('WhatsApp')
                    ->searchable(),

                TextColumn::make('source_type')
                    ->label('Source Type')
                    ->formatStateUsing(fn ($state) => Str::title($state))
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->sortable(),

                IconColumn::make('bypass_device_check')
                    ->label('Bypass device check')
                    ->boolean()
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
            RelationManagers\LoginSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
