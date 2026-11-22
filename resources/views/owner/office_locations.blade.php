@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    .map-picker-container {
        height: 240px;
        width: 100%;
        border-radius: 0.75rem;
        z-index: 1;
    }
</style>
@endpush

@section('content')

<!-- Header Opsi Lokasi & Toggle Switch -->
<div class="mobile-card">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Lokasi Kerja</h5>
            <span class="text-muted small" style="font-size: 0.72rem;">Titik kantor dan radius toleransi</span>
        </div>
        <button class="btn btn-sm btn-primary btn-rounded px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalAddLocation">
            <i class="bi bi-plus-lg me-1"></i> Tambah
        </button>
    </div>

    <!-- Toggle Status Aturan -->
    <div class="p-2.5 rounded-3 {{ $isLocationRequired ? 'bg-primary bg-opacity-10 border border-primary border-opacity-25' : 'bg-light border' }} mt-2">
        <div class="d-flex align-items-center justify-content-between">
            <div class="me-2">
                <span class="fw-bold small d-block {{ $isLocationRequired ? 'text-primary' : 'text-dark' }}">
                    {{ $isLocationRequired ? 'Validasi Radius Aktif' : 'Lokasi Fleksibel (WFH)' }}
                </span>
                <span class="text-muted" style="font-size: 0.7rem;">
                    {{ $isLocationRequired ? 'Karyawan wajib berada di titik lokasi kantor.' : 'Karyawan bebas absen dari mana saja.' }}
                </span>
            </div>
            <form action="{{ route('owner.office.toggle') }}" method="POST" class="m-0 flex-shrink-0">
                @csrf
                <input type="hidden" name="location_verification_enabled" value="{{ $isLocationRequired ? '0' : '1' }}">
                <button type="submit" class="btn btn-sm {{ $isLocationRequired ? 'btn-outline-danger' : 'btn-success' }} btn-rounded px-2.5 py-1" style="font-size: 0.75rem;">
                    {{ $isLocationRequired ? 'Matikan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>
</div>

<!-- List Lokasi Kerja dalam Card Items -->
<div class="mobile-card">
    <div class="section-title">
        <span>Daftar Titik Kantor</span>
        <span class="badge badge-soft-secondary rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">{{ $locations->count() }} Lokasi</span>
    </div>

    <div class="d-flex flex-column gap-2">
        @forelse($locations as $loc)
            <div class="p-2.5 rounded-3 border bg-light">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <strong class="text-dark small d-flex align-items-center gap-1.5">
                        <i class="bi bi-building text-primary"></i> {{ $loc->name }}
                    </strong>
                    @if($loc->is_active)
                        <span class="badge badge-soft-success px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">Aktif</span>
                    @else
                        <span class="badge badge-soft-danger px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">Nonaktif</span>
                    @endif
                </div>

                <div class="text-muted mb-2" style="font-size: 0.72rem;">
                    <i class="bi bi-geo-alt me-1"></i>{{ $loc->address ?? 'Alamat belum diatur' }}<br>
                    <span class="text-secondary">Radius: <strong>{{ $loc->radius_meters }}m</strong> &bull; Lat: {{ $loc->latitude }}, Long: {{ $loc->longitude }}</span>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-1.5 pt-1 border-top">
                    <button class="btn btn-sm btn-light border btn-rounded py-1 px-2.5 text-primary" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalEditLocation{{ $loc->id }}">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    <form action="{{ route('owner.office.destroy', $loc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus lokasi ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger btn-rounded py-1 px-2.5" style="font-size: 0.75rem;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal Edit Lokasi Mobile Friendly -->
            <div class="modal fade" id="modalEditLocation{{ $loc->id }}" tabindex="-1" aria-hidden="true" data-loc-id="{{ $loc->id }}" data-lat="{{ $loc->latitude }}" data-lng="{{ $loc->longitude }}" data-radius="{{ $loc->radius_meters }}">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 p-3">
                        <form action="{{ route('owner.office.update', $loc->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header border-0 pb-0">
                                <h6 class="modal-title fw-bold">Edit Lokasi</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Nama Kantor</label>
                                    <input type="text" name="name" class="form-control rounded-3" value="{{ $loc->name }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Tentukan Titik di Peta</label>
                                    <div id="mapEdit{{ $loc->id }}" class="map-picker-container border"></div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <input type="number" step="any" id="editLat{{ $loc->id }}" name="latitude" class="form-control form-control-sm rounded-3 bg-light" value="{{ $loc->latitude }}" required readonly>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="any" id="editLong{{ $loc->id }}" name="longitude" class="form-control form-control-sm rounded-3 bg-light" value="{{ $loc->longitude }}" required readonly>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Radius Toleransi (Meter)</label>
                                    <input type="number" id="editRadius{{ $loc->id }}" name="radius_meters" class="form-control rounded-3" value="{{ $loc->radius_meters }}" min="10" max="5000" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Alamat</label>
                                    <textarea id="editAddress{{ $loc->id }}" name="address" class="form-control rounded-3" rows="2">{{ $loc->address }}</textarea>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwitch{{ $loc->id }}" {{ $loc->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="activeSwitch{{ $loc->id }}">Status Aktif</label>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light btn-rounded px-3" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary btn-rounded px-3">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted small">
                Belum ada lokasi kerja yang disimpan.
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Tambah Lokasi Mobile Friendly -->
<div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 p-3">
            <form action="{{ route('owner.office.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Tambah Lokasi Kantor</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nama Lokasi</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Kantor Pusat" required>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Geser Pin di Peta</label>
                            <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" style="font-size: 0.72rem;" onclick="useCurrentLocationForAdd()">
                                Lokasi Saya
                            </button>
                        </div>
                        <div id="mapAdd" class="map-picker-container border"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="number" step="any" id="addLat" name="latitude" class="form-control form-control-sm rounded-3 bg-light" placeholder="Lat" required readonly>
                        </div>
                        <div class="col-6">
                            <input type="number" step="any" id="addLong" name="longitude" class="form-control form-control-sm rounded-3 bg-light" placeholder="Long" required readonly>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Radius Toleransi (Meter)</label>
                        <input type="number" id="addRadius" name="radius_meters" class="form-control rounded-3" value="100" min="10" max="5000" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Alamat</label>
                        <textarea id="addAddress" name="address" class="form-control rounded-3" rows="2" placeholder="Jl..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-rounded px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-rounded px-3">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let addMap, addMarker, addCircle;
    const defaultLat = -6.200000;
    const defaultLng = 106.816666;

    async function reverseGeocode(lat, lng, textareaId) {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=id`);
            if (res.ok) {
                const data = await res.json();
                if (data && data.display_name) {
                    const el = document.getElementById(textareaId);
                    if (el) el.value = data.display_name;
                }
            }
        } catch (e) {}
    }

    function initAddMap() {
        if (addMap) {
            addMap.invalidateSize();
            return;
        }

        const mapContainer = document.getElementById('mapAdd');
        if (!mapContainer) return;

        addMap = L.map('mapAdd').setView([defaultLat, defaultLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(addMap);

        addMarker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(addMap);
        addCircle = L.circle([defaultLat, defaultLng], {
            radius: 100,
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.2
        }).addTo(addMap);

        document.getElementById('addLat').value = defaultLat.toFixed(7);
        document.getElementById('addLong').value = defaultLng.toFixed(7);

        function updateAddPin(lat, lng) {
            addMarker.setLatLng([lat, lng]);
            addCircle.setLatLng([lat, lng]);
            document.getElementById('addLat').value = lat.toFixed(7);
            document.getElementById('addLong').value = lng.toFixed(7);
            reverseGeocode(lat, lng, 'addAddress');
        }

        addMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            updateAddPin(pos.lat, pos.lng);
        });

        addMap.on('click', function (e) {
            updateAddPin(e.latlng.lat, e.latlng.lng);
        });

        document.getElementById('addRadius').addEventListener('input', function(e) {
            const rad = parseInt(e.target.value) || 100;
            addCircle.setRadius(rad);
        });
    }

    function useCurrentLocationForAdd() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            if (addMap && addMarker && addCircle) {
                addMap.setView([lat, lng], 16);
                addMarker.setLatLng([lat, lng]);
                addCircle.setLatLng([lat, lng]);
                document.getElementById('addLat').value = lat.toFixed(7);
                document.getElementById('addLong').value = lng.toFixed(7);
                reverseGeocode(lat, lng, 'addAddress');
            }
        }, null, { enableHighAccuracy: true });
    }

    const editMaps = {};

    function initEditMap(locId, initLat, initLng, initRadius) {
        if (editMaps[locId]) {
            editMaps[locId].map.invalidateSize();
            return;
        }

        const mapEl = document.getElementById(`mapEdit${locId}`);
        if (!mapEl) return;

        const map = L.map(`mapEdit${locId}`).setView([initLat, initLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);
        const circle = L.circle([initLat, initLng], {
            radius: initRadius,
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.2
        }).addTo(map);

        editMaps[locId] = { map, marker, circle };

        function updateEditPin(lat, lng) {
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
            document.getElementById(`editLat${locId}`).value = lat.toFixed(7);
            document.getElementById(`editLong${locId}`).value = lng.toFixed(7);
            reverseGeocode(lat, lng, `editAddress${locId}`);
        }

        marker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            updateEditPin(pos.lat, pos.lng);
        });

        map.on('click', function (e) {
            updateEditPin(e.latlng.lat, e.latlng.lng);
        });

        const radInput = document.getElementById(`editRadius${locId}`);
        if (radInput) {
            radInput.addEventListener('input', function (e) {
                const rad = parseInt(e.target.value) || 100;
                circle.setRadius(rad);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalAdd = document.getElementById('modalAddLocation');
        if (modalAdd) {
            modalAdd.addEventListener('shown.bs.modal', function () {
                initAddMap();
            });
        }

        document.querySelectorAll('[id^="modalEditLocation"]').forEach((modalEl) => {
            modalEl.addEventListener('shown.bs.modal', function () {
                const locId = this.getAttribute('data-loc-id');
                const lat = parseFloat(this.getAttribute('data-lat')) || defaultLat;
                const lng = parseFloat(this.getAttribute('data-lng')) || defaultLng;
                const radius = parseInt(this.getAttribute('data-radius')) || 100;
                initEditMap(locId, lat, lng, radius);
            });
        });
    });
</script>
@endpush
