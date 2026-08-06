<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use App\Models\Location;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    protected function afterCreate(): void
    {
        $this->syncPrimaryAgent();
    }

    protected function syncPrimaryAgent(): void
    {
        /** @var Location $location */
        $location = $this->record->load('agents');
        $primaryAgentId = $location->agents->pluck('id')->filter()->first();

        $location->forceFill([
            'agent_id' => $primaryAgentId,
        ])->save();
    }
}
