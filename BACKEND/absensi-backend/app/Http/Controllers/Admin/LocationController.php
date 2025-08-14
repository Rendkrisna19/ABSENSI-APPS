<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::latest()->paginate(10);
        return view('admin.locations.index', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
            'radius'  => 'required|integer|min:1',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Jika tidak ada lat/lng, geocode dari address
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            [$lat, $lng] = $this->geocodeAddress($validated['address']);
            $validated['latitude']  = $lat;
            $validated['longitude'] = $lng;
        }

        Location::create($validated);

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string',
            'radius'  => 'required|integer|min:1',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Jika tidak ada lat/lng, geocode ulang dari address
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            [$lat, $lng] = $this->geocodeAddress($validated['address']);
            $validated['latitude']  = $lat;
            $validated['longitude'] = $lng;
        }

        $location->update($validated);

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil dihapus.');
    }

    /**
     * Geocode alamat via Nominatim (OpenStreetMap).
     * Kembalikan array [lat, lng]. Jika gagal -> null, null.
     */
    private function geocodeAddress(string $address): array
    {
        try {
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->ok() && !empty($response[0])) {
                $lat = (float) ($response[0]['lat'] ?? null);
                $lng = (float) ($response[0]['lon'] ?? null);
                return [$lat ?: null, $lng ?: null];
            }
        } catch (\Throwable $e) {
            // log kalau perlu: \Log::warning('Geocode gagal: '.$e->getMessage());
        }

        return [null, null];
    }
}
