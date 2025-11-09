<?php

namespace App\Filament\Resources\SubscriptionOfferResource\Pages;

use App\Filament\Resources\SubscriptionOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionOffer extends EditRecord
{
    protected static string $resource = SubscriptionOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
