<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/css/dashboard_admin.css">
    <script src="{{ asset('js/home.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="header">
        <div class="logo">
            <img src="{{ asset('img/logo.png') }}" alt="Logo">
            <span class="menu-text">
                <span class="line1">Student</span>
                <span class="line2">LearningPath</span>
            </span>
            <button id="sidebarToggle" class="sidebar-toggle">
                <i class="fa fa-bars"></i>
            </button>
            <span class="text-text">Dashboard</span>
        </div>

        <div class="search-form">
        <form method="GET" action="{{ route('admin.search.result') }}">
            <input type="text" name="nim" placeholder="Masukkan NIM" value="{{ request('nim') }}" required>
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>
        </div>
    </div>
    
    <div class="sidebar">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="menu-item">Dashboard</a>
            <a href="{{ route('admin.profile') }}" class="menu-item">Data Ibtitah</a>
            <a href="{{ route('admin.upload') }}" class="menu-item">Add Data Ibtitah</a>
        </div>
        <div>
            <a href="{{ route('landing') }}" class="menu-item"><i class="fa fa-sign-out-alt"></i> Log Out</a>
        </div>
    </div>
    <div class="dashboard">
    <!-- Stats Section -->
        <section id="target-section" class="stats-section">
            <div class="stats-card">
                <h3 class="stats-title">Mahasiswa Aktif</h3>
                <p class="stats-value">{{ $mahasiswaAktifCount }}</p>
            </div>
            <div class="stats-card">
                <h3 class="stats-title">Ibtitah</h3>
                <p class="stats-value">{{ $keseluruhanIbtitah }}</p>
            </div>
            <div class="stats-card">
                <h3 class="stats-title">Kerja Praktek</h3>
                <p class="stats-value">{{ $jumlahKerjaPraktikAktif }}</p>
            </div>
            <div class="stats-card">
                <h3 class="stats-title">Sidang</h3>
                <p class="stats-value">{{ $totalSidang }}</p>
            </div>
        </section>

            <div>
                <select id="angkatanFilter" onchange="filterByAngkatan()">
                    <option value="all" {{ is_null($angkatan) ? 'selected' : '' }}>All</option>
                    @foreach ($aktifPerAngkatan as $data)
                        <option value="{{ $data->angkatan }}" {{ $angkatan == $data->angkatan ? 'selected' : '' }}>
                            {{ $data->angkatan }}
                        </option>
                    @endforeach
                </select>
            </div>

        <section class="chart-section">
            <div class="chart-container large-chart">
                <h3>Statistik</h3>
                <canvas id="statistikChart"></canvas>
            </div>
            <div class="chart-container small-chart">
                <h3>Ibtitah</h3>
                <canvas id="ibtitahChart"></canvas>
            </div>
            <div class="chart-container small-chart">
                <h3>TA & Sidang</h3>
                <canvas id="sidangChart"></canvas>
            </div>
        </section>
    </div>

    <div class="footer">
        &copy;Student LearningPath. All rights reserved.
    </div>

    <script>
    const dataSets = {
        all: {
            stats: [
                { title: "Mahasiswa Aktif", value: {{ $mahasiswaAktifCount }} },
                { title: "Ibtitah", value: {{ $keseluruhanIbtitah }} },
                { title: "Kerja Praktek", value: {{ $jumlahKerjaPraktikAktif }} },
                { title: "Sidang", value: {{ $totalSidang }} },
            ],
            statistik: [
                {{ $mahasiswaAktifCount }},
                {{ $keseluruhanIbtitah }},
                {{ $jumlahKerjaPraktikAktif }},
                {{ $totalSidang }}
            ],
            ibtitah: {!! json_encode(array_values($ibtitahPerKategori)) !!},
            taSidang: [
                {{ $sidangPerKategori['sempro'] ?? 0 }},
                {{ $sidangPerKategori['kompre'] ?? 0 }},
                {{ $sidangPerKategori['kolokium'] ?? 0 }},
                {{ $sidangPerKategori['munaqasyah'] ?? 0 }}
            ],
        },
        @foreach ($aktifPerAngkatan as $angkatan)
        {{ $angkatan->angkatan }}: {
            stats: [
                { title: "Mahasiswa Aktif", value: {{ $angkatan->jumlah_aktif }} },
                { title: "Ibtitah", value: {{ $keseluruhanIbtitahPerAngkatan->firstWhere('angkatan', $angkatan->angkatan)->jumlah ?? 0 }} },
                { title: "Kerja Praktek", value: {{ $kerjaPraktikPerAngkatan->firstWhere('angkatan', $angkatan->angkatan)->jumlah_aktif ?? 0 }} },
                { title: "Sidang", value: {{ $sidangPerAngkatan->firstWhere('angkatan', $angkatan->angkatan)->jumlah_sidang ?? 0 }} },
            ],
            statistik: [
                {{ $angkatan->jumlah_aktif }},
                {{ $keseluruhanIbtitahPerAngkatan->firstWhere('angkatan', $angkatan->angkatan)->jumlah ?? 0 }},
                {{ $kerjaPraktikPerAngkatan->firstWhere('angkatan', $angkatan->angkatan)->jumlah_aktif ?? 0 }},
                {{ $sidangPerAngkatan->firstWhere('angkatan', $angkatan->angkatan)->jumlah_sidang ?? 0 }}
            ],
            ibtitah: {!! json_encode(array_values($ibtitahPerKategoriPerAngkatan[$angkatan->angkatan] ?? [])) !!},
            taSidang: [
                {{ $sidangPerAngkatanPerKategori['sempro']->firstWhere('angkatan', $angkatan->angkatan)->jumlah_sidang ?? 0 }},
                {{ $sidangPerAngkatanPerKategori['kompre']->firstWhere('angkatan', $angkatan->angkatan)->jumlah_sidang ?? 0 }},
                {{ $sidangPerAngkatanPerKategori['kolokium']->firstWhere('angkatan', $angkatan->angkatan)->jumlah_sidang ?? 0 }},
                {{ $sidangPerAngkatanPerKategori['munaqasyah']->firstWhere('angkatan', $angkatan->angkatan)->jumlah_sidang ?? 0 }}
            ],
        },
        @endforeach
    };

    function filterAngkatan() {
        const selectedAngkatan = document.getElementById('angkatanFilter').value;
        filterData(selectedAngkatan);
    }
    </script>
</body>
</html>