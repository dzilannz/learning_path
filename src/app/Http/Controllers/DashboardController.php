<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\IbtitahService;
use Illuminate\Support\Facades\DB;
use App\Models\Semester;

class DashboardController extends Controller
{


    public function show(Request $request, IbtitahService $ibtitahService)
    {
        Log::info('DashboardController: Accessing dashboard.');

        $user = $request->session()->get('user');
        $nim = $user['nim'] ?? null;

        if (!$nim) {
            Log::warning('DashboardController: Missing NIM in session.', ['user' => $user]);
            return redirect()->route('login.form')->with('error', 'Harap login terlebih dahulu.');
        }

        $tokenMahasiswa = $user['token'];

        // Log untuk melihat token mahasiswa
        Log::info('DashboardController: Token Mahasiswa:', ['tokenMahasiswa' => $tokenMahasiswa]);

        $totalSKS = 147;

        $ibtitahService->syncIbtitahOnLogin($nim);

        // Fetch Mahasiswa Data
        try {
            $responseMahasiswa = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenMahasiswa,
            ])->get(env('API_URL') . '/Api/ProfileKalam', [
                'nim' => $nim,
            ]);

            if (!$responseMahasiswa->ok() || !$responseMahasiswa->json()['status']) {
                return back()->withErrors(['error' => 'Gagal mengambil data mahasiswa.']);
            }

            $mahasiswa = $responseMahasiswa->json()['data'];

            if (!isset($mahasiswa['nim'])) {
                $mahasiswa['nim'] = $nim;
            }

            // Memanggil fungsi syncMahasiswaData di sini
            $this->syncMahasiswaData($mahasiswa);
            
