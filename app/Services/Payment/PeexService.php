<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Peex (peexit.com) integration – ToptopGo
 *
 * IMPORTANT: This service was previously written against invented/guessed
 * endpoint paths that did not match the real API. It has been rewritten to
 * match the official documentation at https://peex-api-docs.peexit.com/
 * (version 1.1, verified July 2026).
 *
 * Peex actually exposes THREE distinct sub-APIs, each with its own base path
 * and its own partner account/balance:
 *
 *   - Collect API      (/collection/*)   – pull money FROM a customer's mobile
 *                                           wallet into our Peex account.
 *                                           This is "la collecte". Per Peex's
 *                                           own docs it currently facilitates
 *                                           collections in Cameroon.
 *   - Disbursement API  (/disbursement/*) – push money TO a mobile wallet
 *                                           (e.g. driver payouts). Per Peex's
 *                                           docs, Cameroon is currently the
 *                                           only active country for this.
 *   - Remittance API    (/clients/*)      – send money to a Peex user by
 *                                           phone/bank (richer KYC/AML fields,
 *                                           multi-country). Also used here for
 *                                           phone verification.
 *
 * All requests are authenticated with a `SECRETKEY` header (no OAuth, no
 * signature). Sandbox amounts are fixed at 10 FCFA regardless of the amount
 * sent in the payload.
 */
class PeexService implements PaymentProviderInterface
{
    protected string $baseUrl;
    protected ?string $secretKey = null;
    protected bool $sandbox;

    public function __construct()
    {
        $this->sandbox = (bool) config('payments.peex.sandbox', true);
        $this->baseUrl = rtrim((string) ($this->sandbox
            ? config('payments.peex.base_url', 'https://sandbox.peexit.com/api/v1/')
            : config('payments.peex.production_url', 'https://server.peexit.com/api/v1/')), '/') . '/';
        $this->secretKey = config('payments.peex.secret_key') ?: null;
    }

    protected function client()
    {
        return Http::withHeaders([
            'SECRETKEY' => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout((int) config('payments.peex.timeout', 30));
    }

    protected function errorMessage($response, string $fallback): string
    {
        $json = $response->json();
        return $json['error']['message'] ?? $json['message'] ?? $fallback;
    }

    /**
     * Countries Peex currently confirms as supported for the Collect API.
     */
    public function supportedCollectCountries(): array
    {
        return config('payments.peex.collect_countries', ['CM']);
    }

    public function supportsCollectFor(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), array_map('strtoupper', $this->supportedCollectCountries()), true);
    }

    /**
     * Countries Peex currently confirms as supported for the Disbursement API
     * (driver payouts). Kept separate from collect_countries in case Peex
     * enables one before the other.
     */
    public function supportedDisbursementCountries(): array
    {
        return config('payments.peex.disbursement_countries', ['CM']);
    }

    public function supportsDisbursementFor(?string $countryCode): bool
    {
        if (!$countryCode) {
            return false;
        }

        return in_array(strtoupper($countryCode), array_map('strtoupper', $this->supportedDisbursementCountries()), true);
    }

    /**
     * Countries supported for bank payouts (Remittance API, request_bank_payment).
     * Unlike the Disbursement API, Peex's docs don't restrict `to_country` for
     * bank payments — this list is intentionally wider (zone CEMAC).
     */
    public function supportedBankCountries(): array
    {
        return config('payments.peex.bank_countries', ['CM']);
    }

    public function supportsBankFor(?string $countryCode): bool
    {
        if (!$countryCode) {
            return false;
        }

        return in_array(strtoupper($countryCode), array_map('strtoupper', $this->supportedBankCountries()), true);
    }

    // =========================================================================
    // COLLECT API — GET /collection/me, /collection/get_fees,
    //               POST /collection/request_payment, GET /collection/all_requests
    // =========================================================================

    /**
     * Get Collect partner information (balance, fees, activation status).
     * GET /collection/me
     */
    public function getPartnerInfo(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . 'collection/me');

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $this->errorMessage($response, 'Failed to get partner info')];
        } catch (Exception $e) {
            Log::error('Peex getPartnerInfo error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate collection fees before initiating a request.
     * GET /collection/get_fees?amount=&country=&phone_number=
     */
    public function calculateFees(int $amount, string $country = 'CM', ?string $phone = null): array
    {
        try {
            $query = array_filter([
                'amount' => $amount,
                'country' => $country,
                'phone_number' => $phone,
            ]);

            $response = $this->client()->get($this->baseUrl . 'collection/get_fees', $query);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $this->errorMessage($response, 'Failed to calculate fees')];
        } catch (Exception $e) {
            Log::error('Peex calculateFees error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Collect payment from a customer's mobile money wallet (ride payment).
     * POST /collection/request_payment
     *
     * Expected $data keys:
     *   - reference (or track_id) : unique reference, required
     *   - phone                   : E.164 phone number, required
     *   - amount                  : numeric amount, required
     *   - currency                : default XAF
     *   - customer_name           : full name, required by Peex
     *   - country                 : ISO Alpha-2 (e.g. "CM"), required
     *   - description              : reason for the transaction
     */
    public function collect(array $data): array
    {
        try {
            $trackId = $data['track_id'] ?? $data['reference'] ?? null;

            $payload = [
                'track_id' => $trackId,
                'phone' => $this->normalizePhone($data['phone'] ?? '', $data['country'] ?? null),
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'XAF',
                'customer_name' => $data['customer_name'] ?? 'Client TopTopGo',
                'country' => strtoupper($data['country'] ?? 'CM'),
                'description' => $data['description'] ?? 'TopTopGo - Paiement trajet',
            ];

            $response = $this->client()->post($this->baseUrl . 'collection/request_payment', $payload);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $body['id'] ?? null,
                    'reference' => $body['track_id'] ?? $trackId,
                    'status' => $this->mapStatus($body['status'] ?? 'new'),
                    'data' => $body,
                ];
            }

            // ✅ FIX diagnostic : response()->json() renvoie silencieusement
            // null si le corps n'est pas du JSON valide (page d'erreur HTML,
            // corps vide, blocage WAF/proxy...) — on perdait alors toute
            // information exploitable ("raw_response": null dans les logs).
            // On capture donc aussi le statut HTTP et le corps BRUT ici.
            return [
                'success' => false,
                'error' => $this->errorMessage($response, 'Collection request failed'),
                'data' => $response->json(),
                'http_status' => $response->status(),
                'raw_body' => $response->body(),
                'request_sent' => $payload,
                'endpoint' => $this->baseUrl . 'collection/request_payment',
            ];
        } catch (Exception $e) {
            Log::error('Peex collect error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the status of a collection request by our own reference (track_id).
     * GET /collection/all_requests?track_id=...
     *
     * Note: despite the parameter name, $transactionId here is expected to be
     * the track_id we originally sent (Peex doesn't expose lookup by its own
     * internal numeric id for the Collect API).
     */
    public function getTransactionStatus(string $transactionId): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . 'collection/all_requests', [
                'track_id' => $transactionId,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                // Peex returns either a single object or an array with one item.
                $item = is_array($body) && array_is_list($body) ? ($body[0] ?? null) : $body;

                if (!$item) {
                    return ['success' => false, 'error' => 'Transaction not found'];
                }

                return [
                    'success' => true,
                    'status' => $this->mapStatus($item['status'] ?? 'new'),
                    'data' => $item,
                ];
            }

            return ['success' => false, 'error' => $this->errorMessage($response, 'Failed to get transaction status')];
        } catch (Exception $e) {
            Log::error('Peex getTransactionStatus error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all recent collection requests (Peex only returns up to 3 days of history).
     */
    public function getTransactions(array $filters = []): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . 'collection/all_requests', $filters);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $this->errorMessage($response, 'Failed to get transactions')];
        } catch (Exception $e) {
            Log::error('Peex getTransactions error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // REMITTANCE API — phone verification (used pre-collect to validate a number)
    // =========================================================================

    /**
     * Verify a mobile phone number before collecting/paying.
     * POST /clients/verify_phoneNumber
     */
    public function verifyPhone(string $phone, ?string $operator = null, ?string $country = null): array
    {
        try {
            $response = $this->client()->post($this->baseUrl . 'clients/verify_phoneNumber', [
                'mobile_phone' => $this->normalizePhone($phone, $country),
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $this->errorMessage($response, 'Phone verification failed')];
        } catch (Exception $e) {
            Log::error('Peex verifyPhone error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // DISBURSEMENT API — driver payouts
    // =========================================================================

    /**
     * Payout to a driver's mobile wallet.
     * POST /disbursement/request_payment
     *
     * Wired into the admin withdrawal-approval flow
     * (PaymentPartnerController::approveWithdrawal). Only call this for
     * countries returned by supportedDisbursementCountries() — Peex's public
     * docs only mention Cameroon, widened to include Congo Brazzaville per
     * written confirmation from Peex (see PEEX_DISBURSEMENT_COUNTRIES).
     */
    public function payout(array $data): array
    {
        try {
            $trackId = $data['reference'] ?? $data['track_id'] ?? null;

            $payload = [
                'track_id' => $trackId,
                'mobile_phone' => $this->normalizePhone($data['phone'] ?? '', $data['country'] ?? null),
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'XAF',
                'sender_first_name' => $data['sender_first_name'] ?? 'TopTopGo',
                'sender_last_name' => $data['sender_last_name'] ?? 'SARL',
                'sender_mobile_phone' => $data['sender_mobile_phone'] ?? $this->secretSenderPhone(),
                'first_name' => $data['first_name'] ?? 'Chauffeur',
                'last_name' => $data['last_name'] ?? 'TopTopGo',
                'country' => strtoupper($data['country'] ?? 'CM'),
                'purpose' => $data['purpose'] ?? 'BUSINESS',
                'fund_origin' => $data['fund_origin'] ?? 'SALES_AND_BUSINESS_DEVELOPMENT',
            ];

            $response = $this->client()->post($this->baseUrl . 'disbursement/request_payment', $payload);

            if ($response->successful()) {
                $body = $response->json()['request'] ?? $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $body['id'] ?? null,
                    'reference' => $body['track_id'] ?? $trackId,
                    'status' => $this->mapStatus($body['status'] ?? 'new'),
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'error' => $this->errorMessage($response, 'Payout request failed'),
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Peex payout error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bank payout via the Remittance API (larger withdrawals / IBAN transfers).
     * POST /clients/request_bank_payment
     */
    public function bankPayout(array $data): array
    {
        try {
            $trackId = $data['reference'] ?? $data['track_id'] ?? null;

            $payload = [
                'track_id' => $trackId,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_address' => $data['bank_address'],
                'bank_iban' => $data['bank_iban'],
                'bank_swift' => $data['bank_swift'],
                'amount' => $data['amount'],
                'aml_cft' => 1,
                'fxrate' => 1,
                'from_currency' => $data['currency'] ?? 'XAF',
                'to_currency' => $data['currency'] ?? 'XAF',
                'to_country' => strtoupper($data['country'] ?? 'CM'),
                'sender_first_name' => $data['sender_first_name'] ?? 'TopTopGo',
                'sender_last_name' => $data['sender_last_name'] ?? 'SARL',
                'sender_email' => $data['sender_email'] ?? null,
                'sender_mobile_phone' => $data['sender_mobile_phone'] ?? $this->secretSenderPhone(),
                'sender_country' => strtoupper($data['sender_country'] ?? $data['country'] ?? 'CM'),
                'first_name' => $data['first_name'] ?? 'Chauffeur',
                'last_name' => $data['last_name'] ?? 'TopTopGo',
                'purpose' => $data['purpose'] ?? 'BUSINESS',
                'fund_origin' => $data['fund_origin'] ?? 'SALES_AND_BUSINESS_DEVELOPMENT',
            ];

            $response = $this->client()->post($this->baseUrl . 'clients/request_bank_payment', $payload);

            if ($response->successful()) {
                $body = $response->json()['request'] ?? $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $body['id'] ?? null,
                    'reference' => $body['track_id'] ?? $trackId,
                    'status' => $this->mapStatus($body['status'] ?? 'new'),
                    'data' => $body,
                ];
            }

            return ['success' => false, 'error' => $this->errorMessage($response, 'Bank payout request failed')];
        } catch (Exception $e) {
            Log::error('Peex bankPayout error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // WEBHOOK
    // =========================================================================

    /**
     * Handle a single transaction item from a Peex callback.
     *
     * Peex posts an ARRAY of transaction objects to the configured callback
     * URL (see https://peex-api-docs.peexit.com/notifications), secured with
     * HTTP Basic Auth — NOT a per-item call. The caller (WebhookController)
     * is responsible for verifying Basic Auth and iterating the array; this
     * method just normalizes one item.
     */
    public function handleWebhook(array $payload): array
    {
        $trackId = $payload['track_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$trackId || !$status) {
            return ['success' => false, 'error' => 'Invalid webhook payload (missing track_id/status)'];
        }

        return [
            'success' => true,
            'reference' => $trackId,
            'status' => $this->mapStatus($status),
            'raw' => $payload,
        ];
    }

    /**
     * Map Peex's real status values (lowercase: new, pending, paid, failed,
     * canceled, rejected) to TopTopGo's internal statuses.
     */
    protected function mapStatus(string $peexStatus): string
    {
        return match (strtolower($peexStatus)) {
            'paid' => 'success',
            'new', 'pending' => 'pending',
            'canceled', 'cancelled' => 'cancelled',
            'failed', 'rejected' => 'failed',
            default => 'pending',
        };
    }

    public function getProviderName(): string
    {
        return 'peex';
    }

    public function isAvailable(): bool
    {
        if (!$this->secretKey) {
            return false;
        }

        return $this->getPartnerInfo()['success'] ?? false;
    }

    /**
     * Indicatif téléphonique international par pays (zone CEMAC couverte par
     * Peex — voir supportedCollectCountries()/supportedDisbursementCountries()).
     */
    protected function callingCode(string $country): string
    {
        return match (strtoupper($country)) {
            'CM' => '237', // Cameroun
            'CG' => '242', // Congo-Brazzaville
            'GA' => '241', // Gabon
            'TD' => '235', // Tchad
            'CF' => '236', // Centrafrique
            'GQ' => '240', // Guinée équatoriale
            default => '237',
        };
    }

    /**
     * Phone numbers must be E.164 (e.g. +237677777777), no spaces/dashes.
     *
     * ✅ FIX v2 : la v1 retirait le "0" initial du numéro local avant de
     * préfixer l'indicatif, sur l'hypothèse (fausse) que c'est un préfixe de
     * tronc à jeter. Confirmé en production par la réponse EXPLICITE de Peex
     * (HTTP 403) : "Congo phone number must have 9 digits!" /
     * "Cameroon phone number must have 9 digits!" — un numéro local saisi à
     * 9 chiffres (ex: "067621919") devenait 8 chiffres une fois le 0 retiré
     * ("67621919"), et Peex rejetait systématiquement la requête. On ne
     * retire donc plus ce 0 : le numéro local est envoyé tel quel (9
     * chiffres) après l'indicatif pays.
     */
    protected function normalizePhone(string $phone, ?string $country = null): string
    {
        $phone = trim($phone);
        $digits = preg_replace('/[^0-9+]/', '', $phone);

        // Déjà en E.164 (le client a saisi le "+indicatif...") : on garde tel quel.
        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        // Format international "00" (ex: 00242067621919).
        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $callingCode = $this->callingCode($country ?: 'CM');

        // Éviter de dupliquer l'indicatif si l'utilisateur l'a déjà saisi sans "+"
        // (ex: 242067621919).
        if (str_starts_with($digits, $callingCode)) {
            return '+' . $digits;
        }

        // ⚠️ Ne PAS retirer le 0 initial — Peex exige que le numéro local
        // garde ses 9 chiffres (voir note ci-dessus).
        return '+' . $callingCode . $digits;
    }

    /**
     * Peex's Remittance/Disbursement payloads require a sender_mobile_phone.
     * Use a configured platform number as the "sender" identity for payouts.
     */
    protected function secretSenderPhone(): string
    {
        return config('payments.peex.sender_phone', '+237600000000');
    }
}
