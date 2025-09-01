<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;

class OrderDashboardStats extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.order-dashboard-stats';
    
    protected int | string | array $columnSpan = 'full';

    public ?array $data = [
        'fromDate' => null,
        'toDate' => null,
    ];

    public function mount(): void
    {
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('fromDate')
                            ->label(false)
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateStats()),
                        
                        DatePicker::make('toDate')
                            ->label(false)
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateStats()),
                            
                        Actions::make([
                            Action::make('clear')
                                ->label('Clear Filters')
                                ->color('gray')
                                ->icon('heroicon-o-x-mark')
                                ->action('clearFilters'),
                        ])->fullWidth(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function updateStats(): void
    {
        // This method will be called when dates change
        // The widget will automatically re-render
    }

    public function clearFilters(): void
    {
        $this->data = [
            'fromDate' => null,
            'toDate' => null,
        ];
        $this->form->fill($this->data);
    }

    public function getStats(): array
    {
        $query = Order::query();

        if ($this->data['fromDate'] && $this->data['toDate']) {
            $query->whereBetween('created_at', [$this->data['fromDate'], $this->data['toDate']]);
        } elseif ($this->data['fromDate']) {
            $query->whereDate('created_at', '>=', $this->data['fromDate']);
        } elseif ($this->data['toDate']) {
            $query->whereDate('created_at', '<=', $this->data['toDate']);
        }

        $orderCount = $query->count();
        $totalRevenue = $query->sum('total_amount') ?? 0; 
        $avgOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;

        return [
            [
                'label' => 'Total Orders',
                'value' => number_format($orderCount),
                'description' => $this->getDateRangeDescription(),
                'color' => 'success',
                'icon' => 'heroicon-o-shopping-bag',
            ],
            [
                'label' => 'Total Revenue',
                'value' => '₹' . number_format($totalRevenue, 2),
                'description' => $this->getDateRangeDescription(),
                'color' => 'primary',
                'icon' => 'heroicon-o-currency-dollar',
            ],
            [
                'label' => 'Avg Order Value',
                'value' => '₹' . number_format($avgOrderValue, 2),
                'description' => $this->getDateRangeDescription(),
                'color' => 'warning',
                'icon' => 'heroicon-o-calculator',
            ],
        ];
    }

    private function getDateRangeDescription(): string
    {
        $fromDate = $this->data['fromDate'];
        $toDate = $this->data['toDate'];
        
        if ($fromDate && $toDate) {
            return "From " . date('M j, Y', strtotime($fromDate)) . " to " . date('M j, Y', strtotime($toDate));
        } elseif ($fromDate) {
            return "From " . date('M j, Y', strtotime($fromDate));
        } elseif ($toDate) {
            return "Until " . date('M j, Y', strtotime($toDate));
        }
        
        return "All time";
    }
}