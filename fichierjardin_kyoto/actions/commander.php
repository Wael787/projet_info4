<?php
// actions/commander.php
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

$panier = $_SESSION['panier'] ?? [];
if (empty($panier)) {
    header('Location: ../panier.php?erreur=commande_vide');
    exit;
}

$type_livraison  = in_array($_POST['type_livraison'] ?? '', ['livraison','emporter','sur_place'])
                    ? $_POST['type_livraison'] : 'livraison';
$adresse         = trim($_POST['adresse_livraison'] ?? '');
$infos           = trim($_POST['infos_livraison']   ?? '');
$commentaire     = trim($_POST['commentaire']       ?? '');
$heure_type      = $_POST['heure_type'] ?? 'immediat';
$heure_souhaitee = ($heure_type === 'programmee') ? trim($_POST['heure_souhaitee'] ?? '') : null;

if ($type_livraison === 'livraison' && $adresse === '') {
    header('Location: ../panier.php?erreur=champs_manquants');
    exit;
}

$plats_json  = lire_json('plats.json');
$index_plats = array_column($plats_json, null, 'id');
$user        = trouver_utilisateur_par_id($_SESSION['user']['id']) ?? $_SESSION['user'];

$sous_total = 0.0;
$articles   = [];

foreach ($panier as $item) {
    $est_menu = isset($item['type']) && $item['type'] === 'menu';

    if ($est_menu) {
        // Article de type menu : toutes les infos sont dans l'item de session
        $prix        = (float)($item['prix'] ?? 0);
        $qte         = (int)$item['quantite'];
        $sous_total += $prix * $qte;
        $articles[]  = [
            'plat_id'     => $item['plat_id'],
            'type'        => 'menu',
            'nom'         => $item['nom'] ?? 'Menu',
            'description' => $item['description'] ?? '',
            'quantite'    => $qte,
            'prix_unit'   => $prix,
        ];
    } else {
        // Plat normal
        $plat = $index_plats[$item['plat_id']] ?? null;
        if (!$plat) continue;
        $prix        = (float)$plat['prix'];
        $qte         = (int)$item['quantite'];
        $sous_total += $prix * $qte;
        $articles[]  = [
            'plat_id'   => $item['plat_id'],
            'quantite'  => $qte,
            'prix_unit' => $prix,
        ];
    }
}

$remise    = (int)($user['remise'] ?? 0);
$reduction = $remise > 0 ? round($sous_total * $remise / 100, 2) : 0;
$total     = round($sous_total - $reduction, 2);

$commande_id = generer_id();
$commande = [
    'id'               => $commande_id,
    'client_id'        => $user['id'],
    'articles'         => $articles,
    'type_livraison'   => $type_livraison,
    'adresse'          => $adresse,
    'infos_livraison'  => $infos,
    'commentaire'      => $commentaire,
    'heure_souhaitee'  => $heure_souhaitee,
    'sous_total'       => $sous_total,
    'remise'           => $remise,
    'reduction'        => $reduction,
    'total'            => $total,
    'statut'           => 'en_attente',
    'paiement_statut'  => 'en_attente',
    'livreur_id'       => null,
    'date'             => date('Y-m-d H:i:s'),
];

$commandes   = lire_json('commandes.json');
$commandes[] = $commande;
ecrire_json('commandes.json', $commandes);

$_SESSION['commande_en_cours'] = ['id' => $commande_id, 'total' => $total];

header('Location: ../panier.php?msg=commande_creee');
exit;