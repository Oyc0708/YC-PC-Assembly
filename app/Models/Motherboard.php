<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motherboard extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'manufacturer',
        'socket',
        'max_ram',
        'ram_slots',
        'ram_type',
        'form_factor',
    ];

    public function prices()
    {
        return $this->morphMany(\App\Models\ComponentPrice::class, 'component');
    }
}