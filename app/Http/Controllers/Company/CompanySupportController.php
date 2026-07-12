<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Company;
use App\Models\SupportMessage;
use App\Services\Realtime\PusherBroadcaster;
use Illuminate\Http\Request;

/**
 * CompanySupportController — chat Société ↔ Support TopTopGo (panel web).
 *
 * Avant cette classe, une société ne pouvait qu'APPELER le support
 * (CompanyCallController) ou voir en LECTURE SEULE les conversations de ses
 * clients (CompanyMessageController::support()) — aucun canal texte propre
 * à la société elle-même n'existait. Symétrique à UserSupportController
 * (client ↔ support) et DriverSupportController (chauffeur ↔ support).
 */
class CompanySupportController extends Controller
{
    private function company(): Company
    {
        // ✅ Résout la société pour le compte principal ET pour un agent
        // connecté (auth('company')->user() renvoie null pour un agent).
        return \App\Support\CompanyContext::company();
    }

    /**
     * Affiche le fil de discussion Société ↔ Support (un seul thread).
     */
    public function index(Request $request)
    {
        $company = $this->company();

        $messages = SupportMessage::where(function ($q) use ($company) {
                $q->where('sender_type', Company::class)->where('sender_id', $company->id);
            })
            ->orWhere(function ($q) use ($company) {
                $q->where('recipient_type', Company::class)->where('recipient_id', $company->id);
            })
            ->oldest()
            ->get();

        // Marquer comme lus les messages reçus du support
        SupportMessage::where('recipient_type', Company::class)
            ->where('recipient_id', $company->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('company.messages.support-admin', compact('company', 'messages'));
    }

    /**
     * Envoyer un message au support TopTopGo.
     */
    public function send(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $company = $this->company();
        $admin   = AdminUser::first();

        $message = SupportMessage::create([
            'sender_type'    => Company::class,
            'sender_id'      => $company->id,
            'recipient_type' => AdminUser::class,
            'recipient_id'   => $admin?->id ?? 1,
            'content'        => $request->content,
            'is_read'        => false,
        ]);

        // ✅ broadcast() (Events Laravel) est silencieusement inopérant sur ce
        // projet — BROADCAST_DRIVER=log en .env. On déclenche directement via
        // Pusher, même pattern que UserSupportController/CallOrchestrator.
        PusherBroadcaster::trigger('admin-support', 'message.received', [
            'id'             => $message->id,
            'content'        => $message->content,
            'sender_type'    => $message->sender_type,
            'sender_id'      => $message->sender_id,
            'recipient_type' => $message->recipient_type,
            'recipient_id'   => $message->recipient_id,
            'created_at'     => $message->created_at->format('d/m H:i'),
        ]);

        return redirect()->route('company.support.index')
                          ->with('success', 'Message envoyé au support.');
    }
}
