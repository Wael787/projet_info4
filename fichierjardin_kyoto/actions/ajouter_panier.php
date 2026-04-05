<?php
// actions/ajouter_panier.php — ajoute/retire/supprime un article du panier
require_once '../includes/session.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: ../connexion.php?erreur=non_connecte');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../panier.php');
    exit;
}

$plat_id  = trim($_POST['plat_id']  ?? '');
$quantite = (int)($_POST['quantite'] ?? 1);
$action   = $_POST['action'] ?? 'ajouter';

if ($plat_id === '') {
    header('Location: ../panier.php');
    exit;
}

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Les menus ont une clé qui commence par "MENU_"
$est_menu = (strpos($plat_id, 'MENU_') === 0);

if (!$est_menu) {
    // Plat normal : vérifier qu'il existe dans plats.json
    $plats = lire_json('plats.json');
    $index = array_column($plats, null, 'id');
    if (!isset($index[$plat_id])) {
        header('Location: ../panier.php?erreur=plat_introuvable');
        exit;
    }
}
// Pour les menus, la clé existe forcément en session si on les manipule depuis le panier

if ($action === 'supprimer') {
    unset($_SESSION['panier'][$plat_id]);
} else {
    if (isset($_SESSION['panier'][$plat_id])) {
        $nouvelle_qte = (int)$_SESSION['panier'][$plat_id]['quantite'] + $quantite;
        if ($nouvelle_qte <= 0) {
            unset($_SESSION['panier'][$plat_id]);
        } else {
            $_SESSION['panier'][$plat_id]['quantite'] = $nouvelle_qte;
        }
    } else {
        // Uniquement pour les plats normaux (les menus sont toujours ajoutés
        // via leur propre script et existent déjà en session)
        if (!$est_menu && $quantite > 0) {
            $_SESSION['panier'][$plat_id] = [
                'plat_id'  => $plat_id,
                'quantite' => $quantite,
            ];
        }
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../panier.php';
$sep     = strpos($referer, '?') === false ? '?' : '&';
header('Location: ' . $referer . $sep . 'msg=ajout_ok');
exit;