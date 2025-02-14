<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\NimHelper;
use App\Helpers\SemesterHelper;
use App\Jobs\ProcessNimValidation;
use App\Jobs\CalculateKPCount;
use App\Jobs\CalculateIbtitahCount;
use App\Jobs\CalculateSidangCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LandingPageController extends Controller
{
    public function index(Request $request, $angkatan = null)
    {
        Log::info('LandingPageController: Memulai proses penghitungan mahasiswa aktif.');
    
        // Ambil semester saat ini
        $currentSemId = SemesterHelper::getCurrentSemesterId();
        Log::info('LandingPageController: Semester saat ini.', ['currentSemId' => $currentSemId]);
    
        // Ambil semua NIM yang sudah ada di database
        $tahunSudahAda = DB::table('nims')
            ->selectRaw('DISTINCT angkatan')
            ->pluck('angkatan')
            ->toArray();
    
        Log::info('LandingPageController: Tahun yang sudah ada di tabel nims.', ['tahunSudahAda' => $tahunSudahAda]);

        $queryNims = DB::table('nims');
        if ($angkatan) {
            $queryNims->where('angkatan', $angkatan);
        }
    
        // Generate NIM baru hanya untuk tahun yang belum ada
        $tahunMulai = 2017;
        $tahunAkhir = date('Y');
        $kodeUniversitas = '1';
        $kodeProdi = '705';
        $maxMahasiswa = 150;
    
        foreach (range($tahunMulai, $tahunAkhir) as $tahun) {
            if (!in_array($tahun, $tahunSudahAda)) {
                Log::info('LandingPageController: Mengenerate NIM untuk tahun.', ['tahun' => $tahun]);
    
                $daftarNim = NimHelper::generateNimList($tahun, $tahun, $kodeUniversitas, $kodeProdi, $maxMahasiswa);
    
                $batchInsert = array_map(function ($nim) use ($tahun) {
                    return [
                        'nim' => $nim,
                        'angkatan' => $tahun,
                        'status' => 'tidak aktif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $daftarNim);
    
                // Insert batch ke tabel nims
                DB::table('nims')->insertOrIgnore($batchInsert);
    
                Log::info('LandingPageController: Berhasil menyimpan NIM ke tabel nims.', [
                    'tahun' => $tahun,
                    'total_nim' => count($daftarNim),
                ]);
            } else {
                Log::info('LandingPageController: Tahun sudah ada, melewatkan generate NIM.', ['tahun' => $tahun]);
            }
        }
    
        // Hapus NIM berlebih jika ada
        $jumlahNIM = DB::table('nims')->count();
        if ($jumlahNIM > 1350) {
            $nimToDelete = DB::table('nims')
                ->orderBy('created_at', 'asc')
                ->limit($jumlahNIM - 1350)
                ->pluck('nim')
                ->toArray();
    
            DB::table('nims')->whereIn('nim', $nimToDelete)->delete();
    
            Log::info('LandingPageController: Menghapus NIM berlebih dari tabel nims.', [
                'jumlah_dihapus' => count($nimToDelete),
                'nim_dihapus' => $nimToDelete,
            ]);
        }
    
        // Kirim setiap NIM ke queue untuk validasi status aktif
        $nims = DB::table('nims')->get();
        foreach ($nims as $nim) {
            // Kirim ke queue default untuk validasi
            ProcessNimValidation::dispatch($nim->nim, $currentSemId);
            [
                'nim' => $nim->nim,
            ];
        
            CalculateKPCount::dispatch($nim->nim)->onQueue('kp_count');
            
        }

        CalculateIbtitahCount::dispatch()->onQueue('ibtitah'); // Kirim ke queue bernama 'ibtitah'
        Log::info('LandingPageController: Job CalculateIbtitahCount telah dikirim ke queue.');
    
        Log::info('LandingPageController: Semua NIM telah dikirim ke queue untuk diproses.');
    
        $aktifPerAngkatan = DB::table('nims')
        ->select('angkatan', DB::raw('COUNT(*) as jumlah_aktif'))
        ->where('status', 'aktif')
        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
            return $query->where('angkatan', $angkatan);
        })
        ->groupBy('angkatan')
        ->orderBy('angkatan', 'asc')
        ->get();

    // Ambil data lainnya berdasarkan filter angkatan
        $mahasiswaAktifCount = DB::table('nims')
        ->where('status', 'aktif')
        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
            return $query->where('angkatan', $angkatan);
        })
        ->count();
    
        $jumlahKerjaPraktikAktif = DB::table('nim_matkul')
            ->join('nims', 'nim_matkul.nim', '=', 'nims.nim')
            ->where('nims.status', 'aktif') // Hanya hitung mahasiswa yang statusnya aktif
            ->where('nim_matkul.nama_mk', 'Kerja Praktek/Magang') // Kode MK Kerja Praktik
            ->whereNotNull('nim_matkul.nilai_huruf')
            ->count();

        $kerjaPraktikPerAngkatan = DB::table('nim_matkul')
            ->join('nims', 'nim_matkul.nim', '=', 'nims.nim') // Gabungkan dengan tabel nims
            ->select('nims.angkatan', DB::raw('COUNT(nim_matkul.nim) as jumlah_aktif'))
            ->where('nim_matkul.nama_mk', 'Kerja Praktek/Magang') // Filter mata kuliah "Kerja Praktek/Magang"
            ->whereNotNull('nim_matkul.nilai_huruf') // Hanya nilai yang valid
            ->where('nims.status', 'aktif') // Hanya mahasiswa dengan status aktif
            ->groupBy('nims.angkatan') // Kelompokkan berdasarkan angkatan
            ->orderBy('nims.angkatan', 'asc') // Urutkan berdasarkan angkatan
            ->get();
        
        Log::info('LandingPageController: Angkatan diterima', ['angkatan' => $angkatan]);

    
        Log::info('LandingPageController: Jumlah mahasiswa aktif yang mengambil Kerja Praktik.', [
            'jumlahKerjaPraktikAktif' => $jumlahKerjaPraktikAktif,
        ]);

        Log::info('LandingPageController: Memulai proses penghitungan data landing page.');

        // Definisi kategori
        $categories = [
            'tilawah' => 'Praktek Tilawah',
            'ibadah' => 'Praktek Ibadah',
            'tahfidz' => 'Tahfidz',
        ];

        
        $keseluruhanIbtitah = DB::table('statistics')
            ->where('name', 'ibtitah_count')
            ->value('value') ?? 0;

        $keseluruhanIbtitahPerAngkatan = DB::table('ibtitah')
            ->join('nims', 'ibtitah.nim', '=', 'nims.nim') // Join once with the nims table
            ->where('ibtitah.status', 'approved')
            ->where('nims.status', 'aktif')
            ->where('nims.angkatan', $angkatan)
            ->whereIn('ibtitah.kategori', ['tilawah', 'ibadah', 'tahfidz'])
            ->select(DB::raw('COUNT(DISTINCT nims.nim) as total_mahasiswa')) // Counting distinct nim from nims table
            ->havingRaw('COUNT(DISTINCT ibtitah.kategori) = 3') // Ensure student has all 3 categories
            ->first(); // Get the result directly
        

        // **Ibtitah per kategori**
        $ibtitahPerKategori = [];
            foreach ($categories as $kategori => $namaMk) {
                $ibtitahPerKategori[$kategori] = DB::table('ibtitah')
                    ->join('nims', 'ibtitah.nim', '=', 'nims.nim') // Join nims table to check student status
                    ->where('ibtitah.kategori', $kategori)
                    ->where('ibtitah.status', 'approved')
                    ->where('nims.status', 'aktif') // Ensure the student is active
                    ->count();
            }

        // **Ibtitah per kategori per angkatan**
        $ibtitahPerKategoriPerAngkatan = [];
        foreach ($categories as $kategori => $namaMk) {
            $ibtitahPerKategoriPerAngkatan[$kategori] = DB::table('ibtitah')
                ->join('nims', 'ibtitah.nim', '=', 'nims.nim') // Join with nims table to check student status
                ->where('ibtitah.kategori', $kategori) // Filter by category
                ->where('ibtitah.status', 'approved') // Ensure the record is approved
                ->where('nims.status', 'aktif') // Ensure the student is active
                ->select('nims.angkatan', DB::raw('COUNT(DISTINCT ibtitah.nim) as jumlah')) // Count distinct students
                ->groupBy('nims.angkatan') // Group by intake year (angkatan)
                ->orderBy('nims.angkatan', 'asc') // Order by intake year
                ->get();
        }
        
        // Log hasil untuk debugging
        Log::info('LandingPageController: Data Ibtitah berhasil dihitung.', [
            'keseluruhanIbtitah' => $keseluruhanIbtitah,
            'ibtitahPerKategori' => $ibtitahPerKategori,
            'keseluruhanIbtitahPerAngkatan' => $keseluruhanIbtitahPerAngkatan,
            'ibtitahPerKategoriPerAngkatan' => $ibtitahPerKategoriPerAngkatan,
        ]);

        // Panggil job CalculateSidangCount untuk memperbarui data sidang
        CalculateSidangCount::dispatch()->onQueue('sidang'); // Kirim job ke queue bernama 'sidang'
        Log::info('LandingPageController: Job CalculateSidangCount telah dikirim ke queue.');

        // Ambil data sidang keseluruhan (Exclude seminar_kp)
        $totalSidang = DB::table('statistics')->where('name', 'sidang_count')->value('value') ?? 0;
        $sidangPerKategori = [];
        $kodeSidang = [
            'seminar_kp' => 'Kerja Praktek/Magang',
            'sempro' => 'Kerja Praktek/Magang',
            'kompre' => 'Komprehensif',
            'kolokium' => 'Tugas Akhir dan Seminar',
            'munaqasyah' => 'Tugas Akhir dan Seminar',
        ];
        foreach (array_keys($kodeSidang) as $kategori) {
            $sidangPerKategori[$kategori] = DB::table('statistics')
                ->where('name', "sidang_{$kategori}_count")
                ->value('value') ?? 0;
        }
        

        // Sidang per kategori (Exclude seminar_kp)
        $sidangPerKategori = [];
        $kodeSidang = [
            'seminar_kp' => 'Kerja Praktek/Magang', // Tidak dihitung di sini
            'sempro' => 'Kerja Praktek/Magang',
            'kompre' => 'Komprehensif',
            'kolokium' => 'Tugas Akhir dan Seminar',
            'munaqasyah' => 'Tugas Akhir dan Seminar',
        ];

        foreach ($kodeSidang as $sidang => $namaMk) {
            // Hanya hitung kategori selain seminar_kp
            if ($sidang !== 'seminar_kp') {
                if ($sidang == 'kompre') {
                    // Pastikan sempro sudah true sebelum menghitung kompre
                    $sidangPerKategori[$sidang] = DB::table('sidang')
                        ->where('sempro', true) // Pastikan sudah sempro
                        ->where('kompre', true) // Kemudian baru kompre
                        ->count();
                } else {
                    $sidangPerKategori[$sidang] = DB::table('sidang')->where($sidang, true)->count();
                }
            }
        }

        // Sidang per angkatan
        $sidangPerAngkatan = DB::table('sidang')
            ->join('nims', 'sidang.nim', '=', 'nims.nim')
            ->select('nims.angkatan', DB::raw('COUNT(sidang.nim) as jumlah_sidang'))
            ->where(function ($query) {
                $query->where('sempro', true)
                    ->orWhere('kompre', true)
                    ->orWhere('kolokium', true)
                    ->orWhere('munaqasyah', true); // Exclude seminar_kp
            })
            ->groupBy('nims.angkatan')
            ->orderBy('nims.angkatan', 'asc')
            ->get();

        $sidangPerAngkatanPerKategori = [];
            foreach (array_keys($kodeSidang) as $kategori) {
                $sidangPerAngkatanPerKategori[$kategori] = DB::table('sidang')
                    ->join('nims', 'sidang.nim', '=', 'nims.nim')
                    ->select('nims.angkatan', DB::raw('COUNT(sidang.nim) as jumlah_sidang'))
                    ->where($kategori, true)
                    ->groupBy('nims.angkatan')
                    ->orderBy('nims.angkatan', 'asc')
                    ->get();
            }
            

        // Data statistik untuk ditampilkan
        $mahasiswaAktifCount = DB::table('nims')->where('status', 'aktif')->count();
        $aktifPerAngkatan = DB::table('nims')
            ->select('angkatan', DB::raw('COUNT(*) as jumlah_aktif'))
            ->where('status', 'aktif')
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'asc')
            ->get();

        Log::info('LandingPageController: Data sidang berhasil dihitung.');

        $angkatan = $angkatan ?? $request->query('angkatan', 'all');

        if ($request->ajax()) {
            // Jika permintaan datang melalui AJAX, hanya kirimkan data JSON
            $data = [
                'mahasiswaAktifCount' => DB::table('nims')
                    ->where('status', 'aktif')
                    ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                        return $query->where('angkatan', $angkatan);
                    })
                    ->count(),
                'jumlahKerjaPraktikAktif' => DB::table('nim_matkul')
                    ->join('nims', 'nim_matkul.nim', '=', 'nims.nim')
                    ->where('nims.status', 'aktif')
                    ->where('nim_matkul.nama_mk', 'Kerja Praktek/Magang')
                    ->whereNotNull('nim_matkul.nilai_huruf')
                    ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                        return $query->where('nims.angkatan', $angkatan);
                    })
                    ->count(),
                'keseluruhanIbtitah' => DB::table('ibtitah')
                    ->join('nims', 'ibtitah.nim', '=', 'nims.nim')
                    ->where('ibtitah.status', 'approved')
                    ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                        return $query->where('nims.angkatan', $angkatan);
                    })
                    ->count(),
                'ibtitahPerKategoriPerAngkatan' => [
                    'tilawah' => DB::table('ibtitah')
                        ->join('nims', 'ibtitah.nim', '=', 'nims.nim')
                        ->where('ibtitah.kategori', 'tilawah')
                        ->where('ibtitah.status', 'approved')
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                    'ibadah' => DB::table('ibtitah')
                        ->join('nims', 'ibtitah.nim', '=', 'nims.nim')
                        ->where('ibtitah.kategori', 'ibadah')
                        ->where('ibtitah.status', 'approved')
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                    'tahfidz' => DB::table('ibtitah')
                        ->join('nims', 'ibtitah.nim', '=', 'nims.nim')
                        ->where('ibtitah.kategori', 'tahfidz')
                        ->where('ibtitah.status', 'approved')
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                ],
                'totalSidang' => DB::table('sidang')
                    ->join('nims', 'sidang.nim', '=', 'nims.nim')
                    ->where(function ($query) {
                        $query->where('sempro', true)
                            ->orWhere('kompre', true)
                            ->orWhere('kolokium', true)
                            ->orWhere('munaqasyah', true);
                    })
                    ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                        return $query->where('nims.angkatan', $angkatan);
                    })
                    ->count(),
                'sidangPerKategori' => [
                    'sempro' => DB::table('sidang')
                        ->join('nims', 'sidang.nim', '=', 'nims.nim')
                        ->where('sidang.sempro', true)
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                    'kompre' => DB::table('sidang')
                        ->join('nims', 'sidang.nim', '=', 'nims.nim')
                        ->where('sidang.kompre', true)
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                    'kolokium' => DB::table('sidang')
                        ->join('nims', 'sidang.nim', '=', 'nims.nim')
                        ->where('sidang.kolokium', true)
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                    'munaqasyah' => DB::table('sidang')
                        ->join('nims', 'sidang.nim', '=', 'nims.nim')
                        ->where('sidang.munaqasyah', true)
                        ->when($angkatan !== 'all', function ($query) use ($angkatan) {
                            return $query->where('nims.angkatan', $angkatan);
                        })
                        ->count(),
                ],
            ];
        
            return response()->json($data);
        }
    

        return view('landing', [
            'mahasiswaAktifCount' => DB::table('nims')->where('status', 'aktif')->count(),
            'aktifPerAngkatan' => $aktifPerAngkatan,
            'jumlahKerjaPraktikAktif' => $jumlahKerjaPraktikAktif,
            'kerjaPraktikPerAngkatan' => $kerjaPraktikPerAngkatan,
            'keseluruhanIbtitah' => $keseluruhanIbtitah,
            'ibtitahPerKategori' => $ibtitahPerKategori,
            'keseluruhanIbtitahPerAngkatan' => $keseluruhanIbtitahPerAngkatan ?? collect(),
            'ibtitahPerKategoriPerAngkatan' => $ibtitahPerKategoriPerAngkatan,
            'totalSidang' => $totalSidang,
            'sidangPerKategori' => $sidangPerKategori,
            'sidangPerAngkatan' => $sidangPerAngkatan,
            'sidangPerAngkatanPerKategori' => $sidangPerAngkatanPerKategori,
            'angkatan' => $angkatan,
        ]);
    }    
}