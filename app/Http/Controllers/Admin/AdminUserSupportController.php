<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use App\Models\User\User;
use App\Services\Realtime\PusherBroadcaster;

class AdminUserSupportController extends Controller
{
    /**
     * Liste TOUS les utilisateurs (avec ou sans messages)
     */
    public function index(Request $request)
    {
        $query = User::withCount(['supportMessages as unread_count' => function ($q) {
                $q->where('is_read', false);
            }])
            ->with(['supportMessages' => function ($q) {
                $q->latest()->limit(1);
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name',  'like', "%$search%")
                  ->orWhere('phone',      'like', "%$search%")
                  ->orWhere('email',      'like', "%$search%");
            });
        }

        // Trier : ceux avec messages en premier, puis les autres
        $query->orderByRaw('(SELECT COUNT(*) FROM support_messages WHERE recipient_type = ? AND recipient_id = users.id) DESC', [
            \App\Models\User\User::class
        ])->orderBy('first_name');

        $users = $query->paginate(20);

        $totalConversations = User::whereHas('supportMessages')->count();
        $totalMessages      = SupportMessage::where('recipient_type', \App\Models\User\User::class)->count();
        $unreadMessages     = SupportMessage::where('recipient_type', \App\Models\User\User::class)
                                ->where('is_read', false)->count();

        return view('admin.messages.admin-user', compact(
            'users', 'totalConversations', 'totalMessages', 'unreadMessages'
        ));
    }

    /**
     * Affiche la conversation avec un utilisateur spécifique
     */
    public function show(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $messages = SupportMessage::where('recipient_type', \App\Models\User\User::class)
            ->where('recipient_id', $userId)
            ->with('admin')
            ->oldest()
            ->get();

        // Marquer comme lus
        SupportMessage::where('recipient_type', \App\Models\User\User::class)
            ->where('recipient_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // Sidebar : tous les users
        $query = User::withCount(['supportMessages as unread_count' => function ($q) {
                $q->where('is_read', false);
            }])
            ->with(['supportMessages' => function ($q) {
                $q->latest()->limit(1);
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name',  'like', "%$search%")
                  ->orWhere('phone',      'like', "%$search%")
                  ->orWhere('email',      'like', "%$search%");
            });
        }

        $query->orderByRaw('(SELECT COUNT(*) FROM support_messages WHERE recipient_type = ? AND recipient_id = users.id) DESC', [
            \App\Models\User\User::class
        ])->orderBy('first_name');

        $users = $query->paginate(20);

        $totalConversations = User::whereHas('supportMessages')->count();
        $totalMessages      = SupportMessage::where('recipient_type', \App\Models\User\User::class)->count();
        $unreadMessages     = SupportMessage::where('recipient_type', \App\Models\User\User::class)
                                ->where('is_read', false)->count();

        return view('admin.messages.admin-user', compact(
            'user', 'users', 'messages',
            'totalConversations', 'totalMessages', 'unreadMessages'
        ));
    }

    /**
     * Envoyer un message à un utilisateur
     */
    public function send(Request $request, $userId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($userId);

        $message = SupportMessage::create([
            'sender_type'    => \App\Models\Admin\AdminUser::class,
            'sender_id'      => session('admin_id'),
            'recipient_type' => \App\Models\User\User::class,
            'recipient_id'   => $userId,
            'content'        => $request->content,
            'is_read'        => false,
        ]);

        // ✅ NOUVEAU — cette réponse n'était jamais notifiée en temps réel
        // au client avant (aucun broadcast n'existait ici du tout).
        PusherBroadcaster::trigger('admin-support', 'message.received', [
            'id'             => $message->id,
            'content'        => $message->content,
            'sender_type'    => $message->sender_type,
            'sender_id'      => $message->sender_id,
            'recipient_type' => $message->recipient_type,
            'recipient_id'   => $message->recipient_id,
            'created_at'     => $message->created_at->format('d/m H:i'),
        ]);

        return redirect()->route('admin.support.users.show', array_filter([
            'user'   => $userId,
            'search' => $request->search,
        ]))->with('success', 'Message envoyé à ' . $user->first_name . ' !');
    }
}
