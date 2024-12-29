<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Livewire\Component;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

use Filament\Forms\Set;

class Pos extends Component implements HasForms
{
    use InteractsWithForms;
    public $search = '';
    public $name_customer = '';
    public $payment_methods;
    public $order_items = [];
    public $total_price;
    public $gender;
    public $payment_method_id;

    protected $listeners = ['scanResult' => 'handleScanResult'];

    public function render()
    {
        return view('livewire.pos', [
            'products' => Product::where('stock', '>', 0)
                        ->search($this->search)
                        ->paginate(12)
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
               Section::make('Form Checkout')
                    ->schema([
                        TextInput::make('name_customer')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => $this->name_customer),
                        Select::make('gender')
                            ->options([
                                'male' => 'Laki Laki',
                                'female' => 'Perempuan',
                            ]),
                        TextInput::make('total_price')
                        ->readOnly()
                        ->default(fn () => $this->total_price),
                        Select::make('payment_method_id')
                            ->options($this->payment_methods->pluck('name', 'id'))
                            ->label('Payment Method')
                            ->required()
                    ])
            ]);
    }

    public function addToOrder($productId){
        $product = Product::find($productId);
        if($product){
             if($product->stock <= 0){
                Notification::make()
                    ->title('Stok Habis')
                    ->danger()
                    ->send();
                return;
            }

            $existingItemKey = null;
            foreach($this->order_items as $key => $item){
                if($item['product_id'] == $productId){
                    $existingItemKey = $key;
                    break;
                }
            }

            if ($existingItemKey !== null){
                $this->order_items[$existingItemKey]['quantity']++;
            } else {
                $this->order_items[] = [
                    'product_id' => $productId,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => 1,
                    'image_url' => $product->image_url,
                ];
            }

            session()->put('order_items', $this->order_items);
            Notification::make()
                ->title('Produk Ditambahkan')
                ->success()
                ->send();
        }
    }

    public function loadOrderItems($orderItems){
        $this->order_items = $orderItems;
        session()->put('order_items', $this->order_items);
    }

    public function increaseQuantity($productId){
        $product = Product::find($productId);
        if(!$product){
            Notification::make()
                ->title('Produk Tidak Ditemukan')
                ->danger()
                ->send();
            return;
        }

        foreach($this->order_items as $key => $item){
            if($item['product_id'] == $productId){
                if($item['quantity'] + 1 <= $product->stock){
                    $this->order_items[$key]['quantity']++;
                } else {
                    Notification::make()
                        ->title('Stok Tidak Mencukupi')
                        ->danger()
                        ->send();
                    return;
                }
                break;
            }
        }

        session()->put('order_items', $this->order_items);
    }

    public function decreaseQuantity($productId){
        foreach($this->order_items as $key => $item){
            if($item['product_id'] == $productId){
                if($this->order_items[$key]['quantity'] > 1){
                    $this->order_items[$key]['quantity']--;
                } else {
                    unset($this->order_items[$key]);
                    $this->order_items = array_values($this->order_items);
                }
                break;
            }
        }

        session()->put('order_items', $this->order_items);
    }

    public function calculateTotal(){
        $total = 0;
        foreach($this->order_items as $item){
            $total += $item['price'] * $item['quantity'];
        }
        $this->total_price = $total;
        return $total;
    }

    public function checkout(){
        $this->validate([
            'name_customer' => 'required',
            'gender' => 'required|in:male,female',
            'payment_method_id' => 'required',
        ]);

        $payment_method_id_temp = $this->payment_method_id;

        $order = Order::create([
            'name' => $this->name_customer,
            'gender' => $this->gender,
            'total_price' => $this->calculateTotal(),
            'payment_method_id' => $payment_method_id_temp,
        ]);

        foreach($this->order_items as $item){
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'unit_price' => $item['price'],
                'quantity' => $item['quantity'],
            ]);
        }

        $this->order_items = [];
        session()->forget('order_items');

        return redirect()->to('admin/orders');
    }

    public function mount(): void
    {
        if(session()->has('order_items')){
            $this->order_items = session()->get('order_items');
        }
        $this->payment_methods = PaymentMethod::all();
        $this->form->fill([
            'payment_methods' => $this->payment_methods,
        ]);
    }

    public function handleScanResult($decodedText){
        $product = Product::where('barcode', $decodedText)->first();
            if($product){
                $this->addToOrder($product->id);
            }else{
                Notification::make()
                    ->title('Product Not Found ' . $decodedText)
                    ->danger()
                    ->send();
            }
        }
}
