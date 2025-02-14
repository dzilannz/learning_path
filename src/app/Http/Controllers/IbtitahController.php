<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class IbtitahController extends Controller
{
    // Upload file ibtitah mahasiswa
    public function submitProof(Request $request)
    {
        // Log akses ke fungsi
        Log::info('IbtitahController: submitProof accessed.');
    
        // Validasi input
        $request->validate([
            'nim' => 'required|string|max:50',
            'kategori' => 'required|string',
            'proof_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // Maks 2MB
        ]);
    
        $nim = $request->input('nim');
        $kategori = $request->input('kategori');
        $proofFile = $request->file('proof_file');
    
        try {
            // Simpan file ke storage
            $filePath = $proofFile->store('proof_files', 'public'); // Disimpan di storage/app/public/proof_files
            $fileUrl = Storage::url($filePath); // URL publik untuk file
    
            // Periksa apakah data sudah ada di tabel ibtitah untuk kategori tertentu
            $ibtitah = DB::table('ibtitah')
                ->where('nim', $nim)
                ->where('kategori', $kategori)
                ->first();
    
            if ($ibtitah) {
                // Jika data sudah ada, perbarui hanya untuk kategori yang dipilih
                $newStatus = $ibtitah->status === 'rejected' ? 'pending' : $ibtitah->status;
    
                DB::table('ibtitah')
                    ->where('nim', $nim)
                    ->where('kategori', $kategori)
                    ->update([
                        'file_path' => $filePath,
                        'proof_file' => $fileUrl,
                        'status' => $newStatus, // Ubah status jika diperlukan
                        'submitted_at' => empty($ibtitah->file_path) ? now() : $ibtitah->submitted_at,
                     // Tetap gunakan submitted_at sebelumnya jika sudah ada
                        'updated_at' => now(),
                    ]);
    
                // Logging hasil update
                Log::info('IbtitahController: Proof file updated in database.', [
                    'nim' => $nim,
                    'kategori' => $kategori,
                    'file_path' => $filePath,
                    'proof_file' => $fileUrl,
                    'new_status' => $newStatus,
                ]);
            } else {
                // Jika data belum ada, insert data baru untuk kategori tertentu
                DB::table('ibtitah')->insert([
                    'nim' => $nim,
                    'kategori' => $kategori,
                    'file_path' => $filePath,
                    'proof_file' => $fileUrl,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                // Logging hasil insert
                Log::info('IbtitahController: Proof file inserted into database.', [
                    'nim' => $nim,
                    'kategori' => $kategori,
                    'file_path' => $filePath,
                    'proof_file' => $fileUrl,
                ]);
            }
    
            // Logging keberhasilan umum
            Log::info('IbtitahController: Proof submitted successfully.', [
                'nim' => $nim,
                'kategori' => $kategori,
                'file_path' => $filePath,
                'proof_file' => $fileUrl,
            ]);
    
            return redirect()->route('dashboard')->with('success', 'File berhasil diunggah.');
        } catch (\Exception $e) {
            // Logging jika terjadi error
            Log::error('IbtitahController: Error submitting proof.', [
                'message' => $e->getMessage(),
            ]);
    
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat mengunggah file.']);
        }
    }
    

    
    
}
