<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateDocumentsRequest;
use App\Http\Resources\Driver\DriverResource;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use App\Models\Review;
use Illuminate\Support\Facades\Storage;

class DriverProfileController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

    public function show(Request $request)
    {
        $driver = $request->user()->load('wallet', 'latestLocation');

        $reviews = Review::where('driver_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $avgRating = $reviews->avg('rating') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id'         => $driver->id,
                'first_name' => $driver->first_name,
                'last_name'  => $driver->last_name,
                'email'      => $driver->email,
                'phone'      => $driver->phone,

                'profile_photo' => $driver->profile_photo
                    ? asset('storage/' . $driver->profile_photo)
                    : null,

                // ✅ FIX: tous les champs véhicule retournés
                'vehicle_brand'   => $driver->vehicle_brand,
                'vehicle_model'   => $driver->vehicle_model,
                'vehicle_color'   => $driver->vehicle_color,
                'vehicle_plate'   => $driver->vehicle_plate,   // ← ajouté
                'vehicle_year'    => $driver->vehicle_year,    // ← ajouté
                'vehicle_type'    => $driver->vehicle_type,    // ← ajouté
                'vehicle_country' => $driver->vehicle_country,
                'vehicle_city'    => $driver->vehicle_city,

                'status'        => $driver->status        ?? 'pending',
                'driver_status' => $driver->driver_status ?? 'offline',
                'is_verified'   => (bool) ($driver->is_verified ?? false),

                // ✅ Notes réelles
                'average_rating' => round($avgRating, 1),
                'rating_count'   => $reviews->count(),

                // ✅ 10 derniers avis avec nom du client
                'reviews' => $reviews->take(10)->map(fn($r) => [
                    'id'          => $r->id,
                    'rating'      => $r->rating,
                    'comment'     => $r->comment ?? '',
                    'client_name' => $r->user
                        ? trim(($r->user->first_name ?? '') . ' ' . ($r->user->last_name ?? ''))
                        : ($r->client_name ?? 'Client'),
                    'created_at'  => $r->created_at,
                ]),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'first_name'      => 'sometimes|string|max:100',
            'last_name'       => 'sometimes|string|max:100',
            'vehicle_brand'   => 'sometimes|string|max:100',
            'vehicle_model'   => 'sometimes|string|max:100',
            'vehicle_color'   => 'sometimes|string|max:50',
            'vehicle_plate'   => 'sometimes|string|max:20',   // ← ajouté
            'vehicle_year'    => 'sometimes|integer|min:1990|max:2030', // ← ajouté
            'vehicle_type'    => 'sometimes|string|max:50',   // ← ajouté
            'vehicle_country' => 'sometimes|string|max:100',
            'vehicle_city'    => 'sometimes|string|max:100',
        ]);

        $request->user()->update($request->only([
            'first_name',
            'last_name',
            'vehicle_brand',
            'vehicle_model',
            'vehicle_color',
            'vehicle_plate',   // ← ajouté
            'vehicle_year',    // ← ajouté
            'vehicle_type',    // ← ajouté
            'vehicle_country',
            'vehicle_city',
        ]));

        return new DriverResource($request->user()->fresh());
    }

    public function updateDocuments(UpdateDocumentsRequest $request)
    {
        $driver = $request->user();
        $data   = [];

        $fields = [
            'id_card_front',
            'id_card_back',
            'license_front',
            'license_back',
            'vehicle_registration',
            'insurance',
        ];

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->fileUploadService->uploadDocument(
                    $request->file($field),
                    $driver->id,
                    $field
                );
            }
        }

        $driver->update($data);

        return response()->json([
            'message' => 'Documents mis à jour. En attente de validation.',
            'driver'  => new DriverResource($driver->fresh()),
        ]);
    }

    // ✅ Méthode existante — route corrigée dans api.php pour pointer ici
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120'
        ]);

        $driver = $request->user();

        if ($driver->profile_photo) {
            Storage::disk('public')->delete($driver->profile_photo);
        }

        $path = $request->file('photo')->store('drivers/photos', 'public');

        $driver->update(['profile_photo' => $path]);

        return response()->json([
            'success'       => true,
            'message'       => 'Photo mise à jour.',
            'profile_photo' => asset('storage/' . $path)
        ]);
    }
}