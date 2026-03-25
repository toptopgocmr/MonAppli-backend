<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Trip;
use App\Models\User\User;
use App\Models\Driver\Driver;

class AdminMessageController extends Controller
{
    /**
     * Affiche toutes les conversations Users ↔ Chauffeurs
     * avec filtrage optionnel par user_id ou driver_id
     */
    public function index(Request $request)
    {
        $query = Trip::whereHas('messages')
            ->with(['user', 'driver', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        $trips   = $query->latest()->paginate(20);
        $users   = User::orderBy('first_name')->get();
        $drivers = Driver::orderBy('first_name')->get();

        $totalMessages          = Message::count();
        $unreadMessages         = Message::where('is_read', false)->count();
        $totalTripsWithMessages = Trip::whereHas('messages')->count();

        return view('admin.messages.user-driver', compact(
            'trips', 'users', 'drivers',
            'totalMessages', 'unreadMessages', 'totalTripsWithMessages'
        ));
    }

    /**
     * Affiche le détail d'une conversation (trip)
     */
    public function show(Request $request, $tripId)
    {
        $trip = Trip::with(['user', 'driver'])->findOrFail($tripId);

        $messages = Message::where('trip_id', $tripId)
            ->oldest()
            ->get();

        $query = Trip::whereHas('messages')
            ->with(['user', 'driver', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        $trips   = $query->latest()->paginate(20);
        $users   = User::orderBy('first_name')->get();
        $drivers = Driver::orderBy('first_name')->get();

        $totalMessages          = Message::count();
        $unreadMessages         = Message::where('is_read', false)->count();
        $totalTripsWithMessages = Trip::whereHas('messages')->count();

        return view('admin.messages.user-driver', compact(
            'trip', 'trips', 'messages', 'users', 'drivers',
            'totalMessages', 'unreadMessages', 'totalTripsWithMessages'
        ));
    }
}