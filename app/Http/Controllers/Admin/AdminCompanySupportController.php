<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use App\Models\Company;
use App\Models\Admin\AdminUser;
use App\Services\Realtime\PusherBroadcaster;
use App\Support\ContentModerator;
use Illuminate\Support\Facades\Log;

/**
 * AdminCompanySupportController — chat Admin (support) ↔ Sociétés.
 *
 * Symétrique à AdminUserSupportController / AdminDriverSupportController :
 * avant cette classe, il n'existait AUCUN canal permettant à une société
 * d'écrire au support (ou l'inverse) — seul un appel vocal (CompanyCallController)
 * existait. Les sociétés n'avaient donc aucun moyen d'envoyer un message texte
 * au support TopTopGo, ni de recevoir de réponse écrite.
 */
class AdminCompanySupportController extends Controller
{
    /**
     * Liste TOUTES les sociétés (avec ou sans messages)
     */
    public function index(Request $request)
    {
        $query = Company::withCount(['supportMessages as unread_count' => function ($q) {
                $q->where('is_read', false);
            }])
            ->with(['supportMessages' => function ($q) {
                $q->where('refused', false)->latest()->limit(1);
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $query->orderByRaw('(SELECT COUNT(*) FROM support_messages WHERE recipient_type = ? AND recipient_id = companies.id) DESC', [
            Company::class
        ])->orderBy('name');

        $companies = $query->paginate(20);

        $totalConversations = Company::whereHas('supportMessages')->count();
        // ✅ FIX : compter les 2 sens (avant : uniquement admin → société,
        // sous-comptait de moitié). unreadMessages ne compte que les
        // messages société → admin non lus (ceux qui attendent une réponse).
        $totalMessages = SupportMessage::where(function ($q) {
            $q->where('sender_type', Company::class)->where('recipient_type', AdminUser::class);
        })->orWhere(function ($q) {
            $q->where('sender_type', AdminUser::class)->where('recipient_type', Company::class);
        })->count();
        $unreadMessages = SupportMessage::where('sender_type', Company::class)
            ->where('recipient_type', AdminUser::class)
            ->where('is_read', false)->count();

        return view('admin.messages.admin-company', compact(
            'companies', 'totalConversations', 'totalMessages', 'unreadMessages'
        ));
    }

    /**
     * Affiche la conversation avec une société spécifique
     */
    public function show(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        // ✅ FIX : avant, on ne lisait que les messages admin → société
        // (recipient_type=Company), donc les réponses envoyées par la
        // société au support (sender_type=Company) n'apparaissaient
        // JAMAIS côté admin. Symétrique à AdminDriverSupportController.
        $messages = SupportMessage::where(function ($q) use ($companyId) {
                $q->where('recipient_type', Company::class)
                  ->where('recipient_id', $companyId)
                  ->where('sender_type', AdminUser::class);
            })->orWhere(function ($q) use ($companyId) {
                $q->where('sender_type', Company::class)
                  ->where('sender_id', $companyId)
                  ->where('recipient_type', AdminUser::class);
            })
            ->where('refused', false)
            ->oldest()
            ->get();

        // Marquer comme lus les messages envoyés par la société au support.
        SupportMessage::where('sender_type', Company::class)
            ->where('sender_id', $companyId)
            ->where('recipient_type', AdminUser::class)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $query = Company::withCount(['supportMessages as unread_count' => function ($q) {
                $q->where('is_read', false);
            }])
            ->with(['supportMessages' => function ($q) {
                $q->where('refused', false)->latest()->limit(1);
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $query->orderByRaw('(SELECT COUNT(*) FROM support_messages WHERE recipient_type = ? AND recipient_id = companies.id) DESC', [
            Company::class
        ])->orderBy('name');

        $companies = $query->paginate(20);

        $totalConversations = Company::whereHas('supportMessages')->count();
        // ✅ FIX : compter les 2 sens (avant : uniquement admin → société,
        // sous-comptait de moitié). unreadMessages ne compte que les
        // messages société → admin non lus (ceux qui attendent une réponse).
        $totalMessages = SupportMessage::where(function ($q) {
            $q->where('sender_type', Company::class)->where('recipient_type', AdminUser::class);
        })->orWhere(function ($q) {
            $q->where('sender_type', AdminUser::class)->where('recipient_type', Company::class);
        })->count();
        $unreadMessages = SupportMessage::where('sender_type', Company::class)
            ->where('recipient_type', AdminUser::class)
            ->where('is_read', false)->count();

        retu