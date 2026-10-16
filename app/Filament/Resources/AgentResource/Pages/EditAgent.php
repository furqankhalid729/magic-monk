<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAgent extends EditRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetDevice')
                ->label('Reset device')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('The registered device will be cleared. The next successful login binds whichever device the agent uses.')
                ->visible(fn () => $this->getRecord()->device_id !== null)
                ->action(function () {
                    $this->getRecord()->resetDevice();

                    Notification::make()->title('Device reset')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
