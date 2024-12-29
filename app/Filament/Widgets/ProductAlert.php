<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductAlert extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Stock Alert';


    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()->where('stock', '<=', 10)->orderBy('stock', 'asc')
            )
            ->columns([
                 Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image'),
                 Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->color(static function ($value) {
                       if ($value <= 5) {
                           return 'danger';
                       } elseif ($value <= 10) {
                           return 'warning';
                       }
                    })
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5);
    }
}
