<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cpu extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'manufacturer', 'cores', 'socket', 'tdp', 'base_clock',
    ];

    public function prices()
    {
        return $this->morphMany(\App\Models\ComponentPrice::class, 'component');
    }
}
