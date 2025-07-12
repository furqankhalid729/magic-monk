<?php

namespace App\Filament\Resources\AgentDailyStockResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\AgentDailyStock;

class TodayStockActivity extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Today Picked Entries', AgentDailyStock::whereDate('date', today())->count())
                ->description('Stock records submitted today')
                ->icon('heroicon-o-clock')
                ->color('info'),
        ];
    }
}
