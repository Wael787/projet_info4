<?php
// includes/cybank.php

require_once __DIR__ . '/utils.php';

define('CYBANK_VENDEUR', 'MI-1_H');
define('CYBANK_URL', 'https://www.plateforme-smc.fr/cybank/index.php');

function cybank_controle_envoi($api_key, $transaction, $montant, $vendeur, $retour) {
    return md5($api_key.'#'.$transaction.'#'.$montant.'#'.$vendeur.'#'.$retour.'#');
}

function cybank_controle_retour($api_key, $transaction, $montant, $vendeur, $statut) {
    return md5($api_key.'#'.$transaction.'#'.$montant.'#'.$vendeur.'#'.$statut.'#');
}

function cybank_formulaire_paiement($commande_id, $montant, $url_retour) {
    $api_key = 'zzzz';
    $getapikey = __DIR__ . '/../getapikey.php';
    if (file_exists($getapikey)) {
        require_once $getapikey;
        $api_key = getAPIKey(CYBANK_VENDEUR);
    }

    $vendeur         = CYBANK_VENDEUR;
    $montant_formate = number_format($montant, 2, '.', '');
    $controle        = cybank_controle_envoi($api_key, $commande_id,
                                              $montant_formate, $vendeur, $url_retour);

    $h = fn($v) => htmlspecialchars($v);

    $html  = '<form action="'.$h(CYBANK_URL).'" method="POST">';
    $html .= '<input type="hidden" name="transaction" value="'.$h($commande_id).'">';
    $html .= '<input type="hidden" name="montant"     value="'.$h($montant_formate).'">';
    $html .= '<input type="hidden" name="vendeur"     value="'.$h($vendeur).'">';
    $html .= '<input type="hidden" name="retour"      value="'.$h($url_retour).'">';
    $html .= '<input type="hidden" name="control"     value="'.$h($controle).'">';
    $html .= '<button type="submit" class="bouton-validation">💳 Payer '
              .$h($montant_formate).' € par carte</button>';
    $html .= '</form>';
    return $html;
}
