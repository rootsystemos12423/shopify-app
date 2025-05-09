<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'price_rule_id',
        'code',
        'usage_count',
        'created_at',
        'updated_at',
        'id',
    ];

    public function priceRule()
    {
        return $this->belongsTo(PriceRule::class);
    }

    // Acessor para verificar se o código está ativo
    public function getIsActiveAttribute()
    {
        return $this->priceRule && $this->priceRule->status === 'active';
    }
}
