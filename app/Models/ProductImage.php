<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'position',
        'alt',
        'width',
        'height',
        'src'
    ];

    protected $casts = [
        'position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class);
    }
}