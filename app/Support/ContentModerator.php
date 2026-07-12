<?php

namespace App\Support;

/**
 * ContentModerator — modération centralisée pour les canaux de chat qui
 * n'avaient jusqu'ici AUCUN filtre (support Admin ↔ Société / Admin ↔
 * Client / Admin ↔ Chauffeur / Société ↔ Support / Chauffeur ↔ Support).
 *
 * Ces listes reprennent/étendent celles déjà utilisées par
 * DriverMessageController / UserMessageController (chat client ↔ chauffeur),
 * élargies suite à des messages à caractère sexuel/injurieux passés sans
 * filtre dans le chat support (ex. "JE TE BAISE").
 */
class ContentModerator
{
    private const THREATS = [
        'je vais te tuer', 'je te tue', 'mort à', 'tu vas mourir', 'je te retrouve',
        'je te fracasse', 'gare à toi', 'tu vas regretter', 'je vais te buter',
        'crève', 'je te massacre', 'prépare-toi',
    ];

    // Insultes + vocabulaire sexuel explicite.
    private const INSULTS = [
        'fils de pute', 'fdp', 'connard', 'connasse', 'salope', 'pute', 'putain',
        'enculé', 'enculee', 'enculée', 'batard', 'bâtard', 'nique ta mère', 'ntm',
        'va te faire foutre', 'va te faire enculer', 'je te baise', 'je vais te baiser',
        'je te nique', 'nique toi', 'sale pute', 'sale connard', 'trou du cul',
        'suceuse', 'suceur', 'bite', 'couille', 'couilles', 'chatte', 'baiser ta',
        'enfoiré', 'enfoirée', 'abruti', 'abrutie', 'débile mental', 'sombre merde',
        'grosse merde', 'tafiole', 'tapette', 'pd', 'pédé', 'salaud',
    ];

    private const HATEFUL = [
        'sale race', 'sale étranger', 'sale etranger', 'rentre chez toi',
        'rentre dans ton pays', 'retourne dans ton pays', 'sous-race', 'sous race',
        'racaille', 'espèce de sous-race', 'sale noir', 'sale arabe', 'sale blanc',
        'sale juif', 'nique ta race', 'nique ta religion',
    ];

    private const DISRESPECT = [
        'ferme ta gueule', 'ta gueule', 'tais-toi', 'tu es nul', 'tu es nulle',
        'tu ne sers à rien', 'tu ne sers a rien', 'moins que rien', 'bon à rien',
        'bon a rien', 'tu es stupide', 'tu es débile', 'tu es debile', 'dégage',
        'degage', 'sous-merde', 'sous merde', 'tu me fais perdre mon temps',
    ];

    // Contenu sexuel / drague déplacée.
    private const SEXUAL = [
        'je t\'aime', 'je taime', 'je vous aime', 'je veux te faire',
        'donne-moi ton numéro', 'donne moi ton numero', 'ton whatsapp',
        'viens chez moi', 'tu es belle', 'tu es beau', 'tu es sexy', 'tu me plais',
        'tu me plait', 'on peut se voir', 'on se voit ce soir', 'rendez-vous amoureux',
        'tu veux sortir avec moi', 'es-tu libre ce soir', 'je te trouve attirante',
        'je te trouve attirant', 'mon coeur', 'ma chérie', 'mon chéri', 'bisous',
        'je craque pour toi', 'tu me manques', 'petit ami', 'petite amie',
        'sortir ensemble', 'câlin', 'calin', 'envoie une photo de toi', 'photo nue',
        'nue pour moi', 'tu es nue', 'tu es nu', 'veux-tu coucher', 'coucher avec moi',
        'faire l\'amour', 'sexe avec toi', 'envie de toi', 'excité', 'excitée', 'fantasme',
    ];

    /**
     * Modération "légère" pour les chats support (admin ↔ société / client /
     * chauffeur) : on ne bloque que l'offensant, le haineux et le sexuel —
     * contrairement au chat client ↔ chauffeur (voir DriverMessageController /
     * UserMessageController), on n'y bloque PAS les numéros/emails/liens, un
     * agent support pouvant légitimement en partager.
     *
     * Retourne un libellé de raison si le message doit être refusé, sinon null.
     */
    public static function moderateOffensive(string $text): ?string
    {
        $t = mb_strtolower($text);

        foreach (self::THREATS    as $w) { if (str_contains($t, $w)) return 'menace'; }
        foreach (self::INSULTS    as $w) { if (str_contains($t, $w)) return 'propos injurieux ou à caractère sexuel'; }
        foreach (self::HATEFUL    as $w) { if (str_contains($t, $w)) return 'propos haineux'; }
        foreach (self::DISRESPECT as $w) { if (str_contains($t, $w)) return 'manque de respect'; }
        foreach (self::SEXUAL     as $w) { if (str_contains($t, $w)) return 'contenu à caractère sexuel'; }

        return null;
    }
}
