<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Absensi Karyawan') }} - Masuk</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-shell-container {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
            margin: auto;
        }

        .login-card {
            background: #ffffff;
            border-radius: 1.5rem;
            border: 1px solid var(--card-border);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .login-header-banner {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: #ffffff;
            padding: 2.25rem 1.75rem;
            text-align: center;
        }

        .login-logo-circle {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            margin: 0 auto 1rem auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .btn-rounded {
            border-radius: 9999px;
            min-height: 48px;
            font-weight: 700;
        }

        .form-control {
            border-radius: 0.75rem;
            min-height: 46px;
            border-color: #cbd5e1;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
    </style>
</head>
<body>

    <div class="login-shell-container">
        <div class="login-card">
            
            <!-- Top Header Banner -->
            <div class="login-header-banner">
                <div class="login-logo-circle">
                    <i class="bi bi-fingerprint"></i>
                </div>
                <h4 class="fw-bold mb-1 text-white">Absensi<span style="opacity: 0.85;">App</span></h4>
                <p class="mb-0 text-white-50 small">Sistem Presensi GPS & Biometrik Wajah AI</p>
            </div>

            <!-- Form Body -->
            <div class="p-4 p-md-4">
                <div class="mb-3 text-center">
                    <h5 class="fw-bold text-dark mb-1">Masuk ke Akun</h5>
                    <p class="text-muted small mb-0">Silakan masukkan email dan kata sandi Anda</p>
                </div>

                <!-- Alert Status / Error -->
                @if (session('status'))
                    <div class="alert alert-success border-0 rounded-3 small py-2 mb-3">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 small py-2 mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Input -->
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-muted">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control border-start-0 rounded-end-3" placeholder="nama@perusahaan.com" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-semibold text-muted mb-0">Kata Sandi</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none" style="font-size: 0.75rem;">
                                    Lupa Password?
                                </a>
                            @endif
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input id="password" type="password" name="password" class="form-control border-start-0 rounded-end-3" placeholder="••••••••" required autocomplete="current-password">
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                        <label class="form-check-label small text-muted" for="remember_me">
                            Ingat sesi saya
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-rounded w-100 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <span>Masuk ke Sistem</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <span class="text-muted small" style="font-size: 0.75rem;">AbsensiApp &copy; {{ date('Y') }} &bull; All Rights Reserved</span>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
