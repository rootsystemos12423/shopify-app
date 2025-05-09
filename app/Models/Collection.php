<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'store_id',
        'title',
        'handle',
        'body_html',
        'published_at',
        'sort_order',          // New field
        'template_suffix',     // New field
        'disjunctive',         // New field
        'rules',               // New field
        'published_scope',     // New field
        'products_count',
        'image',
        'shopify_data',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'disjunctive' => 'boolean',     // Cast to boolean
        'rules' => 'array',             // Cast JSON rules to array
        'image' => 'array',
        'shopify_data' => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}