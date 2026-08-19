<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Psu extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'manufacturer',
        'wattage',
        'efficiency',
    ];

    public function prices()
    {
        return $this->morphMany(\App\Models\ComponentPrice::class, 'component');
    }
}