<?php
// actions/ajouter_menu_panier.php
// Ajoute tous les plats d'un menu configuré au panier en session
require_once '../includes/session.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: ../connexion.php?erreur=non_connecte');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../produit.php');
    exit;
}

$menu_id = trim($_POST['menu_id'] ?? '');
$menus   = lire_json('menus.json');
$menu    = null;
foreach ($menus as $m) {
    if (($m['id'] ?? '') === $menu_id) { $menu = $m; break; }
}

if (!$menu) {
    header('Location: ../produit.php?erreur=menu_introuvable');
    exit;
}

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Ajouter chaque plat du menu au panier
foreach ($menu['plats'] as $plat_id) {
    if (isset($_SESSION['panier'][$plat_id])) {
        $_SESSION['panier'][$plat_id]['quantite']++;
    } else {
        $_SESSION['panier'][$plat_id] = ['plat_id' => $plat_id, 'quantite' => 1];
    }
}

header('Location: ../panier.php?msg=ajout_ok');
exit;