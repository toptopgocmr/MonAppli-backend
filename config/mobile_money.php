<?php

// Métadonnées téléphonie/Mobile Money par pays (indicatif, opérateurs +
// couleur de marque). Miroir de kCountries
// (mobile-client-main/lib/core/constants/app_data.dart) pour garder les
// mêmes indicatifs/opérateurs affichés entre l'app mobile et le panel web
// (retraits société, etc.). À maintenir en synchro si l'un des deux évolue.
//
// Les drapeaux ne sont plus stockés en emoji ici (rendu cassé sous Windows/
// Chrome — affiche "CG" au lieu du drapeau) : ils sont générés à la volée
// via flagcdn.com à partir du code pays (voir WithdrawalController).
//
// Pas de vrai logo d'opérateur hotlinké (pas de CDN fiable/officiel pour les
// opérateurs télécom africains) : chaque opérateur a une couleur de marque
// approximative utilisée pour un badge circulaire avec ses initiales, même
// principe que PaymentPartnerController::partnerColor() côté admin.

return [
    'countries' => [
        'CG' => ['name' => 'Congo (Brazzaville)', 'dial' => '+242', 'operators' => [
            ['name' => 'Airtel Congo', 'color' => '#E4002B'],
            ['name' => 'MTN Congo',    'color' => '#FFCB05'],
        ]],
        'CD' => ['name' => 'Congo (RDC)', 'dial' => '+243', 'operators' => [
            ['name' => 'Airtel',   'color' => '#E4002B'],
            ['name' => 'Vodacom',  'color' => '#E60000'],
            ['name' => 'Orange',   'color' => '#FF7900'],
            ['name' => 'Africell', 'color' => '#8E24AA'],
        ]],
        'CM' => ['name' => 'Cameroun', 'dial' => '+237', 'operators' => [
            ['name' => 'MTN Cameroon',    'color' => '#FFCB05'],
            ['name' => 'Orange Cameroun', 'color' => '#FF7900'],
        ]],
        'GA' => ['name' => 'Gabon', 'dial' => '+241', 'operators' => [
            ['name' => 'Airtel Gabon', 'color' => '#E4002B'],
            ['name' => 'Moov Africa',  'color' => '#0072CE'],
        ]],
        'CI' => ['name' => "Côte d'Ivoire", 'dial' => '+225', 'operators' => [
            ['name' => 'Orange CI',   'color' => '#FF7900'],
            ['name' => 'MTN CI',      'color' => '#FFCB05'],
            ['name' => 'Moov Africa', 'color' => '#0072CE'],
        ]],
        'SN' => ['name' => 'Sénégal', 'dial' => '+221', 'operators' => [
            ['name' => 'Orange Sénégal', 'color' => '#FF7900'],
            ['name' => 'Free Sénégal',   'color' => '#CC0000'],
            ['name' => 'Expresso',       'color' => '#F7941E'],
        ]],
        'ML' => ['name' => 'Mali', 'dial' => '+223', 'operators' => [
            ['name' => 'Orange Mali',       'color' => '#FF7900'],
            ['name' => 'Moov Africa Mali',  'color' => '#0072CE'],
        ]],
        'BF' => ['name' => 'Burkina Faso', 'dial' => '+226', 'operators' => [
            ['name' => 'Orange BF',      'color' => '#FF7900'],
            ['name' => 'Moov Africa BF', 'color' => '#0072CE'],
            ['name' => 'Telecel',        'color' => '#1E3A8A'],
        ]],
        'NE' => ['name' => 'Niger', 'dial' => '+227', 'operators' => [
            ['name' => 'Airtel Niger', 'color' => '#E4002B'],
            ['name' => 'Orange Niger', 'color' => '#FF7900'],
            ['name' => 'Moov Africa',  'color' => '#0072CE'],
        ]],
        'TG' => ['name' => 'Togo', 'dial' => '+228', 'operators' => [
            ['name' => 'Togocel',          'color' => '#00A651'],
            ['name' => 'Moov Africa Togo', 'color' => '#0072CE'],
        ]],
        'MA' => ['name' => 'Maroc', 'dial' => '+212', 'operators' => [
            ['name' => 'Maroc Telecom', 'color' => '#C8102E'],
            ['name' => 'Orange Maroc',  'color' => '#FF7900'],
            ['name' => 'Inwi',          'color' => '#E6007E'],
        ]],
        'DZ' => ['name' => 'Algérie', 'dial' => '+213', 'operators' => [
            ['name' => 'Mobilis', 'color' => '#009444'],
            ['name' => 'Djezzy',  'color' => '#6A1B9A'],
            ['name' => 'Ooredoo', 'color' => '#A91D3A'],
        ]],
        'FR' => ['name' => 'France', 'dial' => '+33', 'operators' => [
            ['name' => 'Orange',      'color' => '#FF7900'],
            ['name' => 'SFR',         'color' => '#E2001A'],
            ['name' => 'Bouygues',    'color' => '#0057A8'],
            ['name' => 'Free Mobile', 'color' => '#CC0000'],
        ]],
        'BE' => ['name' => 'Belgique', 'dial' => '+32', 'operators' => [
            ['name' => 'Proximus',       'color' => '#5C2D91'],
            ['name' => 'Base',           'color' => '#E2001A'],
            ['name' => 'Orange Belgium', 'color' => '#FF7900'],
        ]],
        'CA' => ['name' => 'Canada', 'dial' => '+1', 'operators' => [
            ['name' => 'Bell',      'color' => '#0066A1'],
            ['name' => 'Rogers',    'color' => '#D80418'],
            ['name' => 'Telus',     'color' => '#4B286D'],
            ['name' => 'Vidéotron', 'color' => '#FDB913'],
        ]],
        'CH' => ['name' => 'Suisse', 'dial' => '+41', 'operators' => [
            ['name' => 'Swisscom', 'color' => '#0057B8'],
            ['name' => 'Sunrise',  'color' => '#FFD500'],
            ['name' => 'Salt',     'color' => '#552583'],
        ]],
        'PT' => ['name' => 'Portugal', 'dial' => '+351', 'operators' => [
            ['name' => 'MEO',          'color' => '#00A19A'],
            ['name' => 'NOS',          'color' => '#E2001A'],
            ['name' => 'Vodafone PT',  'color' => '#E60000'],
        ]],
        'US' => ['name' => 'États-Unis', 'dial' => '+1', 'operators' => [
            ['name' => 'AT&T',     'color' => '#00A8E0'],
            ['name' => 'Verizon',  'color' => '#CD040B'],
            ['name' => 'T-Mobile', 'color' => '#E20074'],
        ]],
        'DE' => ['name' => 'Allemagne', 'dial' => '+49', 'operators' => [
            ['name' => 'Deutsche Telekom', 'color' => '#E20074'],
            ['name' => 'Vodafone DE',      'color' => '#E60000'],
            ['name' => 'O2',               'color' => '#0019A5'],
        ]],
        'ES' => ['name' => 'Espagne', 'dial' => '+34', 'operators' => [
            ['name' => 'Movistar',   'color' => '#019DF4'],
            ['name' => 'Orange ES',  'color' => '#FF7900'],
            ['name' => 'Vodafone ES','color' => '#E60000'],
        ]],
    ],

    // Nombre de chiffres attendus après l'indicatif, par indicatif (miroir de kPhoneDigits).
    'phone_digits' => [
        '+242' => 9, '+243' => 9, '+237' => 9, '+241' => 8,
        '+225' => 10, '+221' => 9, '+212' => 9, '+213' => 9,
        '+33' => 9, '+1' => 10,
    ],
];
