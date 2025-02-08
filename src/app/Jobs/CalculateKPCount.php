<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateKPCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // You can add any necessary parameters to the constructor
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('CalculateKPCount: Memulai perhitungan dan sinkronisasi Kerja Praktek/Magang.');

            // Nama mata kuliah yang terkait
            $namaMatkulKerjaPraktik = 'Kerja Praktek/Magang';

            // Ambil semua NIM dari database lokal dengan status 'aktif'
            $apiNims = DB::table('nims')
                ->where('status', 'aktif') // Hanya NIM dengan status aktif
                ->pluck('nim');

            $nimChunks = $apiNims->chunk(10);

            foreach ($apiNims as $nim) {
                // Ambil status mahasiswa (aktif) dari tabel nims
                $statusMahasiswa = DB::table('nims')->where('nim', $nim)->value('status');

                // Ambil data hasil studi mahasiswa dari API
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('API_TOKEN'),
                ])
                ->timeout(30)
                ->retry(5, 1000)
                ->get(env('API_URL') . '/Krs/hasilStudi', [
                    'nim' => $nim,
                ]);

                if ($response->ok() && isset($response->json()['data'])) {
                    $data = $response->json()['data'];

                    // Proses setiap semester
                    foreach ($data as $semester) {
                        $sem_id = $semester['sem_id']; // Ambil sem_id dari semester

                        // Cari mata kuliah "Kerja Praktek/Magang" di detail_mk
                        $matkul = collect($semester['detail_mk'] ?? [])->first(function ($item) use ($namaMatkulKerjaPraktik) {
                            return isset($item['nama_mk'], $item['nilai_huruf']) &&
                                strcasecmp(trim($item['nama_mk']), trim($namaMatkulKerjaPraktik)) === 0 &&
                                $item['nilai_huruf'] !== null;
                        });

                        if ($matkul) {
                            Log::info('CalculateKPCount: Mata kuliah Kerja Praktek/Magang ditemukan dan nilai_huruf valid.', [
                                'nim' => $nim,
                                'matkul' => $matkul,
                            ]);

                            // Gunakan updateOrInsert untuk memastikan data disisipkan atau diperbarui
                            DB::table('nim_matkul')->updateOrInsert(
                                [
                                    'nim' => $nim,
                                    'nama_mk' => $namaMatkulKerjaPraktik,
                                ],
                                [
                                    'semester_id' => $sem_id,
                                    'nilai_huruf' => $matkul['nilai_huruf'], // Menambahkan nilai_huruf
                                    'status' => $statusMahasiswa, // Pastikan status mahasiswa dari tabel nims disalin ke nim_matkul
                                    'updated_at' => now(),
                                ]
                            );

                            break; // Hentikan pencarian setelah ditemukan
                        }
                    }

                    if (!$matkul) {
                        Log::warning('CalculateKPCount: Tidak ada mata kuliah valid ditemukan untuk NIM.', [
                            'nim' => $nim,
                        ]);
                    }
                } else {
                    Log::warning("CalculateKPCount: Gagal mendapatkan data API untuk NIM {$nim}.");
                }
            }

            // Hitung total data Kerja Praktek/Magang yang memiliki nilai_huruf
            $kpCount = DB::table('nim_matkul')
                ->join('nims', 'nim_matkul.nim', '=', 'nims.nim')
                ->where('nim_matkul.nama_mk', 'Kerja Praktek/Magang')
                ->whereNotNull('nim_matkul.nilai_huruf') // Hanya yang memiliki nilai_huruf
                ->count();

            // Simpan total data ke tabel statistik
            DB::table('statistics')->updateOrInsert(
                ['name' => 'kp_count'], // Kolom unik
                ['value' => $kpCount, 'updated_at' => now()]
            );

            Log::info("CalculateKPCount: Total Kerja Praktek/Magang disinkronkan dan dihitung: {$kpCount}");
        } catch (\Exception $e) {
            Log::error("CalculateKPCount: Gagal menghitung dan menyinkronkan Kerja Praktek/Magang. Error: {$e->getMessage()}");
        }
    }
}
