<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{

    protected $casts = [
        'properties' => 'array',
        'tax_lines' => 'array',
        'discount_allocations' => 'array',
        'price_set' => 'array',
        'total_discount_set' => 'array',
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'title',
        'quantity',
        'price',
        'grams',
        'sku',
        'variant_title',
        'fulfillment_status',
        'requires_shipping',
        'taxable',
        'gift_card',
        'name',
        'vendor',
        'properties',
        'tax_lines',
        'discount_allocations',
        'price_set',
        'total_discount_set',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}