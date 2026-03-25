<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\SosAlert;
use Illuminate\Http\Request;

class DriverSosController extends Controller
{
    public function index(Request $request)
    {
        $sender = $request->user();

        $alerts = SosAlert::where('sender_type', get_class($sender))
                          ->where('sender_id', $sender->id)
                          ->latest()
                          ->get();

        return response()->json(['success' => true, 'data' => $alerts]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lat'     => 'nullable|numeric',
            'lng'     => 'nullable|numeric',
            'trip_id' => 'nullable|exists:trips,id',
            'message' => 'nullable|string',
        ]);

        $sender = $request->user();

        $sos = SosAlert::create([
            'sender_type' => get_class($sender),
            'sender_id'   => $sender->id,
            'trip_id'     => $request->trip_id,
            'lat'         => $request->lat,
            'lng'         => $request->lng,
            'message'     => $request->message,
            'status'      => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alerte SOS envoyée.',
            'data'    => $sos,
        ], 201);
    }
}