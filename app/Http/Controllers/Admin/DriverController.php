<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name',  'like', '%' . $request->search . '%')
                  ->orWhere('phone',      'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $drivers = $query->paginate(15);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('admin.drivers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'required|string|max:100',
            'birth_date'           => 'required|date',
            'birth_place'          => 'required|string|max:100',
            'country_birth'        => 'required|string|max:100',
            'phone'                => 'required|string|unique:drivers,phone',
            'password'             => 'required|string|min:8|confirmed',
            'vehicle_plate'        => 'nullable|string|unique:drivers,vehicle_plate',
            'profile_photo'        => 'nullable|image|max:2048',
            'id_card_front'        => 'nullable|file|max:5120',
            'id_card_back'         => 'nullable|file|max:5120',
            'license_front'        => 'nullable|file|max:5120',
            'license_back'         => 'nullable|file|max:5120',
            'vehicle_registration' => 'nullable|file|max:5120',
            'insurance'            => 'nullable|file|max:5120',
        ]);

        $data = $request->except([
            'password', 'password_confirmation',
            'profile_photo', 'id_card_front', 'id_card_back',
            'license_front', 'license_back', 'vehicle_registration', 'insurance',
        ]);

        $data['password'] = Hash::make($request->password);

        // ── Upload fichiers → Backblaze B2 ────────────────────────
        foreach ($this->fileFields() as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->uploadToBackblaze(
                    $request->file($field),
                    'drivers/' . $field
                );
            }
        }

        // ── Géocodage automatique ─────────────────────────────────
        $coords = $this->geocode($request->vehicle_city, $request->vehicle_country);
        if ($coords) {
            $data['vehicle_lat'] = $coords['lat'];
            $data['vehicle_lng'] = $coords['lng'];
        }

        Driver::create($data);

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Chauffeur créé avec succès.' .
                ($coords ? ' Position GPS détectée.' : ' ⚠️ Position GPS non détectée.'));
    }

    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return view('admin.drivers.show', compact('driver'));
    }

    public function edit($id)
    {
        $driver = Driver::findOrFail($id);
        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'phone'         => 'required|string|unique:drivers,phone,' . $id,
            'vehicle_plate' => 'nullable|string|unique:drivers,vehicle_plate,' . $id,
            'password'      => 'nullable|string|min:8|confirmed',
        ]);

        $data = $request->except([
            'password', 'password_confirmation',
            'profile_photo', 'id_card_front', 'id_card_back',
            'license_front', 'license_back', 'vehicle_registration', 'insurance',
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // ── Upload fichiers → Backblaze B2 ────────────────────────
        foreach ($this->fileFields() as $field) {
            if ($request->hasFile($field)) {
                // Supprimer l'ancien fichier s'il existe sur Backblaze
                if ($driver->$field && str_contains($driver->$field, 'backblazeb2.com')) {
                    $this->deleteFromBackblaze($driver->$field);
                }

                $data[$field] = $this->uploadToBackblaze(
                    $request->file($field),
                    'drivers/' . $field
                );
            }
        }

        // ── Regéocodage si ville ou pays changé ──────────────────
        $cityChanged    = $request->vehicle_city    !== $driver->vehicle_city;
        $countryChanged = $request->vehicle_country !== $driver->vehicle_country;

        if ($cityChanged || $countryChanged || (!$driver->vehicle_lat && !$driver->vehicle_lng)) {
            $coords = $this->geocode($request->vehicle_city, $request->vehicle_country);
            if ($coords) {
                $data['vehicle_lat'] = $coords['lat'];
                $data['vehicle_lng'] = $coords['lng'];
            }
        }

        $driver->update($data);

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Chauffeur modifié avec succès.');
    }

    public function approve($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->update(['status' => 'approved']);
        return back()->with('success', $driver->first_name . ' a été approuvé.');
    }

    public function reject($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->update(['status' => 'rejected']);
        return back()->with('success', $driver->first_name . ' a été rejeté.');
    }

    public function suspend($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->update(['status' => 'suspended']);
        return back()->with('success', $driver->first_name . ' a été suspendu.');
    }

    public function activate($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->update(['status' => 'approved']);
        return back()->with('success', $driver->first_name . ' a été réactivé.');
    }

    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->tokens()->delete();
        $driver->delete();
        return back()->with('success', 'Chauffeur supprimé.');
    }

    // ================================================================
    // HELPERS UPLOAD BACKBLAZE
    // ================================================================

    /**
     * Upload un fichier vers Backblaze B2
     * Retourne l'URL publique complète au format S3 compatible
     *
     * ✅ URL générée : https://s3.us-west-004.backblazeb2.com/toptopgo2026/drivers/profile_photo/uuid.jpg
     */
    private function uploadToBackblaze($file, string $folder): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $folder . '/' . $filename;

        Storage::disk('backblaze')->put($path, file_get_contents($file), 'public');

        // ✅ Construction manuelle de l'URL — plus fiable que Storage::url()
        return rtrim(env('BACKBLAZE_ENDPOINT'), '/')
            . '/' . env('BACKBLAZE_BUCKET')
            . '/' . $path;
    }

    /**
     * Supprimer un fichier de Backblaze via son URL publique
     *
     * Gère les deux formats d'URL possibles :
     *  - Format S3  : https://s3.us-west-004.backblazeb2.com/toptopgo2026/drivers/...
     *  - Format natif B2 (ancien) : https://s3.us-west-004.backblazeb2.com/file/toptopgo2026/drivers/...
     */
    private function deleteFromBackblaze(string $url): void
    {
        try {
            $endpoint = rtrim(env('BACKBLAZE_ENDPOINT'), '/');
            $bucket   = env('BACKBLAZE_BUCKET');

            // Format S3 : endpoint/bucket/path
            $prefixS3 = $endpoint . '/' . $bucket . '/';
            // Format natif B2 : endpoint/file/bucket/path (ancien format stocké en base)
            $prefixB2 = $endpoint . '/file/' . $bucket . '/';

            if (str_starts_with($url, $prefixS3)) {
                $path = substr($url, strlen($prefixS3));
            } elseif (str_starts_with($url, $prefixB2)) {
                $path = substr($url, strlen($prefixB2));
            } else {
                return; // URL non reconnue, on ne fait rien
            }

            if ($path) {
                Storage::disk('backblaze')->delete($path);
            }
        } catch (\Exception $e) {
            // Silencieux — ne pas bloquer l'update si la suppression échoue
        }
    }

    private function fileFields(): array
    {
        return [
            'profile_photo', 'id_card_front', 'id_card_back',
            'license_front', 'license_back', 'vehicle_registration', 'insurance',
        ];
    }

    // ================================================================
    // GEOCODAGE
    // ================================================================

    private function geocode(?string $city, ?string $country): ?array
    {
        if (empty($city) && empty($country)) return null;

        $localDb = [
            'brazzaville'   => ['lat' => -4.2661,  'lng' => 15.2832],
            'pointe-noire'  => ['lat' => -4.7769,  'lng' => 11.8635],
            'pointe noire'  => ['lat' => -4.7769,  'lng' => 11.8635],
            'dolisie'       => ['lat' => -4.1987,  'lng' => 12.6670],
            'nkayi'         => ['lat' => -4.1757,  'lng' => 13.2836],
            'impfondo'      => ['lat' =>  1.6177,  'lng' => 18.0669],
            'ouesso'        => ['lat' =>  1.6136,  'lng' => 16.0503],
            'sibiti'        => ['lat' => -3.6833,  'lng' => 13.3500],
            'owando'        => ['lat' => -0.4833,  'lng' => 15.9000],
            'douala'        => ['lat' =>  4.0511,  'lng' =>  9.7679],
            'yaoundé'       => ['lat' =>  3.8480,  'lng' => 11.5021],
            'yaounde'       => ['lat' =>  3.8480,  'lng' => 11.5021],
            'bafoussam'     => ['lat' =>  5.4764,  'lng' => 10.4214],
            'garoua'        => ['lat' =>  9.3010,  'lng' => 13.3980],
            'bamenda'       => ['lat' =>  5.9597,  'lng' => 10.1460],
            'maroua'        => ['lat' => 10.5910,  'lng' => 14.3158],
            'ngaoundéré'    => ['lat' =>  7.3220,  'lng' => 13.5840],
            'ngaoundere'    => ['lat' =>  7.3220,  'lng' => 13.5840],
            'bertoua'       => ['lat' =>  4.5774,  'lng' => 13.6844],
            'ebolowa'       => ['lat' =>  2.9000,  'lng' => 11.1500],
            'kribi'         => ['lat' =>  2.9395,  'lng' =>  9.9072],
            'libreville'    => ['lat' =>  0.3901,  'lng' =>  9.4544],
            'port-gentil'   => ['lat' => -0.7193,  'lng' =>  8.7815],
            'port gentil'   => ['lat' => -0.7193,  'lng' =>  8.7815],
            'franceville'   => ['lat' => -1.6330,  'lng' => 13.5830],
            'oyem'          => ['lat' =>  1.5997,  'lng' => 11.5790],
            'kinshasa'      => ['lat' => -4.3217,  'lng' => 15.3222],
            'lubumbashi'    => ['lat' => -11.6609, 'lng' => 27.4794],
            'goma'          => ['lat' => -1.6793,  'lng' => 29.2228],
            'bangui'        => ['lat' =>  4.3612,  'lng' => 18.5550],
            "n'djamena"     => ['lat' => 12.1048,  'lng' => 15.0440],
            'ndjamena'      => ['lat' => 12.1048,  'lng' => 15.0440],
            'abidjan'       => ['lat' =>  5.3600,  'lng' => -4.0083],
            'dakar'         => ['lat' => 14.7167,  'lng' => -17.4677],
            'bamako'        => ['lat' => 12.6392,  'lng' => -8.0029],
            'ouagadougou'   => ['lat' => 12.3647,  'lng' => -1.5332],
            'niamey'        => ['lat' => 13.5137,  'lng' =>  2.1098],
            'cotonou'       => ['lat' =>  6.3654,  'lng' =>  2.4183],
            'lomé'          => ['lat' =>  6.1375,  'lng' =>  1.2123],
            'lome'          => ['lat' =>  6.1375,  'lng' =>  1.2123],
            'accra'         => ['lat' =>  5.5600,  'lng' => -0.2057],
            'lagos'         => ['lat' =>  6.5244,  'lng' =>  3.3792],
            'abuja'         => ['lat' =>  9.0765,  'lng' =>  7.3986],
            'luanda'        => ['lat' => -8.8368,  'lng' => 13.2343],
            'kigali'        => ['lat' => -1.9441,  'lng' => 30.0619],
            'nairobi'       => ['lat' => -1.2921,  'lng' => 36.8219],
        ];

        $cityKey = mb_strtolower(trim($city ?? ''));
        if (isset($localDb[$cityKey])) return $localDb[$cityKey];

        $capitalByCountry = [
            'congo'             => 'brazzaville',
            'congo brazzaville' => 'brazzaville',
            'cameroun'          => 'yaoundé',
            'cameroon'          => 'yaoundé',
            'gabon'             => 'libreville',
            'rdc'               => 'kinshasa',
            'centrafrique'      => 'bangui',
            'tchad'             => "n'djamena",
            'chad'              => "n'djamena",
            "côte d'ivoire"     => 'abidjan',
            'sénégal'           => 'dakar',
            'senegal'           => 'dakar',
            'mali'              => 'bamako',
            'burkina faso'      => 'ouagadougou',
            'niger'             => 'niamey',
            'bénin'             => 'cotonou',
            'benin'             => 'cotonou',
            'togo'              => 'lomé',
            'ghana'             => 'accra',
            'nigeria'           => 'abuja',
            'angola'            => 'luanda',
            'rwanda'            => 'kigali',
            'kenya'             => 'nairobi',
        ];

        $countryKey = mb_strtolower(trim($country ?? ''));
        if (isset($capitalByCountry[$countryKey])) {
            $capital = $capitalByCountry[$countryKey];
            if (isset($localDb[$capital])) return $localDb[$capital];
        }

        // Fallback Nominatim
        $query = trim(implode(', ', array_filter([$city, $country])));
        try {
            $response = Http::timeout(4)
                ->withHeaders(['User-Agent' => 'TopTopGo-Admin/1.0'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query, 'format' => 'json', 'limit' => 1,
                ]);

            if ($response->ok() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return ['lat' => (float) $result['lat'], 'lng' => (float) $result['lon']];
            }
        } catch (\Exception $e) {}

        return null;
    }
}