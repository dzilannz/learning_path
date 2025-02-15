<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Academic Tracking</title>
    <link rel="stylesheet" href="/css/home.css">
    <script src="{{ asset('js/home.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>  
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <img src="{{ asset('img/logoif.png') }}" alt="Logo" class="nav-logo">
            <div class="nav-text">
                <span class="nav-line">TEKNIK</span>
                <span class="nav-line">INFORMATIKA</span>
            </div>
        </div>
            <div class="hamburger-menu" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="navbar-right" id="nav-links">
            <a href="#" class="nav-item">Home</a>
            <a href="#about" class="nav-item">About</a>
            <a href="{{ route('login.form') }}" class="nav-item">Login</a>
        </div>
    </nav>

    <header class="header">
        <div class="header-content">
            <h1 class="title">
                Student<br>Academic Tracking
            </h1>
            <p class="subtitle">Lihat perjalanan akademikmu dengan mudah!</p>
            <button class="btn" onclick="scrollToSection()">View More</button>
        </div>
    </header>

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

    <section class="chart-section">
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
    
    <section id="about" class="about-section">
        <div class="about-container">
            <img src="{{ asset('img/v292_191.png') }}" alt="About Illustration" class="about-image">
            <div class="about-content">
                <h2 class="about-title">About</h2>
                <div class="about-box">
                    <p>
                        Platform digital yang dirancang untuk membantu mahasiswa dalam melacak dan memahami perjalanan akademiknya. Kami hadir untuk membantu Anda tetap fokus pada tujuan dan memastikan Anda dapat mencapai potensi terbaik di setiap tahap perjalanan pendidikan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-section">
        <div class="footer-container">
            <div class="footer-left">
                <div class="logos">
                <img src="{{ asset('img/logoif.png') }}" alt="Logo 1" class="footer-logo">
                <img src="{{ asset('img/logo.png') }}" alt="Logo 2" class="footer-logo">
                </div>
                <div class="footer-address">
                    <strong>Teknik Informatika</strong><br>
                    Universitas Islam Negeri Sunan Gunung Djati<br>
                    Jalan A.H Nasution No. 105, Cipadung, Cibiru, Kota Bandung, Jawa Barat 40614
                </div>
            </div>
            <div class="footer-middle">
                <h3>Layanan Akademik</h3>
                <ul>
                    <li>
                        <a href="https://salam.uinsgd.ac.id/" target="_blank">
                        Sistem Informasi Layanan Akademik (SALAM)</a>
                    </li>
                    <li>
                        <a href="https://eknows.uinsgd.ac.id/" target="_blank">
                        Learning Management System (LMS)</a>
                    </li>
                    <li>
                        <a href="https://lib.uinsgd.ac.id/download/" target="_blank">
                            E-Library UIN Sunan Gunung Djati</a>
                    </li> 
                    <li>
                        <a href="#" target="_blank">
                            E-Library Teknik Informatika</a>
                        </li>
                    <li>
                        <a href="https://join.if.uinsgd.ac.id/index.php/join" target="_blank">
                            Jurnal Online Informatika</a>
                    </li>
                </ul>
            </div>
            <div class="footer-right">
                <h3>Akses Cepat</h3>
                <ul>
                    <li>
                        <a href="https://fst.uinsgd.ac.id/" target="_blank">
                            Fakultas Sains Dan Teknologi</a>
                    </li>
                    <li>
                        <a href="https://uinsgd.ac.id/" target="_blank">
                            UIN Sunan Gunung Djati</a>
                    </li>
                    <li>
                        <a href="https://sinta.kemdikbud.go.id/" target="_blank">
                            SINTA Dikti Kemendikbud RI</a></li>
                    <li>
                        <a href="https://pddikti.kemdiktisaintek.go.id/" target="_blank">
                        Pangkalan Data DIKTI Kemendikbud RI</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; Copyrights. All rights reserved. Dzilan Nazira Zahratunnisa</p>
        </div>
    </footer>

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
