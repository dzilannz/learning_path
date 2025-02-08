<?php
namespace App\Helpers;

class SemesterHelper
{
    /**
     * Menghitung semester ID (sem_id) saat ini berdasarkan bulan dan tahun.
     *
     * @return string
     */
    public static function getCurrentSemesterId()
    {
        $tahun = date('Y'); // Tahun saat ini
        $bulan = date('n'); // Bulan saat ini (1–12)

        // Logika untuk menentukan semester
        if ($bulan >= 9 && $bulan <= 12) {
            // Semester ganjil: September - Desember
            $semester = '1';
        } elseif ($bulan >= 2 && $bulan <= 7) {
            // Semester genap: Februari - Juli
            $semester = '2';
            $tahun--; // Semester genap milik tahun akademik sebelumnya
        } else {
            // Bulan Januari atau Agustus
            if ($bulan == 1) {
                $semester = '1'; // Januari adalah bagian dari semester ganjil
                $tahun--; // Milik tahun akademik sebelumnya
            } else { // Bulan Agustus
                $semester = '2'; // Agustus adalah bagian dari semester genap
            }
        }

        return $tahun . $semester; // Contoh: 20241 untuk ganjil 2024
    }
}
