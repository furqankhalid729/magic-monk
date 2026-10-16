<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use App\Models\AgentLoginSession;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoginSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'loginSessions';

    protected static ?string $title = 'Login sessions';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('login_date')
            ->defaultSort('login_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('login_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('login_at')
                    ->label('Login')
                    ->dateTime('h:i A', 'Asia/Kolkata')
                    ->sortable(),

                Tables\Columns\TextColumn::make('logout_at')
                    ->label('Logout')
                    ->dateTime('h:i A', 'Asia/Kolkata')
                    ->placeholder('No logout')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(fn (AgentLoginSession $record) => $record->logout_at
                        ? $record->login_at->diffForHumans($record->logout_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2])
                        : null)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('device')
                    ->label('Device')
                    ->getStateUsing(fn (AgentLoginSession $record) => collect([
                        data_get($record->device_info, 'brand'),
                        data_get($record->device_info, 'modelName'),
                    ])->filter()->implode(' '))
                    ->placeholder('Not reported')
                    ->description(fn (AgentLoginSession $record) => $record->device_id),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (AgentLoginSession $record) => match (true) {
                        $record->blocked => 'Blocked',
                        $record->device_matched === false => 'Device mismatch',
                        $record->device_matched === true => 'Matched',
                        default => 'No device info',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Blocked' => 'danger',
                        'Device mismatch' => 'warning',
                        'Matched' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('mismatch')
                    ->label('Differences')
                    ->getStateUsing(fn (AgentLoginSession $record) => collect($record->mismatch_fields['hard'] ?? [])
                        ->merge($record->mismatch_fields['soft'] ?? [])
                        ->implode(', '))
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('public_ip')
                    ->label('Public IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('app_version')
                    ->label('App version')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('problems')
                    ->label('Blocked or mismatched')
                    ->query(fn ($query) => $query->where(fn ($q) => $q->where('blocked', true)->orWhere('device_matched', false))),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
