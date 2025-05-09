<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Product extends Model
{
    
    protected $table = 'products';

    protected $fillable = [
        'id', // Incluindo o ID explicitamente
        'shopify_id',
        'store_id',
        'title',
        'body_html',
        'vendor',
        'product_type',
        'handle',
        'status',
        'tags',
        'published_at',
        'shopify_data'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function getMainImageAttribute()
    {
        return $this->images->sortBy('position')->first();
    }
}