<?php

// app/Models/ThemeAsset.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ThemeAsset extends Model
{

    protected $fillable = [
        'theme_id',
        'key',
        'content_type',
        'size',
        'checksum',
        'public_url',
        'content',
        'storage_path',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    // Acessor para obter o conteúdo do arquivo
    public function getContentAttribute($value)
    {
        if ($this->storage_path) {
            return Storage::disk('themes')->get($this->storage_path);
        }
        return $value;
    }

    // Mutator para salvar o conteúdo
    public function setContentAttribute($value)
    {
        // Arquivos pequenos (< 1MB) são salvos no banco
        if (strlen($value) < 1048576) {
            $this->attributes['content'] = $value;
            $this->attributes['storage_path'] = null;
        } 
        // Arquivos grandes são salvos no filesystem
        else {
            $path = "themes/{$this->theme_id}/{$this->key}";
            Storage::disk('themes')->put($path, $value);
            $this->attributes['storage_path'] = $path;
            $this->attributes['content'] = null;
        }
        
        $this->attributes['size'] = strlen($value);
        $this->attributes['checksum'] = md5($value);
    }

    // Verifica se o asset foi modificado
    public function isModified($newContent)
    {
        return $this->checksum !== md5($newContent);
    }

    // Caminho relativo para o arquivo
    public function getRelativePathAttribute()
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $this->key);
    }

    // Extensão do arquivo
    public function getExtensionAttribute()
    {
        return pathinfo($this->key, PATHINFO_EXTENSION);
    }
}