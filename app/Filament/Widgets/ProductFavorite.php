<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductFavorite extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Favorite Product';


    public function table(Table $table): Table
    {
        $productQuery = Product::query()->withCount('orderProducts')
        ->orderByDesc('order_products_count')
        ->take(10);
        return $table
            ->query( $productQuery)
            ->columns([
                 Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image'),
                    Tables\Columns\TextColumn::make('order_products_count')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5);
    }
}
