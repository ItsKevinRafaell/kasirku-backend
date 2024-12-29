<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new CategoriesExport(),
            new ProductsExport(),
        ];
    }
}
{

}

Class ProductsExport implements FromCollection, WithHeadings, WithTitle{
    public function collection()
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'name',
            'category_id',
            'price',
            'stock',
            'barcode',
            'is_active',
            'description',
            'image',
        ];
    }

    public function title(): string
    {
        return 'Products';
    }

}

Class CategoriesExport implements FromCollection, WithHeadings, WithTitle{
      /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Category::select('id', 'name')->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
        ];
    }

    public function title(): string
    {
        return 'Categories';
    }
}
