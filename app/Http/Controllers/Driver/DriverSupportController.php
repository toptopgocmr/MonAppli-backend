<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use App\Models\Driver\Driver;
use App\Models\Admin\AdminUser;
use App\Services\Realtime\PusherBroadcaster;

class DriverSupportController extends Controller
{
    /**
     * Liste les messages entre le chauffeur et l'admin
     */
    public function index(Request $request)
    {
        $driverId = auth()->id();

        $messages = SupportMessage::where(function ($q) use ($driverId) {
                $q->where('recipient_type', Driver::class)
                  ->where('recipient_id', $driverId)
                  ->where('sender_type', AdminUser::class);
            })
            ->orWhere(function ($q) use ($driverId) {
                $q->where('sender_type', Driver::class)
                  ->where('sender_id', $driverId)
                  ->where('recipient_type', AdminUser::class);
            })
            ->with('sender', 'recipient')
            ->oldest()
            ->get();

        // Marquer comme lus tous les messages reçus par le chauffeur
        SupportMessage::where('recipient_type', Driver::class)
            ->where('recipient_id', $driverId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'messages' => $messages
        ]);
    }

    /**
     * Envoyer un message à l'admin
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $driverId = auth()->id();

        $admin = AdminUser::firstOrFail();

        $message = SupportMessage::create([
            'sender_type'    => Driver::class,
            'sender_id'      => $driverId,
            'recipient_type' => AdminUser::class,
            'recipient_id'   => $admin->id,
            'content'        => $request->content,
            'is_read'        => false,
        ]);

        // ✅ broadcast() (Events Laravel) est silencieusement inopérant sur ce
        // projet — BROADCAST_DRIVER=log en .env. Trigger Pusher direct.
        PusherBroadcaster::trigger('admin-support', 'message.received', [
            'id'             => $message->id,
            'content'        => $message->content,
            'sender_type'    => $message->sender_type,
            'sender_id'      => $message->sender_id,
            'recipient_type' => $message->recipient_type,
            'recipient_id'   => $message->recipient_id,
            'created_at'     => $message->created_at->format('d/m H:i'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Message envoyé',
            'data' => $message
        ]);
    }
}
