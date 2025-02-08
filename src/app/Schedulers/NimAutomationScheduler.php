<?php

namespace App\Schedulers;

use Illuminate\Console\Scheduling\Schedule;
use App\Helpers\NimHelper;
use App\Jobs\CalculateIbtitahCount;
use App\Jobs\CalculateSidangCount;
use Illuminate\Support\Facades\Http;

class NimAutomationScheduler
{
    public function __invoke(Schedule $schedule)
    {
        // Generate NIM baru pada awal tahun ajaran
        $schedule->call(function () {
            $this->generateNewNim();
        })->yearlyOn(9, 1, '00:00'); // Setiap 1 Juli

        // Hapus NIM lama
        $schedule->call(function () {
            $this->deleteOldNim();
        })->yearlyOn(9, 1, '00:05'); // 5 menit setelah generate

        // Validasi status mahasiswa aktif dan pembaruan nim_matkul
        $schedule->call(function () {
            $this->updateSemesterData();
        })->cron('0 1 1 3,9 *'); // Setiap 1 Maret dan 1 September pukul 01:00

        // Jalankan job CalculateIbtitahCount untuk perhitungan Ibtitah
        $schedule->job(new CalculateIbtitahCount())
            ->cron('0 2 1 3,9 *');
        
        $schedule->job(new CalculateSidangCount())
            ->cron('0 3 1 3,9 *');
    }

    protected function generateNewNim()
    {
        $tahunBaru = date('Y');
        $kodeUniversitas = '1';
        $kodeProdi = '705';
        $maxMahasiswa = 150;

        $daftarNim = NimHelper::generateNimList($tahunBaru, $kodeUniversitas, $kodeProdi, $maxMahasiswa);

        foreach ($daftarNim as $nim) {
            \DB::table('nims')->insertOrIgnore(['nim' => $nim, 'angkatan' => $tahunBaru]);
        }
    }

    protected function deleteOldNim()
    {
        $tahunBatas = date('Y') - 8;
        \DB::table('nims')->where('angkatan', '<', $tahunBatas)->delete();
    }

    protected function updateSemesterData()
    {
        $currentMonth = date('n'); // Bulan saat ini (1-12)
        $currentYear = date('Y'); // Tahun saat ini (contoh: 2025)
    
        // Tentukan semester ID berdasarkan bulan
        if ($currentMonth >= 3 && $currentMonth <= 8) {
            // Semester genap (Maret - Agustus)
            $currentSemId = $currentYear . '2';
        } elseif ($currentMonth >= 9) {
            // Semester ganjil (September - Desember)
            $currentSemId = $currentYear . '1';
        } else {
            // Semester ganjil (Januari - Februari)
            $currentSemId = ($currentYear - 1) . '1';
        }
    
        // Validasi status mahasiswa
        $this->validateActiveStudents($currentSemId);
    
        // Perbarui data nim_matkul
        $this->updateNimMatkul($currentSemId);
    }
    

    protected function validateActiveStudents($currentSemId)
    {
        $nims = \DB::table('nims')->get(); // Ambil semua NIM dari database

        foreach ($nims as $nim) {
            try {
                // Panggil API untuk mendapatkan data KRS mahasiswa
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('API_TOKEN'),
                ])->get(env('API_URL') . '/Krs/hasilStudi', [
                    'nim' => $nim->nim,
                ]);

                // Periksa respons API
                if (!$response->ok() || !isset($response->json()['data'])) {
                    \Log::warning("Data KRS tidak ditemukan untuk NIM {$nim->nim}");
                    continue;
                }

                // Ambil sem_id terbaru dari data API
                $latestSemId = collect($response->json()['data'])->max('sem_id');

                // Validasi status berdasarkan sem_id
                $status = $latestSemId == $currentSemId ? 'aktif' : 'tidak aktif';

                // Perbarui status mahasiswa di database
                \DB::table('nims')->where('nim', $nim->nim)->update(['status' => $status]);
            } catch (\Exception $e) {
                \Log::error("Gagal memvalidasi NIM {$nim->nim}: {$e->getMessage()}");
            }
        }
    }

    protected function updateNimMatkul($currentSemId)
    {
        $nims = \DB::table('nims')->where('status', 'aktif')->get(); // Ambil NIM dengan status aktif
    
        foreach ($nims as $nim) {
            try {
                // Panggil API untuk mendapatkan data KRS mahasiswa
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('API_TOKEN'),
                ])->get(env('API_URL') . '/Krs/hasilStudi', [
                    'nim' => $nim->nim,
                ]);
    
                // Periksa respons API
                if (!$response->ok() || !isset($response->json()['data'])) {
                    \Log::warning("Data KRS tidak ditemukan untuk NIM {$nim->nim}");
                    continue;
                }
    
                // Iterasi data API
                foreach ($response->json()['data'] as $semester) {
                    $sem_id = $semester['sem_id']; // Ambil sem_id dari elemen utama
    
                    foreach ($semester['detail_mk'] ?? [] as $matkul) {
                        // Periksa apakah data lengkap
                        if (isset($matkul['nama_mk'], $matkul['nilai_huruf'])) {
                            \DB::table('nim_matkul')->insertOrIgnore([
                                'nim' => $nim->nim,
                                'kode_mk' => $matkul['kode_mk'] ?? null,
                                'nama_mk' => $matkul['nama_mk'],
                                'semester_id' => $sem_id, // Gunakan sem_id dari elemen utama
                                'status' => 'aktif',
                                'nilai_huruf' => $matkul['nilai_huruf'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Gagal memperbarui nim_matkul untuk NIM {$nim->nim}: {$e->getMessage()}");
            }
        }
    }    
}
