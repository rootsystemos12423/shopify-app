<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'id',
        'title',
        'price',
        'compare_at_price',
        'sku',
        'barcode',
        'position',
        'inventory_policy',
        'inventory_management',
        'inventory_quantity',
        'requires_shipping',
        'taxable',
        'grams',
        'weight',
        'weight_unit',
        'option1',
        'option2',
        'option3'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'grams' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->belongsToMany(ProductImage::class);
    }
}