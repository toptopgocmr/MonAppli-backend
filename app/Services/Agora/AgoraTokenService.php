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
            \App\Models\Driver\