<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ScriptTag extends Model
{
    use BelongsToTenant;

    protected $connection = 'tenant';

    protected $casts = [
        'cache' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'shopify_id',
        'src',
        'event',
        'display_scope',
        'cache',
        'created_at',
        'updated_at',
    ];

    // Scope para scripts globais
    public function scopeGlobal($query)
    {
        return $query->where('display_scope', 'all');
    }

    // Scope para scripts que devem ser cacheados
    public function scopeCached($query)
    {
        return $query->where('cache', true);
    }

    // Gera o HTML para inclusão no tema
    public function toHtml()
    {
        $attributes = [
            'src' => $this->src,
            $this->event => null,
        ];

        if ($this->cache) {
            $attributes['data-cache'] = 'true';
        }

        return '<script '.htmlspecialchars(implode(' ', array_map(
            fn ($key, $value) => $value === null ? $key : $key.'="'.$value.'"',
            array_keys($attributes),
            $attributes
        )).'></script>');
    }
}
