<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentPrice extends Model
{
    protected $fillable = ['component_type', 'component_id', 'vendor', 'price', 'url', 'in_stock'];

    public function component()
    {
        return $this->morphTo();
    }
}