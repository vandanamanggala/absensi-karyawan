@extends('layouts.app')

@section('content')

<!-- Header & Tombol Buat Pengajuan -->
<div class="mobile-card">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <div>
            <h5 class="fw-bold text-dark mb-0">Status Pengajuan Izin / Cuti</h5>
            <span class="text-muted small" style="font-size: 0.72rem;">Pantau persetujuan permohonan Anda oleh Owner</span>
        </div>
        <button class="btn btn-sm btn-primary btn-rounded px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#formLeaveModal">
            <i class="bi bi-plus-lg me-1"></i> Ajukan
        </button>
    </div>
</div>

<!-- Daftar Status Pengajuan Karyawan -->
<div class="mobile-card">
    <div class="section-title">
        <span>Riwayat Pengajuan Saya</span>
        <span class="badge badge-soft-secondary rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">{{ $leaves->total() }} Total</span>
    </div>

    <div class="d-flex flex-column gap-2.5">
        @forelse($leaves as $leave)
            <div class="p-3 rounded-3 border bg-light">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="badge {{ $leave->type === 'cuti' ? 'badge-soft-primary' : 'badge-soft-warning' }} text-uppercase px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.7rem;">
                        <i class="bi {{ $leave->type === 'cuti' ? 'bi-calendar-check' : 'bi-heart-pulse' }} me-1"></i>
                        {{ $leave->type === 'cuti' ? 'Cuti Tahunan / Khusus' : 'Izin Sakit' }}
                    </span>

                    @if($leave->status === 'approved')
                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill small">
                            <i class="bi bi-check-circle-fill me-1"></i> Disetujui (Approved)
                        </span>
                    @elseif($leave->status === 'rejected')
                        <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill small">
                            <i class="bi bi-x-circle-fill me-1"></i> Ditolak (Rejected)
                        </span>
                    @else
                        <span class="badge badge-soft-warning px-2.5 py-1 rounded-pill small">
                            <i class="bi bi-hourglass-split me-1"></i> Menunggu Persetujuan
                        </span>
                    @endif
                </div>

                <div class="text-dark small mb-1">
                    <i class="bi bi-calendar-event me-1 text-primary"></i>
                    <strong>{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }}</strong>
                    s.d.
                    <strong>{{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</strong>
                </div>

                <div class="text-muted small mb-2 p-2 rounded-2 bg-white border" style="font-size: 0.75rem;">
                    <em>"{{ $leave->reason }}"</em>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="font-size: 0.72rem;">
                    <span class="text-muted">Diajukan pada {{ $leave->created_at->format('d M Y, H:i') }}</span>

                    @if($leave->status !== 'rejected')
                        <form action="{{ route('leave.cancel', $leave->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permohonan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-rounded px-2.5 py-1" style="font-size: 0.72rem;">
                                <i class="bi bi-x-circle me-1"></i> Batalkan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-envelope-paper fs-3 d-block text-secondary mb-2"></i>
                Belum ada pengajuan izin atau cuti yang dibuat.
            </div>
        @endforelse
    </div>

    @if($leaves->hasPages())
        <div class="mt-3">
            {{ $leaves->links() }}
        </div>
    @endif
</div>

<!-- Modal Form Pengajuan Baru -->
<div class="modal fade" id="formLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 p-3">
            <form action="{{ route('leave.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Buat Pengajuan Cuti / Izin</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold">Jenis Pengajuan</label>
                        <select name="type" class="form-select rounded-3" required>
                            <option value="cuti">Cuti Tahunan / Khusus</option>
                            <option value="sakit">Sakit (Dengan Keterangan)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan / Keterangan</label>
                        <textarea name="reason" rows="3" class="form-control rounded-3" placeholder="Tuliskan keterangan lengkap pengajuan cuti/sakit..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-rounded px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-rounded px-4">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
