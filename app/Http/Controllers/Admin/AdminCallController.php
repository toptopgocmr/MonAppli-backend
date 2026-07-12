<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Company;
use App\Models\Driver\Driver;
use App\Models\User\User;
use App\Services\Calls\CallOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AdminCallController — appels vocaux in-app pour le panel Admin (support).
 * Web, session Blade (admin_id) — pas de guard Eloquent dédié pour les
 * admins, comme le reste du panel (voir AdminSessionMiddleware).
 *
 * Le support est une file d'attente partagée : n'importe quel admin
 * connecté peut décrocher un appel entrant (client, chauffeur ou société),
 * pas seulement celui visé par receiver_id lors de la création du Call.
 */
class AdminCallController extends Controller
{
    public function __construct(protected CallOrchestrator $calls)
    {
    }

    private function adminId(): int
    {
        return (int) session('admin_id');
    }

    private function adminName(): string
    {
        $admin = AdminUser::find($this->adminId());
        if (!$admin) return session('admin_name', 'Support TopTopGo');
        return trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: 'Support TopTopGo';
    }

    /**
     * Résout un nom affichable pour n'importe quel type d'appelant/receveur
     * polymorphique (User, Driver, Company, AdminUser) — utilisé par
     * pending() et index() pour afficher le journal des appels.
     */
    private function displayName(string $type, int $id): string
    {
        $model = $type::find($id);
        if (!$model) {
            return 'Inconnu (#' . $id . ')';
        }
        if ($type === Company::class) {
            return $model->name ?? 'Société #' . $id;
        }
        return trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')) ?: 'Utilisateur #' . $id;
    }

    /**
     * GET /admin/calls/pending — file de secours (polling).
     * Le widget temps réel (Pusher) doit normalement afficher les appels
     * entrants instantanément, mais si le push échoue silencieusement (ex:
     * identifiants Pusher invalides côté serveur — voir PusherBroadcaster),
     * RIEN ne s'affichait jamais côté admin : ni panel, ni journal, ni
     * action possible. Ce endpoint est interrogé toutes les quelques
     * secondes par admin-call-widget.blade.php pour rattraper les appels
     * en attente même si le temps réel est cassé.
     */
    public function pending(): JsonResponse
    {
        $calls = \App\Models\Call::where('receiver_type', AdminUser::class)
            ->where('status', 'initiated')
            ->orderBy('created_at')
            ->get()
            ->filter(fn ($c) => !$c->isStale())
            ->map(fn ($c) => [
                'call_id'     => $c->id,
                'caller_name' => $this->displayName($c->caller_type, (int) $c->caller_id),
                'queue_type'  => CallOrchestrator::queueTypeFor($c->caller_type),
            ])
            ->values();

        return response()->json(['success' => true, 'calls' => $calls]);
    }

