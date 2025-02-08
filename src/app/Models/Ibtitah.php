<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ibtitah extends Model
{
    use HasFactory;

    protected $table = 'ibtitah';

    /**
     * Kolom yang dapat diisi secara mass-assignment.
     */
    protected $fillable = [
        'nim',
        'status',
        'proof_file',
        'file_path',
        'kategori',
        'file_diupload_admin',
        'approved_at',
    ];

    /**
     * Kolom bertipe tanggal.
     */
    protected $dates = [
        'submitted_at',
        'file_diupload_admin',
        'approved_at',
    ];

    /**
     * Relasi dengan tabel Mahasiswa.
     */
   
}
