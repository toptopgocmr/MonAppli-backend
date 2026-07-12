<?php

namespace App\Services\Agora;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AgoraCloudRecordingService — enregistrement CÔTÉ SERVEUR des appels
 * client↔chauffeur (mobile↔mobile). Contrairement aux appels support
 * (client/chauffeur/société ↔ admin), qui ont TOUJOURS une jambe web
 * (admin-call-widget.blade.php / company-call-widget.blade.php, qui
 * enregistrent déjà via MediaRecorder), les appels client↔chauffeur n'ont
 * aucune jambe web — impossible de les enregistrer côté navigateur.
 *
 * Agora Cloud Recording fait rejoindre un "bot" serveur au canal Agora, qui
 * mixe l'audio des deux participants et l'uploade directement vers le bucket
 * S3 configuré — Laravel n'a jamais le flux audio en transit, seulement les
 * identifiants de session (resourceId/sid) à faire transiter entre le
 * answer() (start) et le end() (stop) d'un même appel.
 *
 * Référence API : https://docs.agora.io/en/cloud-recording/reference/restful-api
 *
 * Nécessite (.env / Railway) :
 *   AGORA_CUSTOMER_KEY / AGORA_CUSTOMER_SECRET   (console Agora > Project
 *     Management > RESTful API — DIFFÉRENT de AGORA_APP_ID/CERTIFICATE)
 *   AGORA_RECORDING_BUCKET / AGORA_RECORDING_ACCESS_KEY /
 *   AGORA_RECORDING_SECRET_KEY / AGORA_RECORDING_S3_REGION
 *     (bucket AWS S3 dédié — vendor=1 dans l'API Agora)
 *
 * ⚠️ Non testable dans cet environnement (nécessite un vrai projet Agora
 * avec Cloud Recording activé + un bucket S3). À valider avec un appel réel
 * dès que les identifiants sont en place — voir logs Laravel (canal
 * 'agora-recording' si configuré, sinon canal par défaut) en cas d'échec
 * silencieux : cette classe ne bloque JAMAIS l'appel lui-même si
 * l'enregistrement échoue (best-effort).
 */
class AgoraCloudRecordingService
{
    // UID dédié au bot d'enregistrement — au-delà de tous les autres offsets
    // (driver/company/admin, voir AgoraTokenService) pour ne jamais entrer
    // en collision avec un participant réel dans le même canal.
    const RECORDING_UID_OFFSET = 9_000_000_000;

    public static function isConfigured(): bool
    {
        $configured = AgoraTokenService::isConfigured()
            && filled(config('agora.cloud_recording.customer_key'))
            && filled(config('agora.cloud_recording.customer_secret'))
            && filled(config('agora.cloud_recording.bucket'))
            && filled(config('agora.cloud_recording.access_key'))
            && filled(config('agora.cloud_recording.secret_key'));

        // vendor 11 (S3-compatible, ex: Backblaze B2) exige un endpoint —
        // les vendors natifs (1=AWS S3, etc.) n'en ont pas besoin.
        if ((int) config('agora.cloud_recording.storage_vendor') === 11) {
            $configured = $configured && filled(config('agora.cloud_recording.endpoint'));
        }

        return $configured;
    }

    /**
     * storageConfig envoyé à l'API start() — inclut extensionParams.endpoint
     * uniquement pour le vendor 11 (S3-compatible : Backblaze B2 et
     * équivalents), ignoré/absent pour les vendors natifs (AWS S3, etc.).
     */
    private static function storageConfig(int $callId): array
    {
        $vendor = (int) config('agora.cloud_recording.storage_vendor', 11);

        $config = [
            'vendor'         => $vendor,
            'region'         => (int) config('agora.cloud_recording.storage_region', 0),
            'bucket'         => config('agora.cloud_recording.bucket'),
            'accessKey'      => config('agora.cloud_recording.access_key'),
            'secretKey'      => config('agora.cloud_recording.secret_key'),
            'fileNamePrefix' => ['call_recordings', (string) $callId],
        ];

        if ($vendor === 11) {
            $config['extensionParams'] = ['endpoint' => config('agora.cloud_recording.endpoint')];
        }

        return $config;
    }

    public static function recordingUidFor(int $callId): int
    {
        return self::RECORDING_UID_OFFSET + $callId;
    }

    private static function baseUrl(): string
    {
        return 'https://api.agora.io/v1/apps/' . config('agora.app_id') . '/cloud_recording';
    }

    private static function http()
    {
        return Http::withBasicAuth(
            config('agora.cloud_recording.customer_key'),
            config('agora.cloud_recording.customer_secret')
        )->timeout(10)->acceptJson()->contentType('application/json');
    }

    /**
     * Démarre l'enregistrement d'un appel — à appeler dès que les DEUX
     * parties ont rejoint le canal (moment "answered"). Renvoie les
     * identifiants à conserver sur la ligne `calls` pour pouvoir arrêter
     * l'enregistrement plus tard (stop()), potentiellement depuis une tout
     * autre requête HTTP.
     *
     * @return array{resourceId:string,sid:string,uid:int}|null null si non
     *         configuré ou en cas d'échec (l'appel continue normalement).
     */
    public static function start(int $callId, string $channelName): ?array
    {
        if (!self::isConfigured()) {
            return null;
        }

        $recordingUid = self::recordingUidFor($callId);

        try {
            $acquire = self::http()->post(self::baseUrl() . '/acquire', [
                'cname'         => $channelName,
                'uid'           => (string) $recordingUid,
                'clientRequest' => [
                    'scene'                => 0,
                    'resourceExpiredHour'  => 24,
                ],
            ]);

            $resourceId = $acquire->json('resourceId');
            if (!$acquire->successful() || !$resourceId) {
                Log::warning('Agora Cloud Recording: acquire échoué', [
                    'call_id' => $callId, 'status' => $acquire->status(), 'body' => $acquire->body(),
                ]);
                return null;
            }

            $token = AgoraTokenService::generate($channelName, $recordingUid);
            if (!$token) {
                return null;
            }

            $start = self::http()->post(
                self::baseUrl() . "/resourceid/{$resourceId}/mode/mix/start",
                [
                    'cname'         => $channelName,
                    'uid'           => (string) $recordingUid,
                    'clientRequest' => [
                        'token'          => $token['token'],
                        'recordingConfig' => [
                            'channelType'  => 0, // 0 = communication (voix point-à-point)
                            'streamTypes'  => 0, // 0 = audio uniquement (pas de vidéo à enregistrer)
                            'maxIdleTime'  => 30,
                        ],
                        // mp4 plutôt que hls : un seul fichier lisible directement via
                        // <audio src="..."> côté admin, pas un manifest + segments .ts
                        // qu'il faudrait exposer et résoudre séparément.
                        'recordingFileConfig' => [
                            'avFileType' => ['mp4'],
                        ],
                        'storageConfig' => self::storageConfig($callId),
                    ],
                ]
            );

            $sid = $start->json('sid');
            if (!$start->successful() || !$sid) {
                Log::warning('Agora Cloud Recording: start échoué', [
                    'call_id' => $callId, 'status' => $start->status(), 'body' => $start->body(),
                ]);
                return null;
            }

            Log::info('🎙️ Agora Cloud Recording démarré', ['call_id' => $callId, 'resourceId' => $resourceId, 'sid' => $sid]);

            return ['resourceId' => $resourceId, 'sid' => $sid, 'uid' => $recordingUid];
        } catch (\Throwable $e) {
            Log::warning('Agora Cloud Recording: start exception: ' . $e->getMessage(), ['call_id' => $callId]);
            return null;
        }
    }

    /**
     * Arrête l'enregistrement et renvoie les chemins des fichiers produits
     * dans le bucket (relatifs — utilisables tels quels avec
     * Storage::disk('agora_recordings')). Best-effort : renvoie un tableau
     * vide en cas d'échec, ne lève jamais d'exception.
     *
     * @return string[]
     */
    public static function stop(int $callId, string $channelName, string $resourceId, string $sid, int $recordingUid): array
    {
        try {
            $resp = self::http()->post(
                self::baseUrl() . "/resourceid/{$resourceId}/sid/{$sid}/mode/mix/stop",
                [
                    'cname'         => $channelName,
                    'uid'           => (string) $recordingUid,
                    'clientRequest' => new \stdClass(),
                ]
            );

            if (!$resp->successful()) {
                Log::warning('Agora Cloud Recording: stop échoué', [
                    'call_id' => $callId, 'status' => $resp->status(), 'body' => $resp->body(),
                ]);
                return [];
            }

            return self::extractFileList($resp->json());
        } catch (\Throwable $e) {
            Log::warning('Agora Cloud Recording: stop exception: ' . $e->getMessage(), ['call_id' => $callId]);
            return [];
        }
    }

    /**
     * La forme exacte de la réponse varie selon le mode/la config Agora
     * (fileList parfois directement sous serverResponse, parfois nichée
     * sous extensionServiceState[].payload.fileList) — on gère les deux
     * formes connues plutôt que de supposer une seule structure.
     */
    private static function extractFileList(?array $json): array
    {
        if (!$json) return [];

        $direct = data_get($json, 'serverResponse.fileList');
        if (is_string($direct) && $direct !== '') {
            return [$direct];
        }
        if (is_array($direct)) {
            return array_values(array_filter(array_map(
                fn ($f) => is_array($f) ? ($f['fileName'] ?? $f['filename'] ?? null) : $f,
                $direct
            )));
        }

        $nested = data_get($json, 'serverResponse.extensionServiceState');
        if (is_array($nested)) {
            $files = [];
            foreach ($nested as $service) {
                foreach (data_get($service, 'payload.fileList', []) as $f) {
                    $name = is_array($f) ? ($f['filename'] ?? $f['fileName'] ?? null) : $f;
                    if ($name) $files[] = $name;
                }
            }
            return $files;
        }

        return [];
    }
}
