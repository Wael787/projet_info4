<?php
// actions/ajouter_menu_classique.php
// Ajoute le Menu Classique comme UN SEUL article au panier,
// avec la description des choix du client stockée dedans.
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

$sushi_id     = trim($_POST['sushi']     ?? '');
$brochette_id = trim($_POST['brochette'] ?? '');
$dessert_id   = trim($_POST['dessert']   ?? '');

if ($sushi_id === '' || $brochette_id === '' || $dessert_id === '') {
    header('Location: ../produit.php?erreur=choix_incomplets');
    exit;
}

// Vérifier que les plats existent et appartiennent aux bonnes catégories
$plats       = lire_json('plats.json');
$index_plats = array_column($plats, null, 'id');

if (!isset($index_plats[$sushi_id])     || $index_plats[$sushi_id]['categorie']     !== 'sushis'     ||
    !isset($index_plats[$brochette_id]) || $index_plats[$brochette_id]['categorie'] !== 'brochettes' ||
    !isset($index_plats[$dessert_id])   || $index_plats[$dessert_id]['categorie']   !== 'desserts') {
    header('Location: ../produit.php?erreur=plat_invalide');
    exit;
}

$nom_sushi     = $index_plats[$sushi_id]['nom'];
$nom_brochette = $index_plats[$brochette_id]['nom'];
$nom_dessert   = $index_plats[$dessert_id]['nom'];

// Clé unique dans le panier pour ce menu avec ces choix précis
// (deux menus classiques avec des choix différents = deux articles distincts)
$cle_panier = 'MENU_CLASSIQUE_' . $sushi_id . '_' . $brochette_id . '_' . $dessert_id;

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

if (isset($_SESSION['panier'][$cle_panier])) {
    $_SESSION['panier'][$cle_panier]['quantite']++;
} else {
    $_SESSION['panier'][$cle_panier] = [
        'plat_id'     => $cle_panier,           // clé unique dans le panier
        'quantite'    => 1,
        'type'        => 'menu',                 // indique que c'est un menu
        'nom'         => 'Menu Classique',
        'prix'        => 17.90,
        'description' => $nom_sushi . ' + ' . $nom_brochette . ' + ' . $nom_dessert . ' + Ramune offerte',
    ];
}

header('Location: ../panier.php?msg=ajout_ok');
exit;