<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreUser extends Model
{

    protected $table = 'store_users'; // Evita conflito com users do Laravel

    protected $casts = [
        'account_owner' => 'boolean',
        'receive_announcements' => 'boolean',
        'tfa_enabled' => 'boolean',
        'permissions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'store_id',
        'admin_graphql_api_id',
        'first_name',
        'last_name',
        'email',
        'url',
        'im',
        'screen_name',
        'phone',
        'bio',
        'account_owner',
        'receive_announcements',
        'locale',
        'user_type',
        'tfa_enabled',
        'permissions',
        'created_at',
        'updated_at',
    ];

    // Acessor para nome completo
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Verifica se o usuário tem uma permissão específica
    public function hasPermission($permission)
    {
        if ($this->account_owner) {
            return true; // Donos da conta têm todas as permissões
        }

        return in_array($permission, $this->permissions ?? []);
    }

    // Verifica se o usuário tem qualquer uma das permissões fornecidas
    public function hasAnyPermission(array $permissions)
    {
        if ($this->account_owner) {
            return true;
        }

        return count(array_intersect($permissions, $this->permissions ?? [])) > 0;
    }

    // Scope para usuários com permissão específica
    public function scopeWithPermission($query, $permission)
    {
        return $query->where('account_owner', true)
            ->orWhereJsonContains('permissions', $permission);
    }
}