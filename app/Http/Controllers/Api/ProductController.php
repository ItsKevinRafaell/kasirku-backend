<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();

        return response()->json([
            'success' => true,
            'message' => 'List Data Product',
            'data' => $products
        ]);
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function showByBarcode($barcode){
        $product = Product::where('barcode', $barcode)->first();

        if($product){
            return response()->json([
                'success' => true,
                'message' => 'Detail Data Product',
                'data' => $product
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Data Product Tidak Ditemukan',
                'data' => null
            ], 404);
        }
    }
}
