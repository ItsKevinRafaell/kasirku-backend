<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $productCount = Product::count();
        $orderCount = Order::count();
        $omsetCount = Order::sum('total_price');
        $expansesCount = Expense::sum('amount');
        return [
            Stat::make('Products', $productCount),
            Stat::make('Orders', $orderCount),
            Stat::make('Omset', 'Rp.'.number_format($omsetCount, 0, ',', '.')),
            Stat::make('Expanses', 'Rp.' . number_format($expansesCount, 0, ',', '.')),
        ];
    }
}
