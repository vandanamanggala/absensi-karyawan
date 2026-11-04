<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi Karyawan</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }
        .card {
            border: 1px solid rgba(0,0,0,.05);
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04);
        }
        .btn-rounded {
            border-radius: 0.75rem;
        }
        .navbar {
            border-bottom: 1px solid rgba(0,0,0,.05);
        }
    </style>
</head>
<body>

    <!-- Simple Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="bi bi-clock-history me-2"></i>AbsensiApps
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2 mt-3 mt-lg-0">
                    <li class="nav-item">
                        <span class="nav-link text-muted small me-2">
                            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }} ({{ strtoupper(Auth::user()->role ?? 'Karyawan') }})
                        </span>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm btn-rounded px-3">
                                <i class="bi bi-box-arrow-right me-1"></i>Keluar
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4 py-lg-5">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>