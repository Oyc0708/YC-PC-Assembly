<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedBuild extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Define relationships to fetch the actual parts later
    public function cpu() { return $this->belongsTo(Cpu::class, 'cpu_id'); }
    public function cooler() { return $this->belongsTo(Cooler::class, 'cooler_id'); }
    public function mobo() { return $this->belongsTo(Motherboard::class, 'mobo_id'); }
    public function ram() { return $this->belongsTo(Ram::class, 'ram_id'); }
    public function gpu() { return $this->belongsTo(Gpu::class, 'gpu_id'); }
    public function psu() { return $this->belongsTo(Psu::class, 'psu_id'); }
    public function pcCase() { return $this->belongsTo(PcCase::class, 'case_id'); }
}