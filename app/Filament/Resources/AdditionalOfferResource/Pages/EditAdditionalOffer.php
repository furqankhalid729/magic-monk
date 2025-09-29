<?php

namespace App\Filament\Resources\AdditionalOfferResource\Pages;

use App\Filament\Resources\AdditionalOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdditionalOffer extends EditRecord
{
    protected static string $resource = AdditionalOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
