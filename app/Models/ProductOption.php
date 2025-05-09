<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $table = 'product_options';

    protected $fillable = [
        'product_id',
        'name',
        'position',
        'values',
        'id',
    ];

    protected $casts = [
        'values' => 'array',
        'position' => 'integer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}