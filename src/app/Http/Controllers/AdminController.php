<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\CalculateIbtitahCount;
use App\Models\Semester;


class AdminController extends Controller
{
    // Menampilkan semua file yang di-submit oleh mahasiswa
    public function showAllFiles(Request $request)
    {
        try {
            // Default sort is by the most recent submission date
            $statusFilter = $request->get('status', 'all');
            $nimSearch = $request->get('nim', ''); 
    
            $query = DB::table('ibtitah')
                ->whereNotNull('file_path')
                ->where('file_path', '!=', '');
    
            // Apply filter by status if selected
            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }
    
            // Apply search filter by NIM if entered
            if (!empty($nimSearch)) {
                $query->where('nim', 'like', '%' . $nimSearch . '%');
            }
    
            // Sort by the most recent submission date
            $submittedFiles = $query->orderBy('submitted_at', 'desc')->paginate(5);
    
            // Log data for debugging
            Log::info('Fetched submitted files with pagination:', ['count' => $submittedFiles->count()]);
    
            return view('admin.profile', [
                'submittedFiles' => $submittedFiles, 
                'statusFilter' => $statusFilter,
                'nimSearch' => $nimSearch // Pass the NIM search value to the view
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching submitted files: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat mengambil data file.']);
        }
    }
    




    // Approve file by admin
    public function approve($id)
    {
        try {
            DB::table('ibtitah')->where('id', $id)->update([
                'status' => 'approved',
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

            CalculateIbtitahCount::dispatch()->onQueue('ibtitah');

            Log::info("File with ID $id approved.");
            return redirect()->back()->with('success', 'File berhasil di-approve.');
        } catch (\Exception $e) {
            Log::error("Error approving file: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat meng-approve file.']);
        }
    }

    // Reject file
    public function reject($id)
    {
        try {
            $affected = DB::table('ibtitah')->where('id', $id)->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);
    
            Log::info("File with ID $id rejected. Rows affected: $affected");
    
            return redirect()->back()->with('success', 'File berhasil ditolak.');
        } catch (\Exception $e) {
            Log::error("Error rejecting file: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menolak file.']);
        }
    }

    // Hapus file
    public function delete($id)
    {
        try {
            $file = DB::table('ibtitah')->where('id', $id)->first();
            if ($file && $file->proof_file) {
                Storage::delete($file->proof_file); // Hapus file dari storage
            }

            DB::table('ibtitah')->where('id', $id)->delete();
            Log::info("File with ID $id deleted.");
            return redirect()->back()->with('success', 'File berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error("Error deleting file: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus file.']);
        }
    }


    public function uploadFile(Request $request)
    {
        $request->validate([
            'nim' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            Log::info('UploadFile: Validating request input.', [
                'nim' => $request->nim,
                'nama' => $request->nama,
                'kategori' => $request->kategori,
            ]);

            // Cek NIM di tabel ibtitah
            Log::info('UploadFile: Checking NIM in local ibtitah table.', ['nim' => $request->nim]);
            $ibtitah = DB::table('ibtitah')->where('nim', $request->nim)->where('kategori', $request->kategori)->first();

            // Simpan file yang diunggah admin
            Log::info('UploadFile: Storing file.', ['file_name' => $request->file('file')->getClientOriginalName()]);
            $filePath = $request->file('file')->store('file_path', 'public');

            if (!$ibtitah) {
                // Jika NIM tidak ada di ibtitah, tambahkan entri baru
                Log::info("NIM not found in ibtitah. Adding new entry with status approved.", ['nim' => $request->nim]);

                DB::table('ibtitah')->insert([
                    'nim' => $request->nim,
                    'nama' => $request->nama,
                    'kategori' => $request->kategori,
                    'file_path' => $filePath,
                    'status' => 'approved', // Langsung set ke approved
                    'file_diupload_admin' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return redirect()->back()->with('success', 'File berhasil diunggah dan status langsung approved.');
            }

            // Jika NIM sudah ada, update status ke approved
            Log::info("NIM found in ibtitah. Updating status to approved.", ['nim' => $request->nim]);
            DB::table('ibtitah')->where('id', $ibtitah->id)->update([
                'file_path' => $filePath,
                'status' => 'approved', // Update status ke approved
                'file_diupload_admin' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'File berhasil diunggah dan status diperbarui menjadi approved.');
        } catch (\Exception $e) {
            Log::error('UploadFile: Error uploading file or updating status.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat mengunggah file.']);
        }
    }

    public function profile()
    {
    $submittedFiles = SubmittedFile::paginate(10); // Data paginasi
    return view('admin.profile', compact('submittedFiles'));
    }

    public function search(Request $request)
    {
        $nim = $request->get('nim');

        // Log input NIM
        Log::info('AdminController: Search initiated', ['input_nim' => $nim]);

        // Validasi input NIM
        if (!$nim) {
            Log::warning('AdminController: NIM not provided');
            return redirect()->route('admin.search')->withErrors(['error' => 'Harap masukkan NIM untuk pencarian.']);
        }

        try {
            // Ambil data mahasiswa dari API
            $API_TOKEN = env('API_TOKEN');
            $responseMahasiswa = Http::withHeaders([
                'Authorization' => 'Bearer ' . $API_TOKEN,
            ])->get(env('API_URL') . '/Api/ProfileKalam', [
                'nim' => $nim,
            ]);

            if (!$responseMahasiswa->ok() || !$responseMahasiswa->json()['status']) {
                return back()->withErrors(['error' => 'Gagal mengambil data mahasiswa.']);
            }

            $mahasiswa = $responseMahasiswa->json()['data'];
            Log::info('AdminController: Mahasiswa data fetched', ['mahasiswa' => $mahasiswa]);

            // Ambil data KHS dari API
            $khsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $API_TOKEN,
            ])->get(env('API_URL') . '/Krs/hasilStudi', [
                'nim' => $nim,
            ]);

            $khsData = $khsResponse->ok() ? collect($khsResponse->json()['data']) : null;
            Log::info('AdminController: KHS data fetched', ['khsData' => $khsData]);

            // Sync Semester Data
            $semesterMapping = [];
            $takenSemesters = [];
            $semesterCounter = 1; // Counter manual untuk nama semester
            $totalSKS = 0;

            foreach ($khsData as $semesterData) {
                // Skip jika SKS diambil null
                if (
                    (is_null($semesterData['ip']) || floatval($semesterData['ip']) == 0.00) && 
                    (is_null($semesterData['sks_diambil']) || intval($semesterData['sks_diambil']) == 0)
                ) {
                    continue; // Lewati semester ini jika memenuhi kondisi
                }

                // Batasi hingga semester ke-8
              

                $semesterName = 'Semester ' . $semesterCounter; // Gunakan counter manual untuk nama semester
                $semesterCounter++; // Increment setelah semester valid diproses

                // Simpan atau update ke tabel 'semester'
                $semester = Semester::updateOrCreate(
                    ['nama' => $semesterName],
                    ['created_at' => now(), 'updated_at' => now()]
                );

                // Mapping sem_id API ke id lokal
                $semesterMapping[$semesterData['sem_id']] = $semester->id;

                // Masukkan semester valid ke takenSemesters
                $takenSemesters[] = [
                    'semester_id' => $semester->id,
                    'nama' => $semesterName,
                    'total_sks' => $semesterData['sks_diambil'],
                    'ip' => $semesterData['ip'] ?? null, // Tambahkan IP
                    'ipk' => $semesterData['ipk'] ?? null, // Tambahkan IPK
                ];

                $totalSKS += $semesterData['sks_diambil']; // Update total SKS
            }

            // Tambahkan placeholder untuk semester kosong agar selalu ada 8 semester
            while (count($takenSemesters) < 8) {
                $semesterName = 'Semester ' . (count($takenSemesters) + 1);
                $takenSemesters[] = [
                    'semester_id' => null,
                    'nama' => $semesterName,
                    'total_sks' => 0,
                    'ip' => null,
                    'ipk' => null,
                ];
            }

            // Ambil data Ibtitah dari database lokal
            $ibtitahData = DB::table('ibtitah')->where('nim', $nim)->get();
            Log::info('AdminController: Ibtitah data fetched', ['ibtitahData' => $ibtitahData]);

            // Ambil data Sidang dari database lokal
            $sidangData = DB::table('sidang')->where('nim', $nim)->first();
            Log::info('AdminController: Sidang data fetched', ['sidangData' => $sidangData]);

            // Kirim data ke view
            return view('admin.search-result', [
                'mahasiswa' => $mahasiswa,
                'takenSemesters' => $takenSemesters,
                'totalSKS' => $totalSKS,
                'khsData' => $khsData,
                'ibtitahData' => $ibtitahData,
                'sidangData' => $sidangData,
                'searchedNim' => $nim,
            ]);
        } catch (\Exception $e) {
            Log::error('AdminController: Error during search', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.search')->withErrors(['error' => 'Terjadi kesalahan saat melakukan pencarian.']);
        }
    }

        
}

