<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File Admin</title>
    <link rel="stylesheet" href="{{ asset('css/upload.css') }}">
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
                <span class="line2">LearningPath</span>
            </span>
            <button id="sidebarToggle" class="sidebar-toggle">
                <i class="fa fa-bars"></i>
            </button>
            <span class="text-text">Upload File</span>
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
            <h2>Upload File</h2>
            @if ($errors->any())
                <div class="error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('admin.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="nim">NIM</label>
                    <input type="text" id="nim" name="nim" value="{{ old('nim') }}" required>
                </div>
                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required>
                </div>
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required>
                        <option value="" disabled selected>Pilih Kategori</option>
                        <option value="tilawah">Tilawah</option>
                        <option value="tahfidz">Tahfidz</option>
                        <option value="ibadah">Ibadah</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file">Upload File</label>
                    <input type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy;Student LearningPath. All rights reserved.
    </div>
</body>
</html>
