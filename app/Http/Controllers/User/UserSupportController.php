<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Events\SupportMessageSent;
use App\Models\SupportMessage;
use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserSupportController extends Controller
{
    /**
     * Liste les messages entre le client et l'admin
     */
    public function index()
    {
        $user = Auth::user();

        $messages = SupportMessage::where(function ($q) use ($user) {
                $q->where('sender_type', get_class($user))
                  ->where('sender_id', $user->id);
            })
            ->orWhere(function ($q) use ($user) {
                $q->where('recipient_type', get_class($user))
                  ->where('recipient_id', $user->id);
            })
            ->oldest()
            ->get()
            ->map(function ($m) use ($user) {
                $isFromUser = $m->sender_type === get_class($user)
                           && $m->sender_id  === $user->id;
                return [
                    'id'          => $m->id,
                    'body'        => $m->content,
                    'sender_type' => $isFromUser ? 'user' : 'support',
                    'is_mine'     => $isFromUser,
                    'created_at'  => $m->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Envoyer un message au support (accepte 'content' ou 'message')
     */
    public function store(Request $request)
    {
        $content = $request->input('content') ?? $request->input('message');

        if (empty($content)) {
            return response()->json([
                'success' => false,
                'message' => 'Le champ message est requis.',
            ], 422);
        }

        $user  = Auth::user();
        $admin = AdminUser::first();

        $msg = SupportMessage::create([
            'sender_type'    => get_class($user),
            'sender_id'      => $user->id,
            'recipient_type' => AdminUser::class,
            'recipient_id'   => $admin?->id ?? 1,
            'content'        => $content,
            'is_read'        => false,
        ]);

        try {
            broadcast(new SupportMessageSent($msg));
        } catch (\Exception $e) {
            Log::error('SupportMessageSent broadcast: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Message envoyé au support.',
            'data'    => [
                'id'          => $msg->id,
                'body'        => $msg->content,
                'sender_type' => 'user',
                'is_mine'     => true,
                'created_at'  => $msg->created_at?->toIso8601String(),
            ],
        ]);
    }
}