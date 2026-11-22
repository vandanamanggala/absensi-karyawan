<?php

namespace App\Http\Controllers;

use App\Models\OfficeLocation;
use App\Models\Setting;
use Illuminate\Http\Request;

class OfficeLocationController extends Controller
{
    public function index()
    {
        $locations = OfficeLocation::latest()->get();
        $isLocationRequired = Setting::get('location_verification_enabled', '1') === '1';

        return view('owner.office_locations', compact('locations', 'isLocationRequired'));
    }

    public function toggleRequirement(Request $request)
    {
        $enabled = $request->boolean('location_verification_enabled') ? '1' : '0';
        Setting::set('location_verification_enabled', $enabled);

        $msg = $enabled === '1'
            ? 'Validasi radius lokasi kerja berhasil diaktifkan. Karyawan wajib berada di titik lokasi.'
            : 'Validasi radius lokasi kerja berhasil dinonaktifkan (fleksibel). Karyawan dapat absen dari mana saja.';

        return redirect()->back()->with('success', $msg);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'address' => 'nullable|string|max:500',
        ]);

        OfficeLocation::create([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Lokasi kerja berhasil ditambahkan!');
    }

    public function update(Request $request, OfficeLocation $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:5000',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $location->update([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'address' => $validated['address'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        return redirect()->back()->with('success', 'Lokasi kerja berhasil diperbarui!');
    }

    public function destroy(OfficeLocation $location)
    {
        $location->delete();
        return redirect()->back()->with('success', 'Lokasi kerja berhasil dihapus!');
    }

    public function getActiveLocations()
    {
        $locations = OfficeLocation::where('is_active', true)->get();
        $isLocationRequired = Setting::get('location_verification_enabled', '1') === '1';

        return response()->json([
            'status' => 'success',
            'is_location_required' => $isLocationRequired,
            'data' => $locations
        ]);
    }
}
