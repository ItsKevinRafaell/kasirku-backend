<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(){
        $orders = Order::with('orderProducts', 'paymentMethod')->get();
        $orders->transform(function ($order) {
            $order->payment_method = $order->paymentMethod->name;
            $order->orderProducts->transform(function ($item){
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'product_name' => $item->product->name,
                ];
            });
            return $order;
        });
        return response()->json([
            'message' => 'Order List',
            'data' => $orders,
            'success' => true
        ]);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'nullable|string',
            'gender' => 'nullable|string',
            'phone' => 'nullable|string',
            'birthday' => 'nullable|date',
            'phone' => 'nullable|string',
            'total_price' => 'required|numeric',
            'notes' => 'nullable|string',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:1',
        ]);

         if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        foreach($request->items as $item){
            $product = Product::find($item['product_id']);
            if(!$product || $product->stock < $item['quantity']){
                return response()->json([
                    'message' => 'Produk' . $product->name . 'tidak ditemukan atau stok tidak cukup',
                    'success' => false
                ], 422);
            }
        }

        $order = Order::create($request->only([
            'name',
            'email',
            'gender',
            'phone',
            'birthday',
            'total_price',
            'notes',
            'payment_method_id',
            'paid_amount',
            'change_amount',
        ]));

        foreach($request->items as $item){
            $order->orderProducts()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return response()->json([
            'message' => 'Order Created',
            'data' => $order,
            'success' => true
        ], 200);
    }
}
