<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Semester;

class SearchAdminController extends Controller
{
    public function search(Request $request)
    {
        // Ambil NIM dari input
        $nim = $request->get('nim');

        // Validasi NIM kosong
        if (!$nim) {
            return redirect()->route('dashboard')->withErrors(['error' => 'NIM tidak boleh kosong.']);
        }

        // Ambil API Token dari environment variable
        $API_TOKEN = env('MHS_API_TOKEN');

        try {
            // Fetch Mahasiswa Data
            $responseMahasiswa = Http::withHeaders([
                'Authorization' => 'Bearer ' . $API_TOKEN,
            ])->get(env('API_URL') . '/Api/ProfileKalam', [
                'nim' => $nim,
            ]);

            if (!$responseMahasiswa->ok() || !$responseMahasiswa->json()['status']) {
                return back()->withErrors(['error' => 'Gagal mengambil data mahasiswa.']);
            }

            $mahasiswa = $responseMahasiswa->json()['data'];

            // Log data mahasiswa untuk debugging
            Log::info('SearchAdminController: Data Mahasiswa berhasil diambil.', ['mahasiswa' => $mahasiswa]);

            // Fetch KHS Data (Krs/hasilStudi)
            $krsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $API_TOKEN,
            ])->get(env('API_URL') . '/Krs/hasilStudi', [
                'nim' => $nim,
            ]);

            if (!$krsResponse->ok() || !$krsResponse->json()['status']) {
                return back()->withErrors(['error' => 'Gagal mengambil data KHS mahasiswa.']);
            }

            $krsHasilStudi = collect($krsResponse->json()['data']);

            // Log data KHS untuk debugging
            Log::info('SearchAdminController: Data KHS berhasil diambil.', ['khs' => $krsHasilStudi]);

            // Fetch Ibtitah Data
            $ibtitah = DB::table('ibtitah')
                ->where('nim', $nim)
                ->get();

            // Log data ibtitah untuk debugging
            Log::info('SearchAdminController: Data Ibtitah berhasil diambil.', ['ibtitah' => $ibtitah]);

            // Fetch Sidang Data
            $sidang = DB::table('sidang')->where('nim', $nim)->first();

            // Log data sidang untuk debugging
            Log::info('SearchAdminController: Data Sidang berhasil diambil.', ['sidang' => $sidang]);

            // Sync Semester Data
            $takenSemesters = [];
$semesterCounter = 1;

foreach ($krsHasilStudi as $semesterData) {
    // Log nilai yang diterima untuk debugging
    Log::info('Semester Data:', [
        'ip' => $semesterData['ip'],
        'sks_diambil' => $semesterData['sks_diambil']
    ]);

    // Cek jika ip atau sks_diambil adalah null atau 0, lewati semester ini
    if (
        (is_null($semesterData['ip']) || floatval($semesterData['ip']) == 0.00) && 
        (is_null($semesterData['sks_diambil']) || intval($semesterData['sks_diambil']) == 0)
    ) {
        continue; // Lewati semester ini jika memenuhi kondisi
    }

    if ($semesterCounter > 8) {
        break;
    }

    $semesterName = 'Semester ' . $semesterCounter;
    $semesterCounter++;

    // Membuat atau memperbarui entitas semester
    $semester = Semester::updateOrCreate(
        ['nama' => $semesterName],
        ['created_at' => now(), 'updated_at' => now()]
    );

    // Menambahkan semester yang valid ke dalam array
    $takenSemesters[] = [
        'semester_id' => $semester->id,
        'nama' => $semesterName,
        'total_sks' => $semesterData['sks_diambil'],
        'ip' => $semesterData['ip'] ?? null,
        'ipk' => $semesterData['ipk'] ?? null,
    ];
}

// Menambahkan semester kosong jika jumlah semester kurang dari 8
while (count($takenSemesters) < 8) {
    $takenSemesters[] = [
        'semester_id' => null,
        'nama' => 'Semester ' . (count($takenSemesters) + 1),
        'total_sks' => 0,
        'ip' => null,
        'ipk' => null,
    ];
}

            // Return view dengan data yang lengkap
            return view('admin.search', [
                'mahasiswa' => $mahasiswa,
                'khsData' => $krsHasilStudi,
                'ibtitah' => $ibtitah,
                'sidang' => $sidang,
                'takenSemesters' => $takenSemesters,
            ]);
        } catch (\Exception $e) {
            Log::error('SearchAdminController: Terjadi kesalahan.', [
                'message' => $e->getMessage(),
                'nim' => $nim,
            ]);

            return back()->withErrors(['error' => 'Terjadi kesalahan saat memproses data.']);
        }
    }
}
