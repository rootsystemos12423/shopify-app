<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeVersion extends Model
{
    protected $fillable = [
        'theme_id',
        'version_name',
        'version_number',
        'assets_manifest',
        'notes',
        'is_current'
    ];

    protected $casts = [
        'assets_manifest' => 'array',
        'is_current' => 'boolean'
    ];

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function getAssetPath(string $assetKey)
    {
        return "themes/{$this->theme_id}/{$this->version_name}/{$assetKey}";
    }

    public function getManifestCountAttribute()
    {
        return count($this->assets_manifest ?? []);
    }
}