            Log::info('syncMahasiswaData function called successfully.');
            
        } catch (\Exception $e) {
            Log::error('DashboardController: Error fetching mahasiswa data.', [
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghubungi server.']);
        }

        // Fetch KHS Data (Krs/hasilStudi)
        try {
            $krsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenMahasiswa,
            ])->get(env('API_URL') . '/Krs/hasilStudi', [
                'nim' => $nim,
            ]);

            if (!$krsResponse->ok() || !$krsResponse->json()['status']) {
                return back()->withErrors(['error' => 'Gagal mengambil data KHS mahasiswa.']);
            }

            $krsHasilStudi = collect($krsResponse->json()['data']);

            // Debugging response
            Log::info('KRS Hasil Studi:', ['krsHasilStudi' => $krsHasilStudi]);
        } catch (\Exception $e) {
            Log::error('DashboardController: Error fetching KHS data.', [
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghubungi server.']);
        }

       // Sync Semester Data 
       // Data untuk section kuliah
        try {
            $semesterMapping = [];
            $semesterNames = [];
            $takenSemesters = [];
            $semesterCounter = 1; // Counter manual untuk nama semester

            foreach ($krsHasilStudi as $semesterData) {
                // Skip jika SKS diambil null
                if (
                    (is_null($semesterData['ip']) || floatval($semesterData['ip']) == 0.00) && 
                    (is_null($semesterData['sks_diambil']) || intval($semesterData['sks_diambil']) == 0)
                ) {
                    continue; // Lewati semester ini jika memenuhi kondisi
                }

                // Batasi hingga semester ke-8
                if ($semesterCounter > 8) {
                    Log::info('Skipping semester beyond semester 8:', ['semesterData' => $semesterData]);
                    break; // Hentikan loop jika semester lebih dari 8
                }

                $semesterName = 'Semester ' . $semesterCounter; // Gunakan counter manual untuk nama semester
                $semesterCounter++; // Increment setelah semester valid diproses
                $semesterNames[] = $semesterName;

                // Simpan atau update ke tabel 'semester'
                $semester = Semester::updateOrCreate(
                    ['nama' => $semesterName],
                    ['created_at' => now(), 'updated_at' => now()]
                );

                // Mapping sem_id API ke id lokal
                $semesterMapping[$semesterData['sem_id']] = $semester->id;

                // Simpan data ke tabel 'semester_mahasiswa'
                DB::table('semester_mahasiswa')->updateOrInsert(
                    [
                        'nim' => $nim,
                        'id_semester' => $semesterData['sem_id'],
                    ],
                    [
                        'semester_id' => $semester->id,
                        'sks_diambil' => $semesterData['sks_diambil'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Masukkan semester valid ke takenSemesters
                $takenSemesters[] = [
                    'semester_id' => $semester->id,
                    'nama' => $semesterName,
                    'total_sks' => $semesterData['sks_diambil'],
                    'ip' => $semesterData['ip'] ?? null, // Tambahkan IP
                    'ipk' => $semesterData['ipk'] ?? null, // Tambahkan IPK
                ];
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

            // Debugging mapping
            Log::info('Semester Mapping:', ['mapping' => $semesterMapping]);
            Log::info('Taken Semesters:', ['takenSemesters' => $takenSemesters]);
        } catch (\Exception $e) {
            Log::error('DashboardController: Error syncing semester data.', [
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyinkronkan data semester.']);
        }


        // Data untuk section ibtitah
        $categories = [
            'tilawah' => 'Praktek Tilawah',
            'ibadah' => 'Praktek Ibadah',
            'tahfidz' => 'Tahfidz',
        ];
        
        try {
            $syncedCategories = [];
            foreach ($categories as $kategori => $namaMk) {
                $matkul = $krsHasilStudi->flatMap(function ($semester) {
                    return $semester['detail_mk'] ?? [];
                })->firstWhere('nama_mk', $namaMk);
        
                // Ambil data saat ini dari tabel 'ibtitah'
                $currentData = DB::table('ibtitah')
                    ->where('nim', $nim)
                    ->where('kategori', $kategori)
                    ->first();
        
                // Debugging
                Log::info('Current ibtitah data:', ['currentData' => $currentData]);
        
                // Tentukan status
                $status = $currentData && in_array($currentData->status, ['approved', 'rejected'])
                    ? $currentData->status
                    : ($matkul && $matkul['nilai_huruf'] !== null ? 'approved' : 'pending');
        
                // Update atau insert data
                DB::table('ibtitah')->updateOrInsert(
                    [
                        'nim' => $nim,
                        'kategori' => $kategori,
                    ],
                    [
                        'proof_file' => $currentData->proof_file ?? null,
                        'status' => $status,
                        'submitted_at' => ($currentData && $currentData->status === 'rejected') ? now() : $currentData->submitted_at,
                        'created_at' => $currentData->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
        
                if ($status === 'approved') {
                    $syncedCategories[] = $kategori;
                }
            }
            // Automatically update records with approved_at set to approved status
            DB::table('ibtitah')
                ->whereNotNull('approved_at')
                ->where('status', '!=', 'approved')
                ->update([
                    'status' => 'approved',
                    'updated_at' => now(),
                ]);
        
            // Fetch Ibtitah Data for View
            $ibtitah = DB::table('ibtitah')->where('nim', $nim)->get();
        
            // Log success
            Log::info('DashboardController: Successfully synced Ibtitah data.', [
                'synced_categories' => $syncedCategories,
                'ibtitah_data' => $ibtitah,
            ]);
        } catch (\Exception $e) {
            Log::error('DashboardController: Error syncing Ibtitah data.', [
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyinkronkan data Ibtitah.']);
        }

        // Fetch Ibtitah History
        try {
            $ibtitahHistory = DB::table('ibtitah')
                ->select('kategori', 'submitted_at', 'file_path', 'status')
                ->where('nim', $nim)
                ->get()
                ->keyBy('kategori'); // Mengelompokkan data berdasarkan kategori

            // Logging data riwayat ibtitah
            Log::info('DashboardController: Ibtitah history fetched successfully.', ['ibtitahHistory' => $ibtitahHistory]);

        } catch (\Exception $e) {
            Log::error('DashboardController: Error fetching Ibtitah history.', ['message' => $e->getMessage()]);
            $ibtitahHistory = collect(); // Jika terjadi error, tetap return koleksi kosong
        }

        Log::info('DashboardController: Final ibtitahHistory sent to blade.', ['ibtitahHistory' => $ibtitahHistory]);

        // Kode MK untuk menandai status sidang
        $kodeSidang = [
            'seminar_kp' => 'Kerja Praktek/Magang',
            'sempro' => 'Kerja Praktek/Magang',
            'kompre' => 'Komprehensif',
            'kolokium' => 'Tugas Akhir dan Seminar',
            'munaqasyah' => 'Tugas Akhir dan Seminar',
        ];
        
        // Data untuk section sidang
        $sidangStatus = [
            'seminar_kp' => false,
            'sempro' => false,
            'kompre' => false,
            'kolokium' => false,
            'munaqasyah' => false,
        ];
        
        try {
            // Periksa status dari API
            foreach ($kodeSidang as $sidang => $namaMk) {
                $matkul = $krsHasilStudi->flatMap(function ($semester) {
                    return $semester['detail_mk'] ?? [];
                })->where('nama_mk', $namaMk)
                  ->filter(function ($matkul) {
                      return isset($matkul['nilai_huruf']) && trim($matkul['nilai_huruf']) !== '';
                  })->sortByDesc('sem_id') // Urutkan berdasarkan semester terbaru
                  ->first();
        
                // Jika ada nilai_huruf, set status sesuai
                if ($matkul) {
                    if ($namaMk === 'Kerja Praktek/Magang') {
                        $sidangStatus['seminar_kp'] = true;
                        $sidangStatus['sempro'] = true;
                    } elseif ($namaMk === 'Komprehensif') {
                        $sidangStatus['kompre'] = true;
                    } elseif ($namaMk === 'Tugas Akhir dan Seminar') {
                        $sidangStatus['kolokium'] = true;
                        $sidangStatus['munaqasyah'] = true;
                    }
                }
            }
        
            // Update atau Insert ke tabel sidang
            DB::table('sidang')->updateOrInsert(
                ['nim' => $nim],
                array_merge($sidangStatus, [
                    'updated_at' => now(),
                ])
            );
        
            // Ambil data untuk ditampilkan di dashboard
            $sidang = DB::table('sidang')->where('nim', $nim)->first();
        
            // Logging keberhasilan sinkronisasi
            Log::info('DashboardController: Successfully synced Sidang data.', [
                'sidang_status' => $sidangStatus,
                'sidang_data' => $sidang,
            ]);
        } catch (\Exception $e) {
            // Logging error
            Log::error('DashboardController: Error syncing Sidang data.', [
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyinkronkan data Sidang.']);
        }
        

        $token = env('MHS_API_TOKEN');
        try {
            $urlProfileMhs = env('API_URL1') . '/Api/ProfileMhs';
        
            $responseProfileMhs = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get($urlProfileMhs, [
                'nim' => $nim,
                'token' => $token,
            ]);
    
            if (!$responseProfileMhs->ok() || !$responseProfileMhs->json()['status']) {
                Log::error('DashboardController: Failed to fetch profile data from HEROKU.', [
                    'url' => $urlProfileMhs,
                    'nim' => $nim,
                    'response' => $responseProfileMhs->json(),
                ]);
        
                return back()->withErrors(['error' => 'Gagal mengambil data profil mahasiswa dari HEROKU.']);
            }
        
            // Data profile dari HEROKU
            $profileMhs = $responseProfileMhs->json()['data'];
        
            Log::info('DashboardController: Successfully fetched profile data from HEROKU.', [
                'url' => $urlProfileMhs,
                'nim' => $nim,
                'profileMhs' => $profileMhs,
            ]);
        
        } catch (\Exception $e) {
            Log::error('DashboardController: Error fetching profile data from HEROKU.', [
                'message' => $e->getMessage(),
                'nim' => $nim,
            ]);
        
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghubungi server HEROKU.']);
        }
        
        
               

        // Prepare Data for View
        return view('dashboard', [
            'mahasiswa' => $mahasiswa,
            'semesters' => Semester::all(),
            'takenSemesters' => $takenSemesters,
            'totalSKS' => $totalSKS,
            'semesterMapping' => $semesterMapping,
            'ibtitah' => $ibtitah ?? collect(),
            'ibtitahHistory' => $ibtitahHistory,
            'sidang' => $sidang ?? collect(),
        ]);
    }

     /**
     * Sinkronisasi data mahasiswa ke tabel lokal.
     *
     * @param array $mahasiswa
     */
    private function syncMahasiswaData(array $mahasiswa)
    {
        try {
            Log::info('Syncing Mahasiswa Data START:', ['mahasiswa' => $mahasiswa]);
    
            // Pastikan mengambil "mulai_smt" dari sub-key "mahasiswa"
            $mulaiSmt = $mahasiswa['mahasiswa']['mulai_smt'] ?? null;
    
            if (!$mulaiSmt) {
                throw new \Exception('Mulai SMT is missing or null.');
            }
    
            DB::table('mahasiswa')->updateOrInsert(
                ['nim' => $mahasiswa['mahasiswa']['nim']], // Akses NIM dari sub-key
                [
                    'mulai_smt' => $mulaiSmt, // Mulai semester
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
    
            Log::info('Syncing Mahasiswa Data SUCCESS:', ['nim' => $mahasiswa['mahasiswa']['nim']]);
        } catch (\Exception $e) {
            Log::error('DashboardController: Error syncing mahasiswa data.', [
                'message' => $e->getMessage(),
                'nim' => $mahasiswa['mahasiswa']['nim'] ?? null,
            ]);
        }
    }
    
}