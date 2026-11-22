<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    /**
     * Tampilkan halaman riwayat & status pengajuan cuti/izin karyawan.
     */
    public function index()
    {
        $leaves = Leave::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('leave.index', compact('leaves'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sakit,cuti',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        Leave::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('leave.index')->with('success', 'Pengajuan izin atau cuti berhasil dikirim dan menunggu persetujuan owner.');
    }

    /**
     * Karyawan membatalkan pengajuan cuti / izin miliknya.
     */
    public function cancel(int $id)
    {
        $leave = Leave::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($leave->status === 'rejected') {
            return redirect()->back()->with('error', 'Pengajuan yang sudah ditolak tidak perlu dibatalkan.');
        }

        $wasApproved = ($leave->status === 'approved');

        // Jika sebelumnya disetujui, kembalikan record absensi
        if ($wasApproved) {
            AttendanceService::revertLeaveAttendances($leave);
        }

        $leave->delete();

        return redirect()->back()->with('success', 'Pengajuan izin/cuti berhasil dibatalkan.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leave = Leave::findOrFail($id);
        $previousStatus = $leave->status;
        $leave->update(['status' => $request->status]);

        if ($request->status === 'approved') {
            AttendanceService::applyApprovedLeave($leave);
        } elseif ($request->status === 'rejected' && $previousStatus === 'approved') {
            AttendanceService::revertLeaveAttendances($leave);
        }

        $message = $request->status === 'approved' 
            ? 'Pengajuan izin atau cuti berhasil disetujui dan status presensi otomatis diperbarui.' 
            : 'Pengajuan izin atau cuti telah ditolak.';

        return redirect()->back()->with('success', $message);
    }
}
