<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PcCase extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'manufacturer',
        'max_gpu_length_mm',
        'form_factor',
    ];

    public function prices()
    {
        return $this->morphMany(\App\Models\ComponentPrice::class, 'component');
    }
}