<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shopify extends Model
{
    use SoftDeletes;

    protected $table = 'shopify_integrations';

    protected $fillable = [
        'store_id',
        'shopify_domain',
        'api_key',
        'api_secret',
        'admin_token',
        'webhook_secret',
        'is_active',
        'last_sync_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
