<?php
// actions/retour_paiement.php
// Reçoit le retour GET de CYBank après paiement.
// Gère deux cas :
//   1. Paiement initial d'une commande (ID normal ex: "20D496D270")
//   2. Paiement différentiel d'une modification (ID se termine par "D" ex: "20D496D270D")

require_once '../includes/session.php';
require_once '../includes/utils.php';
require_once '../includes/cybank.php';

$transaction  = $_GET['transaction'] ?? '';
$montant      = $_GET['montant']     ?? '';
$vendeur      = $_GET['vendeur']     ?? '';
$statut       = $_GET['statut']      ?? $_GET['status'] ?? '';
$control_recu = $_GET['control']     ?? '';

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

// Vérifier la valeur de contrôle
$control_attendu = cybank_controle_retour($api_key, $transaction, $montant, $vendeur, $statut);
if (!hash_equals($control_attendu, $control_recu)) {
    header('Location: ../panier.php?erreur=controle_invalide');
    exit;
}

// --- Détecter si c'est un paiement différentiel ---
// Les paiements différentiels ont un ID qui se termine par "D"
// et la vraie commande a l'ID sans le "D" à la fin
$est_paiement_diff = false;
$commande_id_reel  = '';

if (strlen($transaction) > 1 && substr($transaction, -1) === 'D') {
    // On retire le "D" final pour retrouver l'ID réel de la commande
    $commande_id_reel  = substr($transaction, 0, -1);
    $commande_possible = trouver_commande($commande_id_reel);
    if ($commande_possible) {
        $est_paiement_diff = true;
    }
}

// ============================================================
//  CAS 1 : Paiement différentiel (modification de commande)
// ============================================================
if ($est_paiement_diff) {

    if ($statut === 'accepted') {
        // Vérifier que la session contient bien les nouvelles données à appliquer
        $modif = $_SESSION['modif_commande_en_attente'] ?? null;

        if ($modif && $modif['commande_id'] === $commande_id_reel) {
            // Appliquer les modifications dans le JSON
            $commandes = lire_json('commandes.json');
            foreach ($commandes as &$cmd) {
                if ($cmd['id'] !== $commande_id_reel) continue;
                $cmd['articles']   = $modif['nouveaux_articles'];
                $cmd['sous_total'] = $modif['sous_total'];
                $cmd['reduction']  = $modif['reduction'];
                $cmd['total']      = $modif['total'];
                break;
            }
            unset($cmd);
            ecrire_json('commandes.json', $commandes);

            // Ajouter des points de fidélité (1 pt par euro de différence payée)
            $commande_data = trouver_commande($commande_id_reel);
            $client_id     = $commande_data['client_id'] ?? '';
            if ($client_id !== '') {
                $tous_users = lire_json('users.json');
                foreach ($tous_users as &$u) {
                    if ($u['id'] === $client_id) {
                        $u['points_fidelite'] = ((int)($u['points_fidelite'] ?? 0)) + (int)floor((float)$montant);
                        break;
                    }
                }
                unset($u);
                ecrire_json('users.json', $tous_users);
            }

            // Nettoyer la session
            unset($_SESSION['modif_commande_en_attente']);
        }

        header('Location: ../profil.php?msg=modif_payee&commande_id=' . urlencode($commande_id_reel));

    } else {
        // Paiement refusé : on ne touche à rien, la commande reste inchangée
        unset($_SESSION['modif_commande_en_attente']);
        header('Location: ../profil.php?erreur=paiement_diff_refuse&commande_id=' . urlencode($commande_id_reel));
    }

    exit;
}

// ============================================================
//  CAS 2 : Paiement initial classique (première commande)
// ============================================================
$commande = trouver_commande($transaction);
if (!$commande) {
    header('Location: ../index.php?erreur=commande_introuvable');
    exit;
}

if ($statut === 'accepted') {
    maj_commande($transaction, 'paiement_statut', 'paye');
    maj_commande($transaction, 'statut', 'en_preparation');
    maj_commande($transaction, 'date_paiement', date('Y-m-d H:i:s'));

    // Ajouter des points de fidélité
    $client_id = $commande['client_id'] ?? '';
    if ($client_id !== '') {
        $tous_users = lire_json('users.json');
        foreach ($tous_users as &$u) {
            if ($u['id'] === $client_id) {
                $u['points_fidelite'] = ((int)($u['points_fidelite'] ?? 0)) + (int)floor((float)$montant);
                break;
            }
        }
        unset($u);
        ecrire_json('users.json', $tous_users);

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

    unset($_SESSION['panier']);
    unset($_SESSION['commande_en_cours']);

    header('Location: ../profil.php?msg=paiement_ok&commande_id=' . urlencode($transaction));

} else {
    maj_commande($transaction, 'paiement_statut', 'refuse');
    maj_commande($transaction, 'statut', 'abandonne');
    unset($_SESSION['commande_en_cours']);
    header('Location: ../panier.php?erreur=paiement_refuse');
}
exit;
