<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sidang extends Model
{
    use HasFactory;

    protected $table = 'sidang';
    protected $fillable = [
        'nim',
        'seminar_kp',
        'sempro',
        'kompre',
        'kolokium',
        'munaqasyah'
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }
}