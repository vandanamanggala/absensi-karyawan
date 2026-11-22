@extends('layouts.app')

@section('content')

<!-- Header Profile Summary Card -->
<div class="mobile-card text-center py-3">
    <div class="avatar-initial mx-auto mb-2" style="width: 64px; height: 64px; font-size: 1.5rem; background: linear-gradient(135deg, #1d4ed8, #2563eb);">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
    <h5 class="fw-bold text-dark mb-0">{{ $user->name }}</h5>
    <div class="text-muted small mb-2">{{ $user->email }}</div>
    <div>
        <span class="badge bg-primary text-white rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.72rem;">
            {{ $user->role === 'owner' ? 'Owner' : ($user->position ?? 'Karyawan') }}
        </span>
    </div>
</div>

<!-- Atribut Profil Karyawan -->
<div class="mobile-card">
    <div class="section-title">
        <span>Informasi Karyawan</span>
    </div>
    <div class="row g-2">
        <div class="col-6">
            <div class="p-2.5 rounded-3 bg-light border">
                <span class="text-muted small d-block" style="font-size: 0.7rem;">Jabatan</span>
                <strong class="text-dark small">{{ $user->position ?? 'Staff' }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2.5 rounded-3 bg-light border">
                <span class="text-muted small d-block" style="font-size: 0.7rem;">Tgl Bergabung</span>
                <strong class="text-dark small">{{ $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('d M Y') : '-' }}</strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2.5 rounded-3 bg-light border">
                <span class="text-muted small d-block" style="font-size: 0.7rem;">Uang Makan / Hari</span>
                <strong class="text-success small">
                    @if($user->meal_allowance_per_day > 0)
                        Rp {{ number_format($user->meal_allowance_per_day, 0, ',', '.') }}
                    @else
                        -
                    @endif
                </strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2.5 rounded-3 bg-light border">
                <span class="text-muted small d-block" style="font-size: 0.7rem;">Biometrik Wajah</span>
                @if($user->hasRegisteredFace())
                    <span class="badge badge-soft-success px-2 py-0.5 rounded-pill" style="font-size: 0.7rem;">Terdaftar</span>
                @else
                    <a href="{{ route('face.registration') }}" class="badge badge-soft-warning px-2 py-0.5 rounded-pill text-decoration-none" style="font-size: 0.7rem;">Belum</a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Form Update Data Profil -->
<div class="mobile-card">
    <div class="section-title">
        <span>Perbarui Profil</span>
    </div>
    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-2.5">
            <label class="form-label small fw-semibold text-muted">Nama Lengkap</label>
            <input type="text" name="name" class="form-control rounded-3" value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-semibold text-muted">Email</label>
            <input type="email" name="email" class="form-control rounded-3" value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-semibold text-muted">No. WhatsApp</label>
            <input type="text" name="wa_number" class="form-control rounded-3" value="{{ old('wa_number', $user->wa_number) }}">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Alamat Domisili</label>
            <textarea name="address" rows="2" class="form-control rounded-3">{{ old('address', $user->address) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-rounded w-100 py-2.5">
            Simpan Perubahan
        </button>
    </form>
</div>

<!-- Update Password -->
<div class="mobile-card">
    <div class="section-title">
        <span>Ganti Password</span>
    </div>
    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-2.5">
            <label class="form-label small fw-semibold text-muted">Password Saat Ini</label>
            <input type="password" name="current_password" class="form-control rounded-3" required autocomplete="current-password">
        </div>

        <div class="mb-2.5">
            <label class="form-label small fw-semibold text-muted">Password Baru</label>
            <input type="password" name="password" class="form-control rounded-3" required autocomplete="new-password">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation" class="form-control rounded-3" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-dark btn-rounded w-100 py-2.5">
            Perbarui Password
        </button>
    </form>
</div>

@endsection
