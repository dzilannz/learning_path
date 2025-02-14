<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File Admin</title>
    <link rel="stylesheet" href="{{ asset('css/search.css') }}">
    <script src="{{ asset('js/admin.js') }}" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <img src="{{ asset('img/logo.png') }}" alt="Logo">
            <span class="menu-text">
                <span class="line1">Student</span>
                <span class="line2">AcademicTracking</span>
            </span>
            <button id="sidebarToggle" class="sidebar-toggle">
                <i class="fa fa-bars"></i>
            </button>
            <span class="text-text">Search Result</span>
        </div>

        <div class="search-form">
        <form method="GET" action="{{ route('admin.search.result') }}">
            <input type="text" name="nim" placeholder="Masukkan NIM" value="{{ request('nim') }}" required>
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="menu-item">Dashboard</a>
            <a href="{{ route('admin.profile') }}" class="menu-item">Data Ibtitah</a>
            <a href="{{ route('admin.upload') }}" class="menu-item active">Add Data Ibtitah</a>
        </div>
        <div>
            <a href="{{ route('landing') }}" class="menu-item"><i class="fa fa-sign-out-alt"></i> Log Out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard">
        <div class="container">
                <div class="content">
                    <h4>Data Mahasiswa</h4>
                    <p><strong>Nama:</strong> {{ $mahasiswa['mahasiswa']['nama'] ?? 'Mahasiswa' }}</p>
                    <p><strong>NIM:</strong> {{ $mahasiswa['mahasiswa']['nim'] ?? 'Mahasiswa' }}</p>
                </div>

                <!-- Kuliah Section -->
                <div class="progress-section">
                    <div class="label">Kuliah</div>
                    <div class="progress-bar">
                        @php 
                            $cumulativeSKS = 0; 
                        @endphp

                        @foreach ($takenSemesters as $semester)
                            @php
                                $takenSKS = $semester['total_sks'] ?? 0;
                                $cumulativeSKS += $takenSKS;
                            @endphp

                            <div class="circle {{ $takenSKS > 0 ? 'completed' : '' }}">
                                <span class="tooltip">
                                    {{ $semester['nama'] }} <br>
                                    {{ $takenSKS }} SKS <br>
                                    Total: {{ $cumulativeSKS }} / {{ $totalSKS }} SKS <br>
                                    IP/IPK: {{ $semester['ip'] ?? 'N/A' }} / {{ $semester['ipk'] ?? 'N/A' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Ibtitah Section -->
                <div class="progress-section">
                    <div class="label">Ibtitah</div>
                    <div class="progress-bar-ibtitah">
                        @foreach (['tilawah', 'ibadah', 'tahfidz'] as $kategori)
                            @php
                                $status = $ibtitahData->where('kategori', $kategori)->first();
                            @endphp
                            <div class="circle ibadah-circle {{ $status && $status->status === 'approved' ? 'completed' : '' }}" onclick="openModal('{{ ucfirst($kategori) }}')">
                                <span class="label">{{ ucfirst($kategori) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

               

                <!-- Sidang Section -->
                <div class="progress-section">
                    <div class="label">Sidang</div>
                    <div class="progress-bar-sidang">
                        <div class="circle {{ $sidangData->seminar_kp ? 'completed' : '' }}">
                            <span class="label">Seminar KP</span>
                        </div>
                        <div class="circle {{ $sidangData->sempro ? 'completed' : '' }}">
                            <span class="label">Sempro</span>
                        </div>
                        <div class="circle {{ $sidangData->kompre ? 'completed' : '' }}">
                            <span class="label">Kompre</span>
                        </div>
                        <div class="circle {{ $sidangData->kolokium ? 'completed' : '' }}">
                            <span class="label">Kolokium</span>
                        </div>
                        <div class="circle {{ $sidangData->munaqasyah ? 'completed' : '' }}">
                            <span class="label">Munaqasyah</span>
                        </div>
                    </div>
                </div>
            </div> <!-- End Content -->
    </div> <!-- End Main Container -->

    <!-- Footer -->
    <div class="footer">
        &copy;Student Academic Tracking. All rights reserved.
    </div>
</body>
</html>
