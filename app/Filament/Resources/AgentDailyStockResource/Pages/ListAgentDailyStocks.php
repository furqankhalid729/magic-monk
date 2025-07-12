<?php

namespace App\Filament\Resources\AgentDailyStockResource\Pages;

use App\Filament\Resources\AgentDailyStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgentDailyStocks extends ListRecords
{
    protected static string $resource = AgentDailyStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
