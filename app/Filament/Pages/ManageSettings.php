<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Settings';
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::first();

        // preload form state
        $this->form->fill(
            $settings?->toArray() ?? []
        );
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('shipping_rate')
                ->label('Shipping Rate')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('fast_mover_shipping_rate')
                ->label('Fast Mover Shippinhg Rate')
                ->numeric()
                ->required(),
        ];
    }

    protected function getFormModel(): string
    {
        return Setting::class; // ✅ bind form to your model
    }

    protected function getFormStatePath(): string
    {
        return 'data'; // ✅ ensure form uses $data property
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $settings = Setting::firstOrCreate(['id' => 1]);
        $settings->fill($data)->save();

        Notification::make()
            ->title('Settings updated successfully!')
            ->success()
            ->send();
    }
}
