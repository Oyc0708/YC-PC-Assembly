<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gpu extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'manufacturer',
        'vram_gb',
        'tdp',
        'length_mm',
        'score',
    ];

    public function prices()
    {
        return $this->morphMany(\App\Models\ComponentPrice::class, 'component');
    }
}