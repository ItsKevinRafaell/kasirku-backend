<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            1 => $this,
        ];

    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Product([
            'name' => $row['name'],
            'category_id' => $row['category_id'],
            'slug' => Product::generateUniqueSlug($row['name']),
            'barcode' => $row['barcode'],
            'stock' => $row['stock'],
            'price' => $row['price'],
            'description' => $row['description'],
            'is_active' => $row['is_active'],
            'image' => $row['image'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string',
            '*.category_id' => 'required|exists:categories,id',
            '*.barcode' => 'required|string',
            '*.stock' => 'required|numeric',
            '*.price' => 'required|numeric',
            '*.description' => 'required',
            '*.is_active' => 'required|boolean',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.name.required' => 'The name field is required.',
            '*.name.string' => 'The name field must be a string.',
            '*.category_id.required' => 'The category field is required.',
            '*.category_id.exists' => 'The selected category is invalid.',
            '*.barcode.required' => 'The barcode field is required.',
            '*.barcode.string' => 'The barcode field must be a string.',
            '*.stock.required' => 'The stock field is required.',
            '*.stock.numeric' => 'The stock field must be a number.',
            '*.price.required' => 'The price field is required.',
            '*.price.numeric' => 'The price field must be a number.',
            '*.description.required' => 'The description field is required.',
            '*.is_active.required' => 'The is active field is required.',
            '*.is_active.boolean' => 'The is active field must be a boolean.',
        ];
    }
}
