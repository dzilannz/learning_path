<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/dashboard.css">
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Student Learning Path</title>
</head>
<body>
    <!-- Header -->
    <div class="header-container">
        <div class="header">
            <div class="greeting">
                Halo, {{ $mahasiswa['mahasiswa']['nama'] ?? 'Mahasiswa' }}
            </div>
            <div class="profile-menu">
                <i class="fa fa-user profile-icon" onclick="toggleMenu()"></i>
                <div class="menu" id="menu">
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="{{ route('landing') }}" class="logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="title">Your Progress</div>

        <!-- Kuliah Section -->
        <div class="progress-section">
            <div class="label">
                <a href="/mata-kuliah" style="text-decoration: none; color: inherit;">Kuliah</a>
            </div>
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

        <!-- Fallback jika data kosong -->
        @if (empty($takenSemesters))
            <p>Data semester tidak tersedia.</p>
        @endif

        <!-- Ibtitah Section -->
        <div class="progress-section">
            <div class="label">Ibtitah</div>
            <div class="progress-bar-ibtitah">
                @foreach (['tilawah', 'ibadah', 'tahfidz'] as $kategori)
                    @php
                        $status = $ibtitah->where('kategori', $kategori)->first();
                    @endphp
                    <div class="circle ibadah-circle {{ $status && $status->status === 'approved' ? 'completed' : '' }}" onclick="openModal('{{ ucfirst($kategori) }}')">
                        <span class="tooltip_ibadah">Click here to submit</span>
                        <span class="label">{{ ucfirst($kategori) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Sidang Section -->
        <div class="progress-section">
            <div class="label">Sidang</div>
            <div class="progress-bar-sidang">
                <div class="circle {{ $sidang->seminar_kp ? 'completed' : '' }}">
                    <span class="label">Seminar KP</span>
                </div>
                <div class="circle {{ $sidang->sempro ? 'completed' : '' }}">
                    <span class="label">Sempro</span>
                </div>
                <div class="circle {{ $sidang->kompre ? 'completed' : '' }}">
                    <span class="label">Kompre</span>
                </div>
                <div class="circle {{ $sidang->kolokium ? 'completed' : '' }}">
                    <span class="label">Kolokium</span>
                </div>
                <div class="circle {{ $sidang->munaqasyah ? 'completed' : '' }}">
                    <span class="label">Munaqasyah</span>
                </div>
            </div>
        </div>

        <!-- Modal untuk submit proof -->
        <div id="modal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2 id="modal-title">Submit Proof</h2>
                <form id="submit-form" action="{{ route('ibtitah.submit') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Input kategori hidden -->
                    <input type="hidden" id="kategori-input" name="kategori" value="">
                    <input type="hidden" name="nim" value="{{ $mahasiswa['nim'] }}">
                    <input type="file" name="proof_file" required>
                    <button type="submit">Submit</button>
                </form>

                <!-- Riwayat Upload -->
                @foreach (['tilawah', 'ibadah', 'tahfidz'] as $kategori)
                    @php
                        $history = $ibtitah->firstWhere('kategori', $kategori);
                    @endphp
                    <div id="history-{{ $kategori }}" class="history-section" style="display: none;">
                        <h3>Detail {{ ucfirst($kategori) }}</h3>
                        <p><strong>NIM:</strong> {{ $mahasiswa['nim'] }}</p>
                        <p><strong>Upload:</strong> {{ $history->submitted_at ?? 'Tidak tersedia' }}</p>
                        <p><strong>File:</strong>
                            @if ($history && $history->file_path)
                                <a href="{{ Storage::url($history->file_path) }}" target="_blank">Lihat File</a>
                            @else
                                Belum ada file
                            @endif
                        </p>
                        <p><strong>Status:</strong> {{ ucfirst($history->status ?? 'Belum tersedia') }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Modal Notifikasi -->
        <div id="success-modal" class="modal">
            <div class="modal-content">
                <i class="fa fa-check-circle" style="color: #FFD523; font-size: 50px; margin-bottom: 10px;"></i>
                <h2>Proof submitted successfully!</h2>
                <button onclick="closeSuccessModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy;Student Academic Tracking. All rights reserved.</p>
    </div>
</body>
</html>