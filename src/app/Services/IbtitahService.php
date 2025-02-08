<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IbtitahService
{
    public function syncIbtitahOnLogin($nim)
    {
        try {
            Log::info("Syncing ibtitah data for NIM during login.", ['nim' => $nim]);

            $categories = ['tilawah', 'ibadah', 'tahfidz']; // Kategori yang akan disinkronkan

            foreach ($categories as $kategori) {
                $existingData = DB::table('ibtitah')
                    ->where('nim', $nim)
                    ->where('kategori', $kategori)
                    ->first();

                // Jika data sudah ada dan status adalah `approved` atau `rejected`, lewati sinkronisasi
                if ($existingData && in_array($existingData->status, ['approved', 'rejected'])) {
                    Log::info("Skipping sync for kategori with non-pending status.", [
                        'nim' => $nim,
                        'kategori' => $kategori,
                        'status' => $existingData->status,
                    ]);
                    continue;
                }

                // Jika data ada dengan status `pending`, hanya perbarui `updated_at` dan JANGAN ubah `submitted_at`
                if ($existingData && $existingData->status === 'pending') {
                    DB::table('ibtitah')->where('id', $existingData->id)->update([
                        'updated_at' => now(),
                    ]);
                    Log::info("Updated existing pending ibtitah record.", [
                        'nim' => $nim,
                        'kategori' => $kategori,
                    ]);
                    continue;
                }

                // Jika data tidak ada, insert data baru dengan status `pending` dan `submitted_at` diisi
                if (!$existingData) {
                    DB::table('ibtitah')->insert([
                        'nim' => $nim,
                        'kategori' => $kategori,
                        'status' => 'pending', // Status default untuk data baru
                        'submitted_at' => null, // Tetap kosong karena belum ada unggahan
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info("Inserted new ibtitah record.", [
                        'nim' => $nim,
                        'kategori' => $kategori,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing ibtitah during login: ' . $e->getMessage(), [
                'nim' => $nim,
            ]);
        }
    }
}    

