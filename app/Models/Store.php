<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'is_active',
        'settings',
        'personal_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'status'
    ];

    /**
     * Get the user that owns the store.
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento: Uma loja pode ter vários domínios
    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get the store's status.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if ($this->trashed()) {
            return 'deleted';
        }

        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Scope a query to only include active stores.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include stores of a given user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Activate the store.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the store.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Update store settings.
     *
     * @param  array  $settings
     * @return bool
     */
    public function updateSettings(array $settings): bool
    {
        $currentSettings = $this->settings ?? [];
        $mergedSettings = array_merge($currentSettings, $settings);

        return $this->update(['settings' => $mergedSettings]);
    }
}