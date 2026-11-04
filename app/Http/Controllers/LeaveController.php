<?php

namespace App\Http\Controllers;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sakit,cuti',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        Leave::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan cuti/sakit berhasil dikirim.');
    }

public function updateStatus(Request $request, int $id)
{
    $request->validate([
        'status' => 'required|in:approved,rejected',
    ]);

    $leave = Leave::findOrFail($id);
    $leave->update(['status' => $request->status]);

    return redirect()->back()->with('success', 'Status pengajuan cuti berhasil diperbarui.');
}
}