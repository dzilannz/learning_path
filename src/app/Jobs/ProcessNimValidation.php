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

class ProcessNimValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $nim;
    public $currentSemId;

    /**
     * Create a new job instance.
     *
     * @param string $nim
     * @param string $currentSemId
     */
    public function __construct($nim, $currentSemId)
    {
        $this->nim = $nim;
        $this->currentSemId = $currentSemId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('ProcessNimValidation: Memulai validasi NIM.', ['nim' => $this->nim]);

            // Panggil API untuk mendapatkan data KRS mahasiswa
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN'),
            ])->get(env('API_URL') . '/Krs/hasilStudi', [
                'nim' => $this->nim,
            ]);

            $data = $response->json();

            if ($response->ok() && isset($data['data'])) {
                // Ambil `sem_id` terbaru dari data API
                $latestSemId = collect($data['data'])->max('sem_id');

                // Validasi mahasiswa aktif berdasarkan sem_id terbaru
                $status = $latestSemId == $this->currentSemId ? 'aktif' : 'tidak aktif';

                // Update status mahasiswa di database
                DB::table('nims')->where('nim', $this->nim)->update(['status' => $status]);

                Log::info('ProcessNimValidation: NIM berhasil divalidasi.', [
                    'nim' => $this->nim,
                    'latestSemId' => $latestSemId,
                    'status' => $status,
                ]);
            } else {
                Log::warning('ProcessNimValidation: Data tidak ditemukan untuk NIM.', [
                    'nim' => $this->nim,
                    'response' => $data,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessNimValidation: Gagal memvalidasi NIM.', [
                'nim' => $this->nim,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}

