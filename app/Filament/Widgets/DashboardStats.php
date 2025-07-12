<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\Location;
use App\Models\AgentDailyStock;
use Filament\Widgets\Widget;

class DashboardStats extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-stats';
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 4, 
    ];

    protected function getViewData(): array
    {
        return [
            'totalAgents' => Agent::count(),
            'activeAgents' => Agent::where('status', 'active')->count(),
            'totalLocations' => Location::count(),
            'todayPicked' => AgentDailyStock::whereDate('date', today())->count(),
        ];
    }
}
