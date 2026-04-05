<?php
// actions/retour_paiement.php
// Reçoit le retour GET de CYBank après paiement.
// CYBank ajoute à l'URL : transaction, montant, vendeur, statut, control
require_once '../includes/session.php';
require_once '../includes/utils.php';
require_once '../includes/cybank.php';

// Récupérer les paramètres GET envoyés par CYBank
$transaction = $_GET['transaction'] ?? '';
$montant     = $_GET['montant']     ?? '';
$vendeur     = $_GET['vendeur']     ?? '';
$statut      = $_GET['statut']      ?? $_GET['status'] ?? '';
$control_recu = $_GET['control']   ?? '';

// Si un paramètre essentiel manque → erreur
if ($transaction === '' || $montant === '' || $vendeur === '' || $statut === '' || $control_recu === '') {
    header('Location: ../panier.php?erreur=retour_incomplet');
    exit;
}

// Récupérer la clé API
$api_key = 'zzzz';
if (file_exists(__DIR__ . '/../getapikey.php')) {
    require_once __DIR__ . '/../getapikey.php';
    $api_key = getAPIKey($vendeur);
}

// Vérifier la valeur de contrôle (intégrité du retour)
$control_attendu = cybank_controle_retour($api_key, $transaction, $montant, $vendeur, $statut);

// Vérifier que les données n'ont pas été modifiées
if ($control_attendu !== $control_recu) {
    // Hash invalide : données altérées
    header('Location: ../panier.php?erreur=controle_invalide');
    exit;
}

// Chercher la commande correspondante
$commande = trouver_commande($transaction);
if (!$commande) {
    // Commande introuvable (transaction inconnue)
    header('Location: ../index.php?erreur=commande_introuvable');
    exit;
}

if ($statut === 'accepted') {
    // ✅ Paiement accepté
    maj_commande($transaction, 'paiement_statut', 'paye');
    maj_commande($transaction, 'statut', 'en_preparation');
    maj_commande($transaction, 'date_paiement', date('Y-m-d H:i:s'));

    // Ajouter des points de fidélité au client (1 pt par euro)
    $client_id = $commande['client_id'] ?? '';
    if ($client_id !== '') {
        $tous_users = lire_json('users.json');
        foreach ($tous_users as &$u) {
            if ($u['id'] === $client_id) {
                $points_gagnes = (int)floor((float)$montant);
                $u['points_fidelite'] = ((int)($u['points_fidelite'] ?? 0)) + $points_gagnes;
                break;
            }
        }
        ecrire_json('users.json', $tous_users);

        // Mettre à jour la session si c'est le client connecté
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] === $client_id) {
            foreach ($tous_users as $u) {
                if ($u['id'] === $client_id) {
                    $u_session = $u;
                    unset($u_session['password']);
                    $_SESSION['user'] = $u_session;
                    break;
                }
            }
        }
    }

    // Vider le panier et la commande en cours
    unset($_SESSION['panier']);
    unset($_SESSION['commande_en_cours']);

    header('Location: ../profil.php?msg=paiement_ok&commande_id=' . urlencode($transaction));

} else {
    // ❌ Paiement refusé
    maj_commande($transaction, 'paiement_statut', 'refuse');
    maj_commande($transaction, 'statut', 'abandonne');

    // On garde le panier en session pour que le client puisse réessayer
    unset($_SESSION['commande_en_cours']);

    header('Location: ../panier.php?erreur=paiement_refuse');
}
exit;