<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Absensi Karyawan') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --bg-body: #f1f5f9;
            --card-border: #e2e8f0;
            --text-title: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            -webkit-font-smoothing: antialiased;
            margin: 0;
            padding: 0;
        }

        /* Modern App Frame (Card Shell) */
        .app-shell-container {
            max-width: 520px;
            margin: 0 auto;
            min-height: 100vh;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        @media (min-width: 768px) {
            .app-shell-container {
                margin: 24px auto;
                min-height: calc(100vh - 48px);
                border-radius: 2rem;
                overflow: hidden;
                border: 1px solid var(--card-border);
            }
        }

        /* Top Header Styling */
        .app-topbar {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: #ffffff;
            padding: 1.25rem 1.25rem 1.5rem 1.25rem;
            position: relative;
            z-index: 20;
        }

        .btn-rounded {
            border-radius: 9999px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .btn-sm.btn-rounded {
            min-height: 38px;
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .badge-soft-primary { background-color: #eff6ff; color: #2563eb; }
        .badge-soft-success { background-color: #f0fdf4; color: #16a34a; }
        .badge-soft-warning { background-color: #fffbeb; color: #d97706; }
        .badge-soft-danger  { background-color: #fef2f2; color: #dc2626; }
        .badge-soft-info    { background-color: #f0f9ff; color: #0284c7; }
        .badge-soft-secondary { background-color: #f1f5f9; color: #475569; }

        .avatar-initial {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        /* Content Area */
        .app-content {
            flex: 1 1 auto;
            padding: 1.25rem;
            padding-bottom: 95px; /* space for bottom navigation bar */
        }

        /* App-Style Bottom Navigation Bar */
        .app-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 520px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--card-border);
            z-index: 1040;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        @media (min-width: 768px) {
            .app-bottom-nav {
                border-bottom-left-radius: 2rem;
                border-bottom-right-radius: 2rem;
            }
        }

        .app-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            border-radius: 1rem;
            transition: all 0.2s ease;
            min-height: 44px;
        }

        .app-nav-item i {
            font-size: 1.35rem;
            margin-bottom: 2px;
            transition: transform 0.2s ease;
        }

        .app-nav-item.active {
            color: var(--primary);
            background: #eff6ff;
        }

        .app-nav-item.active i {
            transform: translateY(-2px);
        }

        /* Card System */
        .mobile-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 1.25rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            margin-bottom: 1rem;
            padding: 1.15rem;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
    @stack('styles')
</head>
<body>

    <div class="app-shell-container">
        
        <!-- App Top Bar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('profile.edit') }}" class="text-decoration-none">
                        <div class="avatar-initial">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    </a>
                    <div>
                        <div class="text-white-50 small leading-tight" style="font-size: 0.78rem;">Selamat Datang,</div>
                        <h5 class="fw-bold text-white mb-0">{{ Auth::user()->name }}</h5>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2.5 py-1 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        {{ Auth::user()->role === 'owner' ? 'Owner' : (Auth::user()->position ?? 'Karyawan') }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-link text-white p-1 text-decoration-none" title="Keluar">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="app-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-3 small" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center gap-2 mb-3 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-3 small" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perhatian:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- App Bottom Navigation Bar -->
        <nav class="app-bottom-nav">
            <a href="{{ route('attendance.index') }}" class="app-nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Presensi</span>
            </a>

            <a href="{{ route('face.registration') }}" class="app-nav-item {{ request()->routeIs('face.registration') ? 'active' : '' }}">
                <i class="bi bi-person-bounding-box"></i>
                <span>Wajah</span>
            </a>

            <a href="{{ route('leave.index') }}" class="app-nav-item {{ request()->routeIs('leave.*') ? 'active' : '' }}">
                <i class="bi bi-envelope-paper"></i>
                <span>Izin/Cuti</span>
            </a>

            @if(Auth::user()->role === 'owner')
                <a href="{{ route('owner.dashboard') }}" class="app-nav-item {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Monitor</span>
                </a>
                <a href="{{ route('owner.office.locations') }}" class="app-nav-item {{ request()->routeIs('owner.office.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i>
                    <span>Lokasi</span>
                </a>
            @endif

            <a href="{{ route('profile.edit') }}" class="app-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-person"></i>
                <span>Profil</span>
            </a>
        </nav>

    </div>

    <!-- Bootstrap 5.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
