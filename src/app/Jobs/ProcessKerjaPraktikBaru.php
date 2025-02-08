<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessKerjaPraktikBaru implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $nim;
    public $namaMatkulKerjaPraktik;

    public function __construct($nim)
    {
        $this->nim = $nim;
        $this->namaMatkulKerjaPraktik = 'Kerja Praktek/Magang';
    }

    public function handle()
    {
        try {
            Log::info('ProcessKerjaPraktikBaru: Memulai proses untuk NIM.', ['nim' => $this->nim]);
    
            // Memanggil API untuk mendapatkan data hasil studi
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN'),
            ])->get(env('API_URL') . '/Krs/hasilStudi', [
                'nim' => $this->nim,
            ]);
    
            if ($response->ok() && isset($response->json()['data'])) {
                $data = $response->json()['data'];
                Log::info('ProcessKerjaPraktikBaru: Response API diterima.', ['response' => $data]);
    
                // Iterasi data semester
                foreach ($data as $semester) {
                    $sem_id = $semester['sem_id']; // Ambil sem_id dari tingkat semester
    
                    // Cari mata kuliah di dalam detail_mk
                    $matkul = collect($semester['detail_mk'] ?? [])->first(function ($item) {
                        // Perbandingan nama mata kuliah case-insensitive dan cek nilai_huruf
                        return isset($item['nama_mk'], $item['nilai_huruf']) &&
                            strcasecmp(trim($item['nama_mk']), trim($this->namaMatkulKerjaPraktik)) === 0 &&
                            $item['nilai_huruf'] !== null;
                    });
    
                    if ($matkul) {
                        Log::info('ProcessKerjaPraktikBaru: Mata kuliah ditemukan dan nilai_huruf valid.', [
                            'nim' => $this->nim,
                            'matkul' => $matkul,
                        ]);
    
                        // Perbarui hanya kolom semester_id jika sebelumnya bernilai 0
                        DB::table('nim_matkul')
                            ->where('nim', $this->nim)
                            ->where('nama_mk', $this->namaMatkulKerjaPraktik)
                            ->where('semester_id', 0) // Hanya update jika semester_id = 0
                            ->update([
                                'semester_id' => $sem_id,
                                'updated_at' => now(),
                            ]);
    
                        break; // Hentikan pencarian jika mata kuliah ditemukan
                    }
                }
    
                if (!$matkul) {
                    Log::warning('ProcessKerjaPraktikBaru: Tidak ada mata kuliah valid ditemukan.', [
                        'nim' => $this->nim,
                    ]);
                }
            } else {
                Log::warning('ProcessKerjaPraktikBaru: API response tidak valid.', ['nim' => $this->nim]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessKerjaPraktikBaru: Gagal memproses NIM.', ['nim' => $this->nim, 'error' => $e->getMessage()]);
        }
    }    
}
