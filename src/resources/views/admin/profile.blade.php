<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Ibtitah</title>
    <!-- Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/admin.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <span class="text-text">Data Ibtitah</span>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="menu-item">Dashboard</a>
            <a href="{{ route('admin.profile') }}" class="menu-item">Data Ibtitah</a>
            <a href="{{ route('admin.upload') }}" class="menu-item">Add Data Ibtitah</a>
        </div>
        <div>
            <a href="{{ route('landing') }}"class="menu-item"><i class="fa fa-sign-out-alt"></i> Log Out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1>Data Ibtitah</h1>

        <!-- Sorting Options -->
        <div class="sort-options">
            <form method="GET" action="{{ route('admin.profile') }}">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All</option>
                    <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $statusFilter == 'approved' ? 'selected' : '' }}>Approved</option>
                </select>
            </form>
        </div>

        <div class="search-form" style="margin-bottom: 20px;">
            <form method="GET" action="{{ route('admin.profile') }}">
                <input type="text" name="nim" placeholder="Search by NIM" value="{{ request('nim') }}" class="search-input"/>
                <button type="submit" class="search-button"><i class="fa fa-search"></i></button>
            </form>
        </div>


        @if ($submittedFiles->isEmpty())
            <p style="text-align: center;">Tidak ada data yang tersedia.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NIM</th>
                    <th>Kategori</th>
                    <th class="desktop-only">File</th>
                    <th class="desktop-only">Upload</th>
                    <th class="desktop-only">Upload by Admin</th>
                    <th class="desktop-only">Status</th>
                    <th class="desktop-only">Aksi</th>
                    <th class="desktop-hide">Detail</th> <!-- Tambahkan kolom Detail -->
                </tr>
            </thead>
            <tbody>
                @foreach ($submittedFiles as $index => $file)
                <tr class="main-row">
                    <td>{{ $submittedFiles->firstItem() + $index }}</td>
                    <td>{{ $file->nim }}</td>
                    <td>{{ ucfirst($file->kategori) }}</td>
                    <td class="desktop-only"><a href="{{ asset('storage/' . $file->file_path) }}" target="_blank">View File</a></td>
                    <td class="desktop-only">{{ $file->submitted_at }}</td>
                    <td class="desktop-only">{{ $file->file_diupload_admin }}</td>
                    <td class="desktop-only">{{ ucfirst($file->status) }}</td>
                    <td class="desktop-only">
                        @if ($file->status === 'approved')
                            <button class="btn btn-done" disabled>Done</button>
                        @else
                            <form action="{{ route('admin.approve', $file->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="button" class="btn btn-approve" onclick="confirmAction(event, 'Apakah kamu yakin mau meng-approve ini?', this.closest('form'))">Approve</button>
                            </form>
                            <form action="{{ route('admin.reject', $file->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="button" class="btn btn-reject" onclick="confirmAction(event, 'Apakah kamu yakin mau menolak ini?', this.closest('form'))">Reject</button>
                            </form>
                            <form action="{{ route('admin.delete', $file->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-delete" onclick="confirmAction(event, 'Apakah kamu yakin mau menghapus ini?', this.closest('form'))">Delete</button>
                            </form>
                        @endif
                    </td>
                    <td class="desktop-hide">
                        <button class="toggle-details" onclick="toggleDetails(this)">Detail</button> <!-- Tombol Detail hanya di Mobile -->
                    </td>
                <tr class="details-row">
                    <td colspan="9">
                        <div class="details-content">
                            <p><strong>File:</strong> <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank">View File</a></p>
                            <p><strong>Upload:</strong> {{ $file->submitted_at }}</p>
                            <p><strong>Upload by Admin:</strong> {{ $file->file_diupload_admin }}</p>
                            <p><strong>Status:</strong> {{ ucfirst($file->status) }}</p>

                            <!-- Tambahkan tombol aksi di dalam details-content untuk tampilan mobile -->
                            <div class="mobile-actions">
                                @if ($file->status === 'approved')
                                    <button class="btn btn-done" disabled>Done</button>
                                @else
                                    <form action="{{ route('admin.approve', $file->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="btn btn-approve" onclick="confirmAction(event, 'Apakah kamu yakin mau meng-approve ini?', this.closest('form'))">Approve</button>
                                    </form>
                                    <form action="{{ route('admin.reject', $file->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="btn btn-reject" onclick="confirmAction(event, 'Apakah kamu yakin mau menolak ini?', this.closest('form'))">Reject</button>
                                    </form>
                                    <form action="{{ route('admin.delete', $file->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-delete" onclick="confirmAction(event, 'Apakah kamu yakin mau menghapus ini?', this.closest('form'))">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

     @endif

        <!-- Pagination -->
        <div class="pagination">
            <ul class="pagination-list">
                @if ($submittedFiles->onFirstPage())
                    <li class="disabled"><span>Previous</span></li>
                @else
                    <li><a href="{{ $submittedFiles->previousPageUrl() }}">Previous</a></li>
                @endif

                @foreach ($submittedFiles->getUrlRange(1, $submittedFiles->lastPage()) as $page => $url)
                    <li class="{{ $submittedFiles->currentPage() == $page ? 'active' : '' }}">
                        <a href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach

                @if ($submittedFiles->hasMorePages())
                    <li><a href="{{ $submittedFiles->nextPageUrl() }}">Next</a></li>
                @else
                    <li class="disabled"><span>Next</span></li>
                @endif
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy;Student Academic Tracking. All rights reserved.
    </div>
</body>
</html>