    /**
     * GET /admin/calls — journal des appels support (client, chauffeur,
     * société ↔ support), pour que l'admin voie l'historique même si le
     * temps réel n'a jamais sonné.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Call::where('receiver_type', AdminUser::class)
            ->orWhere('caller_type', AdminUser::class);

        if ($request->filled('queue_type')) {
            $typeMap = [
                'client'    => User::class,
                'chauffeur' => Driver::class,
                'societe'   => Company::class,
            ];
            $filterType = $typeMap[$request->queue_type] ?? null;
            if ($filterType) {
                $query = \App\Models\Call::where(function ($q) use ($filterType) {
                    $q->where('caller_type', $filterType)->where('receiver_type', AdminUser::class);
                })->orWhere(function ($q) use ($filterType) {
                    $q->where('receiver_type', $filterType)->where('caller_type', AdminUser::class);
                });
            }
        }

        $calls = (clone $query)->with('recordings')->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Mapping vers les valeurs attendues par TTCall.startCall() / initiate()
        // (target_type: 'user'|'driver'|'company'), distinct de queue_type qui
        // ne sert qu'à l'affichage ("client"/"chauffeur"/"societe").
        $targetTypeMap = [
            User::class    => 'user',
            Driver::class  => 'driver',
            Company::class => 'company',
        ];

        $calls->getCollection()->transform(function ($c) use ($targetTypeMap) {
            $isInbound = $c->receiver_type === AdminUser::class;
            $otherType = $isInbound ? $c->caller_type : $c->receiver_type;
            $otherId   = $isInbound ? $c->caller_id   : $c->receiver_id;

            $c->direction    = $isInbound ? 'Entrant (vers support)' : 'Sortant (depuis support)';
            $c->other_name   = $this->displayName($otherType, (int) $otherId);
            $c->queue_type   = CallOrchestrator::queueTypeFor($isInbound ? $c->caller_type : $c->receiver_type);
            // ✅ Pour le bouton "Rappeler" du journal — null si le type n'est
            // pas rappelable ou si le compte source a été supprimé depuis.
            $c->target_type  = $targetTypeMap[$otherType] ?? null;
            $c->target_id    = $otherId;
            return $c;
        });

        return view('admin.calls.index', compact('calls'));
    }

    /**
     * POST /admin/calls/initiate
     * body: { target_type: 'user'|'driver'|'company', target_id: int }
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'target_type' => 'required|in:user,driver,company',
            'target_id'   => 'required|integer',
        ]);

        $map = [
            'user'    => User::class,
            'driver'  => Driver::class,
            'company' => Company::class,
        ];
        $receiverType = $map[$request->target_type];
        $receiverId   = (int) $request->target_id;

        $exists = $receiverType::find($receiverId);
        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Destinataire introuvable.'], 404);
        }

        [$call, $agora, $alreadyActive] = $this->calls->initiate(
            AdminUser::class, $this->adminId(), $this->adminName(), '',
            $receiverType, $receiverId,
            null
        );

        if ($alreadyActive) {
            return response()->json([
                'success' => false,
                'message' => 'Un appel est déjà en cours.',
                'call_id' => $call->id,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appel initié.',
            'call'    => ['id' => $call->id],
            'agora'   => $agora,
        ]);
    }

    /**
     * POST /admin/calls/{callId}/answer — n'importe quel admin connecté
     * peut décrocher un appel entrant, même si receiver_id visait un autre
     * admin (file d'attente partagée) : on utilise donc son PROPRE UID
     * Agora pour rejoindre le canal, sans exiger d'être le receiver exact.
     */
    public function answer(Request $request, $callId): JsonResponse
    {
        $call = \App\Models\Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        if ($call->status === 'initiated') {
            // ✅ Mise à jour atomique conditionnelle : file d'attente
            // partagée entre plusieurs admins/machines — si deux d'entre eux
            // cliquent "Répondre" en même temps, un seul gagne la course.
            $won = \App\Models\Call::where('id', $callId)->where('status', 'initiated')
                ->update(['status' => 'answered', 'started_at' => now()]);

            if (!$won) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet appel vient d\'être pris par un collègue.',
                ], 409);
            }

            $call->refresh();

            // Prévenir les AUTRES admins connectés que cet appel est pris,
            // pour qu'ils le retirent de leur file d'attente.
            \App\Services\Realtime\PusherBroadcaster::trigger('admin-support', 'call.taken', ['call_id' => $call->id]);
        }

        $agora = \App\Services\Agora\AgoraTokenService::generate(
            \App\Services\Agora\AgoraTokenService::channelForCall($call->id),
            \App\Services\Agora\AgoraTokenService::uidForAdmin($this->adminId())
        );

        return response()->json(['success' => true, 'agora' => $agora]);
    }

    /**
     * GET /admin/calls/{callId}/token
     */
    public function token(Request $request, $callId): JsonResponse
    {
        $call = \App\Models\Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $agora = \App\Services\Agora\AgoraTokenService::generate(
            \App\Services\Agora\AgoraTokenService::channelForCall($call->id),
            \App\Services\Agora\AgoraTokenService::uidForAdmin($this->adminId())
        );

        if (!$agora) {
            return response()->json(['success' => false, 'message' => "Service audio indisponible."], 503);
        }

        return response()->json(['success' => true, 'agora' => $agora]);
    }

    /**
     * POST /admin/calls/{callId}/end
     */
    public function end(Request $request, $callId): JsonResponse
    {
        $call = $this->calls->end((int) $callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true, 'duration' => $call->duration_seconds]);
    }

    /**
     * POST /admin/calls/{callId}/missed
     */
    public function missed(Request $request, $callId): JsonResponse
    {
        $call = $this->calls->missed((int) $callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /admin/calls/{callId}/status — filet de secours pour raccrocher
     * automatiquement des DEUX côtés.
     *
     * Le Pusher event "call.ended" doit normalement le faire instantanément,
     * mais si le push échoue silencieusement (mêmes identifiants Pusher
     * potentiellement invalides que pour la sonnerie, voir pending()), le
     * widget de la partie qui n'a PAS raccroché restait bloqué "en ligne"
     * indéfiniment. Le widget interroge ce endpoint toutes les quelques
     * secondes pendant un appel actif pour rattraper la fin d'appel.
     */
    public function status(Request $request, $callId): JsonResponse
    {
        $call = \App\Models\Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        return response()->json(['success' => true, 'status' => $call->status]);
    }

    /**
     * POST /admin/calls/{callId}/recording — upload de l'enregistrement
     * audio (micro admin + audio distant reçu, mixés côté navigateur via
     * MediaRecorder — voir admin-call-widget.blade.php). Stocké sur le
     * disque `public` : c'est le seul disque de ce projet couvert par le
     * volume persistant Railway (le disque `local`, jamais utilisé
     * ailleurs dans l'app, ne l'est pas et perd son contenu à chaque
     * déploiement — c'est ce qui causait le 404 sur les anciens
     * enregistrements). Le fichier n'est jamais exposé via une URL
     * publique directe : il n'est servi que par cette route, protégée par
     * l'authentification admin.
     */
    public function storeRecording(Request $request, $callId): JsonResponse
    {
        $call = \App\Models\Call::find($callId);
        if (!$call) {
            return response()->json(['success' => false, 'message' => 'Appel introuvable.'], 404);
        }

        $request->validate([
            'recording' => 'required|file|max:51200', // 50 Mo
        ]);

        $filename = 'call_recordings/' . $call->id . '/' . Str::uuid() . '.webm';
        Storage::disk('public')->put($filename, file_get_contents($request->file('recording')->getRealPath()));

        \App\Models\CallRecording::create([
            'call_id'           => $call->id,
            'recorded_by_type'  => AdminUser::class,
            'recorded_by_id'    => $this->adminId(),
            'path'              => $filename,
            'size_bytes'        => $request->file('recording')->getSize(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * GET /admin/calls/{callId}/recordings/{recordingId} — écoute d'un
     * enregistrement (n'importe quel admin connecté peut écouter, comme pour
     * le reste du support).
     */
    public function playRecording(Request $request, $callId, $recordingId)
    {
        $recording = \App\Models\CallRecording::where('call_id', $callId)->findOrFail($recordingId);

        if (!Storage::disk('public')->exists($recording->path)) {
            abort(404, 'Enregistrement introuvable.');
        }

        return Storage::disk('public')->response($recording->path, 'appel_' . $callId . '.webm', [
            'Content-Type' => 'audio/webm',
        ]);
    }

    /**
     * GET /admin/calls/recordings — liste dédiée de TOUS les enregistrements
     * d'appels (support : client, chauffeur, société ↔ admin), avec lecteur
     * audio inline. Séparée du "Journal des appels" (qui liste TOUS les
     * appels, avec ou sans enregistrement) pour que l'équipe support trouve
     * directement les enregistrements sans avoir à chercher dans le journal.
     */
    public function recordings(Request $request)
    {
        $query = \App\Models\CallRecording::with(['call'])
            ->whereHas('call', function ($q) {
                $q->where('receiver_type', AdminUser::class)
                  ->orWhere('caller_type', AdminUser::class);
            });

        if ($request->filled('queue_type')) {
            $typeMap = [
                'client'    => User::class,
                'chauffeur' => Driver::class,
                'societe'   => Company::class,
            ];
            $filterType = $typeMap[$request->queue_type] ?? null;
            if ($filterType) {
                $query->whereHas('call', function ($q) use ($filterType) {
                    $q->where(function ($q2) use ($filterType) {
                        $q2->where('caller_type', $filterType)->where('receiver_type', AdminUser::class);
                    })->orWhere(function ($q2) use ($filterType) {
                        $q2->where('receiver_type', $filterType)->where('caller_type', AdminUser::class);
                    });
                });
            }
        }

        $recordings = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $recordings->getCollection()->transform(function ($rec) {
            $call = $rec->call;
            if ($call) {
                $isInbound       = $call->receiver_type === AdminUser::class;
                $otherType       = $isInbound ? $call->caller_type : $call->receiver_type;
                $otherId         = $isInbound ? $call->caller_id   : $call->receiver_id;
                $rec->other_name = $this->displayName($otherType, (int) $otherId);
                $rec->direction  = $isInbound ? 'Entrant (vers support)' : 'Sortant (depuis support)';
                $rec->queue_type = CallOrchestrator::queueTypeFor($isInbound ? $call->caller_type : $call->receiver_type);
            } else {
                $rec->other_name = 'Appel supprimé';
                $rec->direction  = '—';
                $rec->queue_type = null;
            }
            $rec->recorded_by_name = $this->displayName($rec->recorded_by_type, (int) $rec->recorded_by_id);
            return $rec;
        });

        return view('admin.calls.recordings', compact('recordings'));
    }
}
