<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'slug',
        'stock',
        'is_active',
        'barcode',
        'category_id',
    ];

    protected $appends = ['image_url'];
    
    public function category(): BelongsTo{
        return $this->belongsTo(Category::class);
    }

    public static function generateUniqueSlug(string $name): string{
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()){
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        return $slug;
    }

    public function getImageUrlAttribute(){
        return $this->image ? url('storage/' . $this->image) : null;
    }

    public function scopeSearch($query, $value){
        return $query->where('name', 'like', '%' . $value . '%')
            ->orWhere('description', 'like', '%' . $value . '%');
    }
}