<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\Admin\AdminUser;
use App\Services\Realtime\PusherBroadcaster;
use App\Support\ContentModerator;
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
            ->where('refused', false)
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

        // ✅ Modération — bloque les messages offensants/haineux/sexuels avant
        // qu'ils n'atteignent le support (voir ContentModerator).
        $reason = ContentModerator::moderateOffensive($content);
        if ($reason) {
            Log::warning('🚫 Message client→support bloqué', [
                'user_id' => $user->id, 'reason' => $reason,
                'content' => substr($content, 0, 80),
            ]);
            SupportMessage::create([
                'sender_type'    => get_class($user),
                'sender_id'      => $user->id,
                'recipient_type' => AdminUser::class,
                'recipient_id'   => $admin?->id ?? 1,
                'content'        => $content,
                'is_read'        => false,
                'refused'        => true,
                'refused_reason' => $reason,
            ]);
            return response()->json([
                'success' => false,
                'blocked' => true,
                'reason'  => $reason,
                'message' => 'Message refusé par la modération.',
            ], 422);
        }

        $msg = SupportMessage::create([
            'sender_type'    => get_class($user),
            'sender_id'      => $user->id,
            'recipient_type' => AdminUser::class,
            'recipient_id'   => $admin?->id ?? 1,
            'cont