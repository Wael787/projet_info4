<?php
// includes/cybank.php
// Fonctions pour gérer l'intégration avec l'API CYBank

require_once __DIR__ . '/utils.php';

// IMPORTANT : Remplace cette valeur par ton identifiant de groupe
// Exemples valides : MI-1_A, MI-2_B, MEF-1_A, MIM_A, SUPMECA_A ...
define('CYBANK_VENDEUR', 'MI-1_H');

// URL de l'interface CYBank
define('CYBANK_URL', 'https://www.plateforme-smc.fr/cybank/index.php');

/**
 *Calcule la valeur de contrôle pourf l'envoi à CYBank.
 *Formule : md5( api_key # transaction # montant # vendeur # retour # )
 */
function cybank_controle_envoi($api_key, $transaction, $montant, $vendeur, $retour) {
    return md5($api_key . '#' . $transaction . '#' . $montant
               . '#' . $vendeur . '#' . $retour . '#');
}

/**
 * Calcule la valeur de contrôle pour le retour de CYBank.
 * Formule : md5( api_key # transaction # montant # vendeur # statut # )
 */
function cybank_controle_retour($api_key, $transaction, $montant, $vendeur, $statut) {
    return md5($api_key . '#' . $transaction . '#' . $montant
               . '#' . $vendeur . '#' . $statut . '#');
}

/**
 * Génère le formulaire HTML à soumettre vers CYBank.
 * Appelé depuis panier.php pour déclencher le paiement.
 *
 * @param string $commande_id  L'id de la commande (stocké en session/BDD)
 * @param float  $montant      Montant total de la commande
 * @param string $url_retour   URL de retour (ex: https://monsite.fr/actions/retour_paiement.php)
 */
function cybank_formulaire_paiement($commande_id, $montant, $url_retour) {
    // Récupérer la clé API via la fonction fournie par getapikey.php
    // (fichier à télécharger sur https://www.plateforme-smc.fr/cybank/getapikey.zip)
    $api_key = 'zzzz'; // valeur par défaut si getapikey.php absent
    if (file_exists(__DIR__ . '/../getapikey.php')) {
        require_once __DIR__ . '/../getapikey.php';
        $api_key = getAPIKey(CYBANK_VENDEUR);
    }

    $vendeur   = CYBANK_VENDEUR;
    // Montant au format "X.XX" (2 décimales, séparateur point)
    $montant_formate = number_format($montant, 2, '.', '');
    $controle  = cybank_controle_envoi($api_key, $commande_id,
                                        $montant_formate, $vendeur, $url_retour);

    $url_cybank = htmlspecialchars(CYBANK_URL);
    $h_transaction = htmlspecialchars($commande_id);
    $h_montant     = htmlspecialchars($montant_formate);
    $h_vendeur     = htmlspecialchars($vendeur);
    $h_retour      = htmlspecialchars($url_retour);
    $h_controle    = htmlspecialchars($controle);

    // Construction du formulaire HTML ligne par ligne
    $html  = '<form action="' . $url_cybank . '" method="POST">';
    $html .= '<input type="hidden" name="transaction" value="' . $h_transaction . '">';
    $html .= '<input type="hidden" name="montant"     value="' . $h_montant . '">';
    $html .= '<input type="hidden" name="vendeur"     value="' . $h_vendeur . '">';
    $html .= '<input type="hidden" name="retour"      value="' . $h_retour . '">';
    $html .= '<input type="hidden" name="control"     value="' . $h_controle . '">';
    $html .= '<button type="submit" class="bouton-validation">💳 Payer ' . $h_montant . ' € par carte</button>';
    $html .= '</form>';
    return $html;
}