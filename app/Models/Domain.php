<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'domain',
        'is_primary',
        'is_verified',
        'verification_code'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
