<?php

namespace App\Filament\Resources\AgentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Agent;

class AgentStatusCount extends BaseWidget
{
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 4, 
    ];

    protected function getStats(): array
    {
        return [
            Stat::make('Active Agents', Agent::where('status', 'active')->count())
                ->description('Total currently active')
                ->color('success'),

            Stat::make('Inactive Agents', Agent::where('status', 'inactive')->count())
                ->description('Agents not currently working')
                ->color('danger'),
        ];
    }
}
