<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Message;
use App\Models\SupportMessage;
use App\Models\User\User;
use App\Models\Driver\Driver;

class CompanyMessageController extends Controller
{
    /**
     * Liste les trajets (avec messages) des chauffeurs de la société.
     */
    public function index(Request $request)
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        $company   = \App\Support\CompanyContext::company();
        $driverIds = $company->drivers()->pluck('id');

        $query = Trip::whereIn('driver_id', $driverIds)
            ->withCount('messages')
            ->with(['driver:id,first_name,last_name,profile_photo', 'user:id,first_name,last_name,phone'])
            ->having('messages_count', '>', 0)
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('departure', 'like', "%$s%")
                  ->orWhere('destination', 'like', "%$s%");
            });
        }

        $trips = $query->paginate(20)->withQueryString();

        $totalTripsWithMessages = Trip::whereIn('driver_id', $driverIds)
            ->has('messages')->count();

        $totalMessages = Message::whereHas('trip', fn ($q) => $q->whereIn('driver_id', $driverIds))->count();

        return view('company.messages.index', compact(
            'trips', 'totalTripsWithMessages', 'totalMessages'
        ));
    }

    /**
     * Affiche la conversation client↔chauffeur d'un trajet.
     */
    public function show(Request $request, $tripId)
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        $company   = \App\Support\CompanyContext::company();
        $driverIds = $company->drivers()->pluck('id');

        $trip = Trip::whereIn('driver_id', $driverIds)
            ->with(['driver:id,first_name,last_name,profile_photo', 'user:id,first_name,last_name,phone'])
            ->findOrFail($tripId);

        $messages = Message::where('trip_id', $trip->id)
            ->oldest()
            ->get();

        return view('company.messages.show', compact('trip', 'messages'));
    }

    /**
     * Messages support des clients qui ont voyagé avec les chauffeurs de la société.
     */
    public function support(Request $request)
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        $company   = \App\Support\CompanyContext::company();
        $driverIds = $company->drivers()->pluck('id');

        // Récupérer les user_ids qui ont eu un trajet avec un chauffeur de la société
        $userIds = Trip::whereIn('driver_id', $driverIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        $query = User::whereIn('id', $userIds)
            ->withCount(['supportMessages as unread_count' => fn ($q) => $q->where('is_read', false)])
            ->with(['supportMessages' => fn ($q) => $q->where('refused', false)->latest()->limit(1)])
            ->having('unread_count', '>=', 0);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name',  'like', "%$s%")
                  ->orWhere('phone',      'like', "%$s%")
                  ->orWhere('email',      'like', "%$s%");
            });
        }

        $query->orderByRaw(
            '(SELECT COUNT(*) FROM support_messages WHERE (sender_type = ? AND sender_id = users.id) OR (recipient_type = ? AND recipient_id = users.id)) DESC',
            [\App\Models\User\User::class, \App\Models\User\User::class]
        )->orderBy('first_name');

        $users = $query->paginate(20)->withQueryString();

        // Conversation sélectionnée
        $selectedUser = null;
        $conversation = collect();

        if ($request->filled('user_id')) {
            $selectedUser = User::whereIn('id', $userIds)->findOrFail($request->user_id);
            $conversation = SupportMessage::where(function ($q) use ($selectedUser) {
                    $q->where('sender_type', User::class)->where('sender_id', $selectedUser->id);
                })
                ->orWhere(function ($q) use ($selectedUser) {
                    $q->where('recipient_type', User::class)->where('recipient_id', $selectedUser->id);
                })
                ->where('refused', false)
                ->oldest()
                ->get();
        }

        return view('company.messages.support', compact('users', 'selectedUser', 'conversation'));
    }
}
