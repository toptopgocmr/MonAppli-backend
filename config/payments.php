<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment provider for the application.
    | Supported: "peex", "mtn_momo", "airtel_money", "stripe"
    |
    */

    'default' => env('PAYMENT_DEFAULT_PROVIDER', 'flutterwave'),

    /*
    |--------------------------------------------------------------------------
    | Passerelle de collecte (paiement des réservations)
    |--------------------------------------------------------------------------
    | wacepay  : WacePay (Digitwace) en priorité pour les pays de WACEPAY_PAYIN_COUNTRIES
    | notchpay : Notch Pay en priorité pour les pays de NOTCHPAY_COLLECT_COUNTRIES
    | peex     : ancien comportement (Peex, sinon Flutterwave)
    | Si la passerelle choisie ne couvre pas le pays, on retombe sur Peex puis Flutterwave.
    | Retour arrière immédiat possible : PAYMENT_COLLECT_GATEWAY=peex
    */

    'collect_gateway' => env('PAYMENT_COLLECT_GATEWAY', 'notchpay'),

    /*
    |--------------------------------------------------------------------------
    | Passerelle de décaissement (retraits chauffeurs / sociétés mobile money)
    |--------------------------------------------------------------------------
    | wacepay : WacePay (module Payout de la plateforme PayIn) pour WACEPAY_PAYOUT_COUNTRIES
    | peex    : comportement actuel (Peex Disbursement, sinon paiement manuel)
    */

    'payout_gateway' => env('PAYMENT_PAYOUT_GATEWAY', 'peex'),

    /*
    |--------------------------------------------------------------------------
    | WacePay (Digitwace)
    |--------------------------------------------------------------------------
    | Doc : https://docs.digitwace.com
    | Collecte ET décaissement : plateforme "WacePay PayIn" (mêmes clés public/private
    | + subscription key). Tableau de bord sandbox : https://sandbox-payin.wacepay.io
    | Les IP sortantes du serveur doivent être autorisées dans ce tableau de bord.
    | Webhook collecte (callback_url envoyé à chaque paiement) :
    |   https://backend.toptopgo.app/api/webhooks/wacepay/collect?token=WACEPAY_WEBHOOK_SECRET
    */

    'wacepay' => [
        'timeout'        => (int) env('WACEPAY_TIMEOUT', 30),
        'webhook_secret' => env('WACEPAY_WEBHOOK_SECRET'),

        // Ex. {"MTN":"MTN","AIRTEL":"AIRTEL"} ou par pays {"CG.MTN":"MTNCG"}
        'operator_map'   => json_decode((string) env('WACEPAY_OPERATOR_MAP', '{}'), true) ?: [],

        // Corridors : CEMAC (XAF) + Afrique de l'Ouest UEMOA (XOF).
        // XAF et XOF sont à parité fixe (1 XAF = 1 XOF) : les montants TopTopGo
        // (en XAF) sont envoyés tels quels. Un pays absent de cette liste est
        // refusé (pas de conversion de devise gérée, ex. GH/NG/GN).
        // Ajout possible sans code : WACEPAY_CURRENCIES={"GN":"GNF"}
        'currencies' => array_merge([
            // CEMAC
            'CM' => 'XAF', 'CG' => 'XAF', 'GA' => 'XAF', 'TD' => 'XAF', 'CF' => 'XAF', 'GQ' => 'XAF',
            // UEMOA
            'CI' => 'XOF', 'SN' => 'XOF', 'BJ' => 'XOF', 'TG' => 'XOF', 'BF' => 'XOF',
            'ML' => 'XOF', 'NE' => 'XOF', 'GW' => 'XOF',
        ], json_decode((string) env('WACEPAY_CURRENCIES', '{}'), true) ?: []),

        'payin' => [
            'base_url'         => env('WACEPAY_PAYIN_BASE_URL', 'https://payinws.wacepay.com/api/v1'),
            'public_key'       => env('WACEPAY_PAYIN_PUBLIC_KEY'),
            'private_key'      => env('WACEPAY_PAYIN_PRIVATE_KEY'),
            'subscription_key' => env('WACEPAY_PAYIN_SUBSCRIPTION_KEY'),
            'callback_url'     => env('WACEPAY_CALLBACK_URL'), // défaut : APP_URL/api/webhooks/wacepay/collect
            'countries'        => array_filter(explode(',', env('WACEPAY_PAYIN_COUNTRIES', 'CG,CM,GA,TD,CF,GQ,CI,SN,BJ,TG,BF,ML,NE,GW'))),
            'msisdn_format'    => env('WACEPAY_MSISDN_FORMAT', 'international'), // international (242…) | local
        ],

        // Décaissement : module Payout de la MÊME plateforme PayIn (mêmes clés).
        // Pas l'API WacePay Business (remittance).
        'payout' => [
            'enabled'       => (bool) env('WACEPAY_PAYOUT_ENABLED', true),
            'countries'     => array_filter(explode(',', env('WACEPAY_PAYOUT_COUNTRIES', 'CG,CM,GA,TD,CF,GQ,CI,SN,BJ,TG,BF,ML,NE,GW'))),
            'msisdn_format' => env('WACEPAY_PAYOUT_MSISDN_FORMAT', 'international'),
            // payoutSubscriptionId par pays.opérateur, ex. {"CG.MTN":"<id>","CG.AIRTEL":"<id>"} ;
            // vide → recherche automatique dans GET payout/customer/services
            'subscriptions' => json_decode((string) env('WACEPAY_PAYOUT_SUBSCRIPTIONS', '{}'), true) ?: [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notch Pay
    |--------------------------------------------------------------------------
    | Doc : https://developer.notchpay.co
    | Même URL pour sandbox et production : la clé (pk_test_… / pk_live_…)
    | détermine l'environnement.
    | Webhook à déclarer dans la console Notch Pay :
    |   https://backend.toptopgo.app/api/webhooks/notchpay
    */

    'notchpay' => [
        'base_url'     => env('NOTCHPAY_BASE_URL', 'https://api.notchpay.co'),
        'public_key'   => env('NOTCHPAY_PUBLIC_KEY'),
        'private_key'  => env('NOTCHPAY_PRIVATE_KEY'),   // X-Grant (transferts uniquement)
        'webhook_hash' => env('NOTCHPAY_WEBHOOK_HASH'),  // "Hash Key" du webhook (hsk_test_…)
        'callback_url' => env('NOTCHPAY_CALLBACK_URL'),  // optionnel (flux avec redirection)
        'currency'     => 'XAF',
        'timeout'      => 30,
        'collect_countries' => array_filter(explode(',', env('NOTCHPAY_COLLECT_COUNTRIES', 'CG,CM,GA,CI,SN'))),
        // Ex. si Notch Pay nomme un canal différemment : 'cg.airtel' => 'cg.airtel_money'
        'channel_overrides' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Peex Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Documentation: https://peex-api-docs.peexit.com/
    | Primary payment gateway for Congo Brazzaville
    |
    */

    'peex' => [
        'base_url' => env('PEEX_BASE_URL', 'https://sandbox.peexit.com/api/v1/'),
        'production_url' => env('PEEX_PRODUCTION_URL', 'https://server.peexit.com/api/v1/'),
        'secret_key' => env('PEEX_SECRET_KEY'),
        'webhook_secret' => env('PEEX_WEBHOOK_SECRET'),
        'sandbox' => env('PEEX_SANDBOX', true),
        'currency' => 'XAF',
        'timeout' => 30,

        // Basic Auth credentials Peex must send back on our /webhooks/peex/* endpoints.
        'callback_username' => env('PEEX_CALLBACK_USERNAME'),
        'callback_password' => env('PEEX_CALLBACK_PASSWORD'),

        // Per https://peex-api-docs.peexit.com/collect the public docs only
        // mention Cameroon; widened to include Congo Brazzaville per written
        // confirmation from Peex support. Any other country falls back to
        // Flutterwave (collect) / manual payout (disbursement).
        'collect_countries' => array_filter(explode(',', env('PEEX_COLLECT_COUNTRIES', 'CM,CG'))),

        // ⚠️ https://peex-api-docs.peexit.com/disbursement dit explicitement :
        // "Currently, the only active country for disbursement is Cameroon
        // (CM)." CG a été ajouté par confirmation écrite de Peex (comme pour
        // le collect). Ne PAS élargir cette liste sans confirmation écrite
        // équivalente pour un autre pays — Peex rejettera sinon la requête.
        'disbursement_countries' => array_filter(explode(',', env('PEEX_DISBURSEMENT_COUNTRIES', 'CM,CG'))),

        // ✅ Retraits par virement bancaire (Remittance API,
        // POST /clients/request_bank_payment) : la doc ne mentionne AUCUNE
        // restriction de pays pour ce endpoint (contrairement au Disbursement
        // ci-dessus) — `to_country` accepte n'importe quel ISO Alpha-2. On
        // autorise donc toute la zone CEMAC ici, à la demande du client.
        'bank_countries' => array_filter(explode(',', env('PEEX_BANK_COUNTRIES', 'CM,CG,GA,TD,CF,GQ'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | MTN Mobile Money Configuration
    |--------------------------------------------------------------------------
    |
    | MTN MoMo API integration for mobile money payments
    |
    */

    'mtn_momo' => [
        'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
        'api_user' => env('MTN_MOMO_API_USER'),
        'api_key' => env('MTN_MOMO_API_KEY'),
        'environment' => env('MTN_MOMO_ENVIRONMENT', 'sandbox'),
        'currency' => 'XAF',
        'callback_url' => env('MTN_MOMO_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Airtel Money Configuration
    |--------------------------------------------------------------------------
    |
    | Airtel Money API integration for mobile money payments
    |
    */

    'airtel_money' => [
        'base_url' => env('AIRTEL_MONEY_BASE_URL', 'https://openapiuat.airtel.africa'),
        'client_id' => env('AIRTEL_MONEY_CLIENT_ID'),
        'client_secret' => env('AIRTEL_MONEY_CLIENT_SECRET'),
        'environment' => env('AIRTEL_MONEY_ENVIRONMENT', 'sandbox'),
        'currency' => 'XAF',
        'country' => 'CG',
        'callback_url' => env('AIRTEL_MONEY_CALLBACK_URL'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Flutterwave Configuration
    |--------------------------------------------------------------------------
    | Passerelle principale pour Mobile Money (MTN & Airtel, Congo Brazzaville).
    | Docs : https://developer.flutterwave.com/docs/getting-started
    */

    'flutterwave' => [
        'env'         => env('FLW_ENV', 'sandbox'),
        'secret_key'  => env('FLW_SECRET_KEY'),
        'public_key'  => env('FLW_PUBLIC_KEY'),
        'secret_hash' => env('FLW_SECRET_HASH'),
        'currency'    => 'XAF',
        'country'     => 'CG',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Stripe payment gateway for international card payments
    |
    */

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => 'XAF',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Fees Configuration
    |--------------------------------------------------------------------------
    |
    | Configure commission rates and fees for the platform
    |
    */

    'fees' => [
        'platform_commission' => env('PLATFORM_COMMISSION_PERCENT', 15), // 15%
        'minimum_commission' => env('MINIMUM_COMMISSION', 100), // 100 XAF
        'escrow_hold_hours' => env('ESCROW_HOLD_HOURS', 24), // Hold funds for 24h after ride
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Providers by Country
    |--------------------------------------------------------------------------
    |
    | Define available payment providers for each country
    |
    */

    'providers_by_country' => [
        'CG' => ['wacepay', 'notchpay', 'flutterwave', 'peex', 'mtn_momo', 'airtel_money'], // Congo Brazzaville
        'CD' => ['peex', 'airtel_money'], // Congo Kinshasa
        'GA' => ['notchpay', 'peex', 'airtel_money'], // Gabon
        'CM' => ['wacepay', 'notchpay', 'peex', 'mtn_momo'], // Cameroon
        'INTERNATIONAL' => ['stripe'], // International
    ],

];
