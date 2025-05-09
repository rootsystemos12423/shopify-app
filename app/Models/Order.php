<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'client_details' => 'array',
        'discount_codes' => 'array',
        'note_attributes' => 'array',
        'payment_gateway_names' => 'array',
        'tax_lines' => 'array',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'closed_at' => 'datetime',
        'current_subtotal_price_set' => 'array',
        'current_total_discounts_set' => 'array',
        'current_total_price_set' => 'array',
        'current_total_tax_set' => 'array',
    ];

    protected $fillable = [
        'admin_graphql_api_id',
        'order_number',
        'email',
        'currency',
        'current_subtotal_price',
        'current_total_discounts',
        'current_total_price',
        'current_total_tax',
        'financial_status',
        'fulfillment_status',
        'name',
        'phone',
        'presentment_currency',
        'processed_at',
        'source_name',
        'subtotal_price',
        'total_discounts',
        'total_line_items_price',
        'total_outstanding',
        'total_price',
        'total_tax',
        'total_tip_received',
        'total_weight',
        'token',
        'billing_address',
        'shipping_address',
        'client_details',
        'discount_codes',
        'note_attributes',
        'tags',
        'payment_gateway_names',
        'store_id',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fulfillments()
    {
        return $this->hasMany(Fulfillment::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}