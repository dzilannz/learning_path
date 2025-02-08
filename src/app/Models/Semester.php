<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semester';
    protected $fillable = ['nama'];

    public function mahasiswa()
    {
        return $this->belongsToMany(Mahasiswa::class, 'semester_mahasiswa', 'semester_id', 'mahasiswa_id');
    }

    public function matkul()
{
    return $this->hasMany(Matkul::class, 'semester_id', 'id');
}
}