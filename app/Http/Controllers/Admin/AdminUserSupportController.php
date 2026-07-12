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
        // ✅ FIX : compter les 2 sens (avant : uniquement admin → client,
        // sous-comptait de moitié). unreadMessages ne compte que les
        // messages client → admin non lus (ceux qui attendent une réponse).
        $totalMessages = SupportMessage::where(function ($q) {
            $q->where('sender_type', \App\Models\User\User::class)->where('recipient_type', \App\Models\Admin\AdminUser::class);
        })->orWhere(function ($q) {
            $q->where('sender_type', \App\Models\Admin\AdminUser::class)->where('recipient_type', \App\Models\User\User::class);
        })->count();
        $unreadMessages = SupportMessage::where('sender_type', \App\Models\User\User::class)
            ->where('recipient_type', \App\Models\Admin\AdminUser::class)
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

        // ✅ FIX : avant, on ne lisait que les messages admin → client
        // (recipient_type=User), donc les réponses envoyées par le client
        // au support (sender_type=User) n'apparaissaient JAMAIS côté admin.
        // Symétrique à AdminDriverSupportController/AdminCompanySupportController.
        $messages = SupportMessage::where(function ($q) use ($userId) {
                $q->where('recipient_type', \App\Models\User\User::class)
                  ->where('recipient_id', $userId)
                  ->where('sender_type', \App\Models\Admin\AdminUser::class);
            })->orWhere(function ($q) use ($userId) {
                $q->where('sender_type', \App\Models\User\User::class)
                  ->where('sender_id', $userId)
                  ->where('recipient_type', \App\Models\Admin\AdminUser::class);
            })
            ->with('admin')
            ->oldest()
            ->get();

        // Marquer comme lus les messages envoyés par le client au support.
        SupportMessage::where('sender_type', \App\Models\User\User::class)
            ->where('sender_id', $userId)
            ->where('recipient_type', \App\Models\Admin\AdminUser::class)
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
        // ✅ FIX : compter les 2 sens (avant : uniquement admin → client,
        // sous-comptait de moitié). unreadMessages ne compte que les
        // messages client → admin non lus (ceux qui attendent une réponse).
        $totalMessages = SupportMessage::where(function ($q) {
            $q->where('sender_type', \App\Models\User\User::class)->where('recipient_type', \App\Models\Admin\AdminUser::class);
        })->orWhere(function ($q) {
            $q->where('sender_type', \App\Models\Admin\AdminUser::class)->where('recipient_type', \App\Models\User\User::class);
        })->count();
        $unreadMessages = SupportMessage::where('sender_type', \App\Models\User\User::class)
            ->where('recipient_type', \App\Models\Admin\AdminUser::class)
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
