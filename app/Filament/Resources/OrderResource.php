<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Utama')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                            Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ])
                            ->required(),
                        ])
                    ]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Info Tambahan')
                        ->schema([
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255)
                                ->default(null),
                            
                            Forms\Components\DatePicker::make('birthday'),
                        ])
                    ]),

                Forms\Components\Section::make('Produk dipesan')->schema([
                    self::getItemsRepeater(),
                ]),

                 Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pesanan Akhir')
                        ->schema([
                            Forms\Components\TextInput::make('total_price')
                                ->required()
                                ->numeric(),
                        
                            Forms\Components\Textarea::make('note')
                                ->columnSpanFull(),
                        ])
                    ]),

                 Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Pembayaran')
                        ->schema([
                            Forms\Components\Select::make('payment_method_id')
                                ->relationship('paymentMethod', 'name')
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $paymentMethod = PaymentMethod::find($state);
                                    $set('is_cash', $paymentMethod->is_cash ?? false);

                                    if(!$paymentMethod->is_cash){
                                        $set('paid_amount', $get('total_price'));
                                        $set('change_amount', 0);
                                    }
                                })
                                ->afterStateHydrated(function($state, Set $set, Get $get){
                                    $paymentMethod = PaymentMethod::find($state);
                                    if(!$paymentMethod?->is_cash){
                                        $set('paid_amount', $get('total_price'));
                                        $set('change_amount', 0);
                                    }
                                    $set('is_cash', $paymentMethod->is_cash ?? false);
                                })
                                ->reactive()
                                ->default(null),
                            Forms\Components\Hidden::make('is_cash')
                                ->dehydrated(),
                            Forms\Components\TextInput::make('paid_amount')
                                ->numeric()
                                ->reactive()
                                ->label('Dibayar')
                                ->readOnly(fn (Get $get) => $get('is_cash') == false)
                                ->afterStateUpdated(function ($state, Set $set, Get $get){
                                    self::updateExchangePaid($get, $set);
                                })
                                ->default(null),
                            Forms\Components\TextInput::make('change_amount')
                                ->numeric()
                                ->readOnly()
                                ->label('Kembalian')
                                ->default(null),
                        ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): Repeater{
        return Forms\Components\Repeater::make('orderProducts')
            ->relationship()
            ->live()
            ->columns([
                'md' => 10,
            ])
            ->afterStateUpdated(function (Get $get, Set $set){
                self::updateTotalPrice($get, $set);
            }) 
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->options(Product::query()->where('stock', '>', 0)->pluck('name', 'id'))
                    ->afterStateUpdated(function ($state, Set $set, Get $get){
                        $product = Product::find($state);
                        $set('unit_price', $product->price ?? 0);
                        $set('stock', $product->stock ?? 0);
                        $quantity = $get('quantity') ?? 1;
                        $stock = $get('stock');
                        
                        self::updateTotalPrice($get, $set);
                    })
                    ->afterStateHydrated(function($state, Get $get, Set $set){
                        $product = Product::find($state);
                        $set('unit_price', $product->price ?? 0);
                        $set('stock', $product->stock ?? 0);
                    })
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()  
                    ->columnSpan([
                        'md' => 5
                    ])

                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->minValue(1)
                    ->columnSpan([
                        'md' => 1
                    ])
                    ->afterStateUpdated(function ($state, Set $set, Get $get){
                        $stock = $get('stock');
                        if($state > $stock){
                            $set('quantity', $stock);
                            Notification::make()
                                ->warning()   
                                ->title('Stock tidak mencukupi')
                                ->send();
                        }

                        self::updateTotalPrice($get, $set);
                    })
                    ->numeric(),
                Forms\Components\TextInput::make('stock')
                    ->required()
                      ->columnSpan([
                        'md' => 1
                    ])
                    ->numeric(),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->prefix('Rp')
                    ->columnSpan([
                        'md' => 2
                    ])
                    ->numeric(),
            ]);
    }

    protected static function updateTotalPrice(Get $get, Set $set): void{
       $selectedProducts = collect($get('orderProducts'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

       $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');
       $total = $selectedProducts->reduce(function ($total, $product) use ($prices){
        return $total + ($prices[$product['product_id']] * $product['quantity']);
       }, 0);

       $set('total_price', $total);
    }

    protected static function updateExchangePaid(Get $get, Set $set): void{
        $paidAmount = (int) $get('paid_amount') ?? 0;
        $totalPrice = (int) $get('total_price') ?? 0;
        $exchangePaid = $paidAmount - $totalPrice;
        $set('change_amount', $exchangePaid);
    }
}
