<?php

namespace App\Filament\Resources\AgentDailyStockResource\Pages;

use App\Filament\Resources\AgentDailyStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentDailyStock extends EditRecord
{
    protected static string $resource = AgentDailyStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
