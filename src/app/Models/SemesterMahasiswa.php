<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemesterMahasiswa extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'semester_mahasiswa';

    /**
     * Kolom yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'nim',
        'id_semester',
        'semester_id',
        'sks_diambil',
    ];

    /**
     * Relasi ke tabel semester.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * Relasi ke tabel mahasiswa (diasumsikan ada tabel mahasiswa).
     */
}
