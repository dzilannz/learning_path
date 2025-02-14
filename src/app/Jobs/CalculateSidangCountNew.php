<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateSidangCountNew implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('CalculateSidangCount: Memulai perhitungan dan sinkronisasi data sidang.');

            $kodeSidang = [
                'seminar_kp' => 'Kerja Praktek/Magang',
                'sempro' => 'Kerja Praktek/Magang',
                'kompre' => 'Komprehensif',
                'kolokium' => 'Tugas Akhir dan Seminar',
                'munaqasyah' => 'Tugas Akhir dan Seminar',
            ];

            // Ambil rentang tahun berdasarkan tahun sekarang
            $tahunSekarang = date('Y');
            $rentangTahun = range($tahunSekarang - 8, $tahunSekarang - 3); // Dinamis berdasarkan tahun sekarang
            $apiNims = DB::table('nims') // Mengambil semua mahasiswa tanpa filter status
                ->whereIn('angkatan', $rentangTahun)
                ->pluck('nim');

            foreach ($apiNims as $nim) {
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
                    $data = collect($response->json()['data']);

                    // Urutkan data berdasarkan sem_id descending
                    $sortedData = $data->sortByDesc('sem_id');

                    $sidangStatus = [
                        'seminar_kp' => false,
                        'sempro' => false,
                        'kompre' => false,
                        'kolokium' => false,
                        'munaqasyah' => false,
                    ];

                    // Ambil mata kuliah dengan sem_id terakhir
                    $mataKuliahTerakhir = $sortedData->flatMap(function ($semester) {
                        return $semester['detail_mk'] ?? [];
                    })
                    ->filter(function ($mk) {
                        return isset($mk['nilai_huruf']) && trim($mk['nilai_huruf']) !== '';
                    })
                    ->groupBy('nama_mk')
                    ->map(function ($mkGroup) {
                        return $mkGroup->sortByDesc('sem_id')->first();
                    });

                    // Update status sidang berdasarkan data terakhir
                    if ($mataKuliahTerakhir->has('Kerja Praktek/Magang')) {
                        $sidangStatus['seminar_kp'] = true;
                    }

                    if ($mataKuliahTerakhir->has('Komprehensif')) {
                        $sidangStatus['kompre'] = true;
                        $sidangStatus['sempro'] = true; // Kompre berarti juga Sempro
                    }

                    if ($mataKuliahTerakhir->has('Tugas Akhir dan Seminar')) {
                        $sidangStatus['kolokium'] = true;
                        $sidangStatus['munaqasyah'] = true;
                    }

                    // Simpan data sidang ke database
                    DB::table('sidang')->updateOrInsert(
                        ['nim' => $nim],
                        array_merge($sidangStatus, ['updated_at' => now()])
                    );
                } else {
                    Log::warning("CalculateSidangCount: Gagal mendapatkan data API untuk NIM {$nim}.");
                }
            }

            // Hitung total sidang global dan per kategori
            $totalSidang = DB::table('sidang')
                ->where(function ($query) {
                    $query->where('sempro', true)
                        ->orWhere('kompre', true)
                        ->orWhere('kolokium', true)
                        ->orWhere('munaqasyah', true);
                })
                ->count();

            $sidangPerKategori = [];
            foreach (array_keys($kodeSidang) as $kategori) {
                $sidangPerKategori[$kategori] = DB::table('sidang')->where($kategori, true)->count();
            }

            // Simpan data statistik
            DB::table('statistics')->updateOrInsert(
                ['name' => 'sidang_count'],
                ['value' => $totalSidang, 'updated_at' => now()]
            );

            foreach ($sidangPerKategori as $kategori => $count) {
                DB::table('statistics')->updateOrInsert(
                    ['name' => "sidang_{$kategori}_count"],
                    ['value' => $count, 'updated_at' => now()]
                );
            }

            Log::info("CalculateSidangCount: Data sidang berhasil dihitung.", [
                'totalSidang' => $totalSidang,
                'sidangPerKategori' => $sidangPerKategori,
            ]);
        } catch (\Exception $e) {
            Log::error("CalculateSidangCount: Gagal menghitung data sidang. Error: {$e->getMessage()}");
        }
    }
}
