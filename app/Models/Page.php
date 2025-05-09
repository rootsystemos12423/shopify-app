<?php

// app/Models/Page.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Page extends Model
{
    use BelongsToTenant;

    protected $connection = 'tenant';

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'shopify_id',
        'admin_graphql_api_id',
        'title',
        'handle',
        'body_html',
        'author',
        'template_suffix',
        'published_at',
        'created_at',
        'updated_at',
    ];

    // Acessor para verificar se está publicado
    public function getIsPublishedAttribute()
    {
        return !is_null($this->published_at);
    }

    // Acessor para URL amigável
    public function getUrlAttribute()
    {
        return route('page.show', $this->handle);
    }

    // Scope para páginas publicadas
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    // Scope para buscar por autor
    public function scopeByAuthor($query, $author)
    {
        return $query->where('author', $author);
    }
}