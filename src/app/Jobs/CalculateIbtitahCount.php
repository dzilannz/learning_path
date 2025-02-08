<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateIbtitahCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('CalculateIbtitahCount: Memulai perhitungan dan sinkronisasi Ibtitah.');

            // Daftar kategori Ibtitah dan nama mata kuliah yang terkait
            $categories = [
                'tilawah' => 'Praktek Tilawah',
                'ibadah' => 'Praktek Ibadah',
                'tahfidz' => 'Tahfidz',
            ];

            // Ambil semua NIM dari database lokal dengan status 'aktif'
            $apiNims = DB::table('nims')
                ->where('status', 'aktif') // Hanya NIM dengan status aktif
                ->pluck('nim');

            $nimChunks = $apiNims->chunk(10);

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
                    $data = $response->json()['data'];

                    // Proses setiap kategori Ibtitah
                    foreach ($categories as $kategori => $namaMk) {
                        // Cari mata kuliah berdasarkan nama_mk
                        $matkul = collect($data)->flatMap(function ($semester) {
                            return $semester['detail_mk'] ?? [];
                        })->firstWhere('nama_mk', $namaMk);

                        // Ambil data saat ini dari tabel `ibtitah`
                        $currentData = DB::table('ibtitah')
                            ->where('nim', $nim)
                            ->where('kategori', $kategori)
                            ->first();

                        // Tentukan status
                        $status = $currentData && in_array($currentData->status, ['approved', 'rejected'])
                            ? $currentData->status
                            : ($matkul && isset($matkul['nilai_huruf']) && $matkul['nilai_huruf'] !== null ? 'approved' : 'pending');

                        // Update atau insert data ke tabel `ibtitah`
                        DB::table('ibtitah')->updateOrInsert(
                            [
                                'nim' => $nim,
                                'kategori' => $kategori,
                            ],
                            [
                                'proof_file' => $currentData->proof_file ?? null,
                                'status' => $status,
                                'submitted_at' => $currentData->submitted_at ?? null,
                                'approved_at' => $status === 'approved' ? now() : null,
                                'created_at' => $currentData->created_at ?? now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                } else {
                    Log::warning("CalculateIbtitahCount: Gagal mendapatkan data API untuk NIM {$nim}.");
                }
            }

            // Hitung total data Ibtitah yang statusnya 'approved' dari mahasiswa aktif
            $ibtitahLocal = DB::table('ibtitah')
                ->join('nims', 'ibtitah.nim', '=', 'nims.nim')
                ->where('nims.status', 'aktif') // Hanya mahasiswa dengan status aktif
                ->where('ibtitah.status', 'approved') // Hanya yang approved
                ->count();

            // Simpan total data ke tabel statistik
            DB::table('statistics')->updateOrInsert(
                ['name' => 'ibtitah_count'], // Kolom unik
                ['value' => $ibtitahLocal, 'updated_at' => now()]
            );

            Log::info("CalculateIbtitahCount: Total Ibtitah disinkronkan dan dihitung: {$ibtitahLocal}");
        } catch (\Exception $e) {
            Log::error("CalculateIbtitahCount: Gagal menghitung dan menyinkronkan Ibtitah. Error: {$e->getMessage()}");
        }
    }
}
