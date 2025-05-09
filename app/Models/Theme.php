<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Theme extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'shopify_theme_id',
        'name',
        'role',
        'active',
        'version',
        'current_version_name',
        'settings',
        'shopify_data'
    ];

    protected $casts = [
        'active' => 'boolean',
        'settings' => 'array',
        'shopify_data' => 'array'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function versions()
    {
        return $this->hasMany(ThemeVersion::class);
    }

    public function assets()
    {
        return $this->hasMany(ThemeAsset::class);
    }

    public function currentVersion()
    {
        return $this->hasOne(ThemeVersion::class)->where('is_current', true);
    }

    public function getActiveThemePathAttribute()
    {
        return "themes/{$this->store_id}/{$this->current_version_name}";
    }

    public function activateVersion(string $versionName)
    {
        $this->versions()->update(['is_current' => false]);
        
        $version = $this->versions()->where('version_name', $versionName)->firstOrFail();
        $version->update(['is_current' => true]);
        
        $this->update([
            'current_version_name' => $versionName,
            'version' => $version->version_number
        ]);

        // Método atualizado para criar symlink
        $this->createVersionSymlink($versionName);
    }

    public function getTemplate($templatePath)
    {
        $basePath = "themes/{$this->store_id}/{$this->current_version_name}";
        
        // Primeiro tenta encontrar o template JSON
        $jsonPath = "{$basePath}/templates/{$templatePath}.json";
        if (Storage::disk('themes')->exists($jsonPath)) {
            return [
                'type' => 'json',
                'content' => json_decode(Storage::disk('themes')->get($jsonPath), true)
            ];
        }
        
        // Fallback para Liquid tradicional
        $liquidPath = "{$basePath}/templates/{$templatePath}.liquid";
        if (Storage::disk('themes')->exists($liquidPath)) {
            return [
                'type' => 'liquid',
                'content' => Storage::disk('themes')->get($liquidPath)
            ];
        }
        
        throw new \Exception("Template {$templatePath} not found");
    }

    public function getSectionContent($sectionName)
    {
        $path = "themes/{$this->store_id}/{$this->current_version_name}/sections/{$sectionName}.liquid";
        
        if (Storage::disk('themes')->exists($path)) {
            return Storage::disk('themes')->get($path);
        }
        
        throw new \Exception("Section {$sectionName} not found");
    }
    

    protected function createVersionSymlink(string $versionName)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Solução para Windows - copia os arquivos em vez de symlink
            $source = storage_path("app/themes/{$this->store_id}/{$versionName}");
            $dest = storage_path("app/themes/{$this->store_id}/current");
            
            if (file_exists($dest)) {
                app('files')->deleteDirectory($dest);
            }
            
            app('files')->copyDirectory($source, $dest);
        } else {
            // Solução padrão para Unix
            $target = "{$this->store_id}/{$versionName}";
            $link = "{$this->store_id}/current";
            
            if (Storage::disk('themes')->exists($link)) {
                Storage::disk('themes')->delete($link);
            }
            
            $filesystem = Storage::disk('themes')->getDriver();
            $adapter = $filesystem->getAdapter();
            $pathPrefix = $adapter->getPathPrefix();
            
            symlink($pathPrefix . $target, $pathPrefix . $link);
        }
    }

    public function createNewVersion(string $versionName, ?string $versionNumber = null, array $manifest = [])
    {
        // Define um número de versão padrão se não for fornecido
        $versionNumber = $versionNumber ?? $this->version ?? '1.0.0';
        
        if (empty($versionNumber)) {
            throw new \InvalidArgumentException("O número da versão não pode ser vazio");
        }
        
        return $this->versions()->create([
            'version_name' => $versionName,
            'version_number' => $versionNumber,
            'assets_manifest' => $manifest,
            'is_current' => false
        ]);
    }
}