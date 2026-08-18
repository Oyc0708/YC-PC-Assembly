<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Build extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cpu_id',
        'cooler_id',
        'mobo_id',
        'ram_id',
        'gpu_id',
        'psu_id',
        'case_id',
        'total_cost',
        'overall_score',
    ];

    // Relationship to the User who created the build
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Hardware Relationships
    public function cpu()
    {
        return $this->belongsTo(Cpu::class, 'cpu_id', 'id');
    }

    public function cooler()
    {
        return $this->belongsTo(Cooler::class, 'cooler_id', 'id');
    }

    public function motherboard()
    {
        return $this->belongsTo(Motherboard::class, 'mobo_id', 'id');
    }

    public function ram()
    {
        return $this->belongsTo(Ram::class, 'ram_id', 'id');
    }

    public function gpu()
    {
        return $this->belongsTo(Gpu::class, 'gpu_id', 'id');
    }

    public function psu()
    {
        return $this->belongsTo(Psu::class, 'psu_id', 'id');
    }

    public function pcCase()
    {
        return $this->belongsTo(PcCase::class, 'case_id', 'id');
    }
}