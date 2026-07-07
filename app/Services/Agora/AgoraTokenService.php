<?php

namespace App\Services\Agora;

/**
 * AgoraTokenService — génère les tokens RTC nécessaires pour qu'un client ou
 * un chauffeur rejoigne le canal audio d'un appel (table `calls`).
 *
 * Convention de nommage :
 *   - Canal   : "tt_call_{callId}"        (unique par appel, jamais réutilisé)
 *   - UID user   : l'id utilisateur tel quel (ex: 42)
 *   - UID driver : 1 000 000 000 + id chauffeur (ex: 1000000007)
 *     → évite toute collision d'UID entre un client et un chauffeur qui
 *       auraient le même id numérique dans leurs tables respectives
 *       (deux clés primaires auto-incrémentées indépendantes).
 */
class AgoraTokenService
{
    // Offsets appliqués aux UID pour ne jamais entrer en collision entre
    // deux participants de types différents dans le même canal (chacun a
    // ses propres ID auto-incrémentés indépendants en base).
    const DRIVER_UID_OFFSET  = 1_000_000_000;
    const COMPANY_UID_OFFSET = 2_000_000_000;
    const ADMIN_UID_OFFSET   = 3_000_000_000;

    public static function isConfigured(): bool
    {
        return filled(config('agora.app_id')) && filled(config('agora.app_certificate'));
    }

    public static function channelForCall(int $callId): string
    {
        return "tt_call_{$callId}";
    }

    public static function uidForUser(int $userId): int
    {
        return $userId;
    }

    public static function uidForDriver(int $driverId): int
    {
        return self::DRIVER_UID_OFFSET + $driverId;
    }

    public static function uidForCompany(int $companyId): int
    {
        return self::COMPANY_UID_OFFSET + $companyId;
    }

    /** Support = agent AdminUser qui décroche (n'importe lequel, file d'attente partagée). */
    public static function uidForAdmin(int $adminId): int
    {
        return self::ADMIN_UID_OFFSET + $adminId;
    }

    /**
     * Résout dynamiquement l'UID Agora à partir d'un type polymorphique
     * (classe Eloquent stockée dans caller_type/receiver_type de `calls`).
     */
    public static function uidFor(string $type, int $id): int
    {
        return match ($type) {
            \App\Models\User\User::class     => self::uidForUser($id),
            \App\Models\Driver\Driver::class  => self::uidForDriver($id),
            \App\Models\Company::class        => self::uidForCompany($id),
            \App\Models\Admin\AdminUser::class => self::uidForAdmin($id),
            default => $id,
        };
    }

    /**
     * Résout le channel Pusher (notifications, pas Agora) sur lequel
     * broadcaster un event pour ce type de destinataire.
     *   - user.{id}   / driver.{id}   : channel personnel (déjà utilisé)
     *   - company.{id}                : channel personnel société (nouveau)
     *   - admin-support               : file d'attente partagée (n'importe
     *     quel admin connecté au panel peut décrocher)
     */
    public static function pusherChannelFor(string $type, int $id): string
    {
        return match ($type) {
            \App\Models\User\User::class      => "user.{$id}",
            \App\Models\Driver\Driver::class  => "driver.{$id}",
            \App\Models\Company::class        => "company.{$id}",
            \App\Models\Admin\AdminUser::class => 'admin-support',
            default => "unknown.{$id}",
        };
    }

    /**
     * @return array{app_id:string,channel:string,uid:int,token:string,expires_in:int}|null
     *         null si les identifiants Agora ne sont pas configurés (.env).
     */
    public static function generate(string $channelName, int $uid): ?array
    {
        if (!self::isConfigured()) {
            return null;
        }

        $ttl = (int) config('agora.token_ttl', 3600);

        $token = RtcTokenBuilder2::buildTokenWithUid(
            config('agora.app_id'),
            config('agora.app_certificate'),
            $channelName,
            $uid,
            RtcTokenBuilder2::ROLE_PUBLISHER,
            $ttl,
            $ttl
        );

        return [
            'app_id'     => config('agora.app_id'),
            'channel'    => $channelName,
            'uid'        => $uid,
            'token'      => $token,
            'expires_in' => $ttl,
        ];
    }
}
