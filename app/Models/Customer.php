<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{

    protected $casts = [
        'addresses' => 'array',
        'default_address' => 'array',
        'email_marketing_consent' => 'array',
        'sms_marketing_consent' => 'array',
        'tax_exemptions' => 'array',
        'total_spent' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'shopify_id',
        'admin_graphql_api_id',
        'email',
        'first_name',
        'last_name',
        'orders_count',
        'state',
        'total_spent',
        'last_order_id',
        'last_order_name',
        'note',
        'verified_email',
        'multipass_identifier',
        'tax_exempt',
        'tags',
        'currency',
        'phone',
        'addresses',
        'default_address',
        'email_marketing_consent',
        'sms_marketing_consent',
        'tax_exemptions',
        'created_at',
        'updated_at',
        'id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Acessor para nome completo
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Acessor para endereço padrão
    public function getDefaultAddressAttribute($value)
    {
        if (empty($value) && !empty($this->addresses)) {
            return collect($this->addresses)->firstWhere('default', true);
        }
        return $value;
    }
}
