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

class ProcessKerjaPraktik implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $nim;
    public $namaMatkulKerjaPraktik;

    public function __construct($nim, $namaMatkulKerjaPraktik = 'Kerja Praktek/Magang')
    {
        $this->nim = $nim;
        $this->namaMatkulKerjaPraktik = $namaMatkulKerjaPraktik;
    }

    public function handle()
    {
        try {
            Log::info('ProcessKerjaPraktik: Memulai proses untuk NIM.', ['nim' => $this->nim]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN'),
            ])->get(env('API_URL') . '/Krs/hasilStudi', [
                'nim' => $this->nim,
            ]);

            if ($response->ok() && isset($response->json()['data'])) {
                $data = $response->json()['data'];
                Log::info('ProcessKerjaPraktik: Response API diterima.', ['response' => $data]);

                $matkul = collect($data)
                    ->flatMap(function ($semester) {
                        return $semester['detail_mk'] ?? [];
                    })
                    ->first(function ($item) {
                        Log::info('ProcessKerjaPraktik: Membandingkan nama mata kuliah.', [
                            'nama_mk_dari_api' => trim($item['nama_mk'] ?? ''),
                            'nama_mk_target' => trim($this->namaMatkulKerjaPraktik),
                        ]);

                        return isset($item['nama_mk']) &&
                            stripos(trim($item['nama_mk']), trim($this->namaMatkulKerjaPraktik)) !== false;
                    });

                if ($matkul && isset($matkul['nilai_huruf']) && $matkul['nilai_huruf'] !== null) {
                    Log::info('ProcessKerjaPraktik: Mata kuliah ditemukan dan nilai_huruf valid.', [
                        'nim' => $this->nim,
                        'matkul' => $matkul,
                    ]);

                    DB::table('nim_matkul')->insertOrIgnore([
                        'nim' => $this->nim,
                        'nama_mk' => $this->namaMatkulKerjaPraktik,
                        'semester_id' => $matkul['sem_id'] ?? null,
                        'status' => 'aktif',
                        'nilai_huruf' => $matkul['nilai_huruf'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    Log::warning('ProcessKerjaPraktik: Mata kuliah tidak ditemukan atau nilai_huruf null.', [
                        'nim' => $this->nim,
                        'matkul' => $matkul,
                    ]);
                }
            } else {
                Log::warning('ProcessKerjaPraktik: API response tidak valid.', ['nim' => $this->nim]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessKerjaPraktik: Gagal memproses NIM.', ['nim' => $this->nim, 'error' => $e->getMessage()]);
        }
    }
}
