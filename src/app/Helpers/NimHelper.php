<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class NimHelper
{
    /**
     * Generate daftar NIM berdasarkan rentang tahun.
     *
     * @param int $tahunMulai
     * @param int|null $tahunAkhir
     * @param string $kodeUniversitas
     * @param string $kodeProdi
     * @param int $maxMahasiswa
     * @return array
     */

  
        public static function generateNimList($tahunMulai, $tahunAkhir = null, $kodeUniversitas = '1', $kodeProdi = '705', $maxMahasiswa = 150)
        {
            // Default tahun akhir adalah tahun saat ini
            $tahunAkhir = $tahunAkhir ?? date('Y');
    
            $daftarNim = [];
    
            for ($tahun = $tahunMulai; $tahun <= $tahunAkhir; $tahun++) {
                $angkatan = substr($tahun, 2, 2); // Ambil 2 digit terakhir dari tahun
    
                for ($i = 1; $i <= $maxMahasiswa; $i++) {
                    $nomorUrut = str_pad($i, 4, '0', STR_PAD_LEFT);
                    $nim = $kodeUniversitas . $angkatan . $kodeProdi . $nomorUrut;
                    $daftarNim[] = $nim;
                }
    
                // Log setiap tahun yang dihasilkan
                Log::info('NimHelper: Daftar NIM berhasil dihasilkan untuk tahun.', [
                    'tahun' => $tahun,
                    'total_nim' => count($daftarNim),
                    'contoh_nim' => array_slice($daftarNim, 0, 5), // Contoh 5 NIM pertama
                ]);
            }
    
            return $daftarNim;
        }
    }
  