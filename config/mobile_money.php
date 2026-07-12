<?php

// Métadonnées téléphonie/Mobile Money par pays (drapeau, indicatif, opérateurs).
// Miroir de kCountries (mobile-client-main/lib/core/constants/app_data.dart)
// pour garder les mêmes indicatifs/opérateurs affichés entre l'app mobile et
// le panel web (retraits société, etc.). À maintenir en synchro si l'un des
// deux évolue.

return [
    'countries' => [
        'CG' => ['name' => 'Congo (Brazzaville)', 'dial' => '+242', 'flag' => '🇨🇬', 'operators' => ['Airtel Congo', 'MTN Congo']],
        'CD' => ['name' => 'Congo (RDC)',          'dial' => '+243', 'flag' => '🇨🇩', 'operators' => ['Airtel', 'Vodacom', 'Orange', 'Africell']],
        'CM' => ['name' => 'Cameroun',              'dial' => '+237', 'flag' => '🇨🇲', 'operators' => ['MTN Cameroon', 'Orange Cameroun']],
        'GA' => ['name' => 'Gabon',                 'dial' => '+241', 'flag' => '🇬🇦', 'operators' => ['Airtel Gabon', 'Moov Africa']],
        'CI' => ['name' => "Côte d'Ivoire",         'dial' => '+225', 'flag' => '🇨🇮', 'operators' => ['Orange CI', 'MTN CI', 'Moov Africa']],
        'SN' => ['name' => 'Sénégal',               'dial' => '+221', 'flag' => '🇸🇳', 'operators' => ['Orange Sénégal', 'Free Sénégal', 'Expresso']],
        'ML' => ['name' => 'Mali',                  'dial' => '+223', 'flag' => '🇲🇱', 'operators' => ['Orange Mali', 'Moov Africa Mali']],
        'BF' => ['name' => 'Burkina Faso',          'dial' => '+226', 'flag' => '🇧🇫', 'operators' => ['Orange BF', 'Moov Africa BF', 'Telecel']],
        'NE' => ['name' => 'Niger',                 'dial' => '+227', 'flag' => '🇳🇪', 'operators' => ['Airtel Niger', 'Orange Niger', 'Moov Africa']],
        'TG' => ['name' => 'Togo',                  'dial' => '+228', 'flag' => '🇹🇬', 'operators' => ['Togocel', 'Moov Africa Togo']],
        'MA' => ['name' => 'Maroc',                 'dial' => '+212', 'flag' => '🇲🇦', 'operators' => ['Maroc Telecom', 'Orange Maroc', 'Inwi']],
        'DZ' => ['name' => 'Algérie',                'dial' => '+213', 'flag' => '🇩🇿', 'operators' => ['Mobilis', 'Djezzy', 'Ooredoo']],
        'FR' => ['name' => 'France',                 'dial' => '+33',  'flag' => '🇫🇷', 'operators' => ['Orange', 'SFR', 'Bouygues', 'Free Mobile']],
        'BE' => ['name' => 'Belgique',                'dial' => '+32',  'flag' => '🇧🇪', 'operators' => ['Proximus', 'Base', 'Orange Belgium']],
        'CA' => ['name' => 'Canada',                  'dial' => '+1',   'flag' => '🇨🇦', 'operators' => ['Bell', 'Rogers', 'Telus', 'Vidéotron']],
        'CH' => ['name' => 'Suisse',                  'dial' => '+41',  'flag' => '🇨🇭', 'operators' => ['Swisscom', 'Sunrise', 'Salt']],
        'PT' => ['name' => 'Portugal',                'dial' => '+351', 'flag' => '🇵🇹', 'operators' => ['MEO', 'NOS', 'Vodafone PT']],
        'US' => ['name' => 'États-Unis',              'dial' => '+1',   'flag' => '🇺🇸', 'operators' => ['AT&T', 'Verizon', 'T-Mobile']],
        'DE' => ['name' => 'Allemagne',               'dial' => '+49',  'flag' => '🇩🇪', 'operators' => ['Deutsche Telekom', 'Vodafone DE', 'O2']],
        'ES' => ['name' => 'Espagne',                 'dial' => '+34',  'flag' => '🇪🇸', 'operators' => ['Movistar', 'Orange ES', 'Vodafone ES']],
    ],

    // Nombre de chiffres attendus après l'indicatif, par indicatif (miroir de kPhoneDigits).
    'phone_digits' => [
        '+242' => 9, '+243' => 9, '+237' => 9, '+241' => 8,
        '+225' => 10, '+221' => 9, '+212' => 9, '+213' => 9,
        '+33' => 9, '+1' => 10,
    ],
];
