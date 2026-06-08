<?php
// actions/commander.php — crée la commande et prépare le paiement CYBank
require_once '../includes/session.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: ../connexion.php?erreur=non_connecte'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../panier.php'); exit;
}

$panier = $_SESSION['panier'] ?? [];
if (empty($panier)) {
    header('Location: ../panier.php?erreur=commande_vide'); exit;
}

$type_livraison    = $_POST['type_livraison']    ?? 'livraison';
$adresse_livraison = trim($_POST['adresse_livraison'] ?? '');
$infos_livraison   = trim($_POST['infos_livraison']   ?? '');
$heure_type        = $_POST['heure_type']        ?? 'immediat';
$heure_souhaitee   = ($heure_type === 'programmee') ? ($_POST['heure_souhaitee'] ?? null) : null;
$commentaire       = trim($_POST['commentaire']   ?? '');

$plats_json  = lire_json('plats.json');
$index_plats = array_column($plats_json, null, 'id');
$user        = trouver_utilisateur_par_id($_SESSION['user']['id']) ?? $_SESSION['user'];

$sous_total = 0.0;
$articles   = [];

foreach ($panier as $cle => $item) {
    if (($item['type'] ?? '') === 'menu') {
        $prix = (float)($item['prix'] ?? 0);
        $qte  = (int)$item['quantite'];
        $sous_total += $prix * $qte;
        $articles[] = [
            'type'        => 'menu',
            'nom'         => $item['nom'] ?? 'Menu',
            'description' => $item['description'] ?? '',
            'prix'        => $prix,
            'quantite'    => $qte,
        ];
    } else {
        $plat = $index_plats[$item['plat_id']] ?? null;
        if (!$plat) continue;
        $prix = (float)$plat['prix'];
        $qte  = (int)$item['quantite'];
        $sous_total += $prix * $qte;
        $articles[] = ['plat_id' => $item['plat_id'], 'quantite' => $qte];
    }
}

$remise    = (int)($user['remise'] ?? 0);
$reduction = $remise > 0 ? round($sous_total * $remise / 100, 2) : 0;
$total     = round($sous_total - $reduction, 2);

if ($total <= 0) {
    header('Location: ../panier.php?erreur=commande_vide'); exit;
}

$commande_id = strtoupper(generer_id());
$nouvelle_commande = [
    'id'                => $commande_id,
    'client_id'         => $user['id'],
    'articles'          => $articles,
    'type_livraison'    => $type_livraison,
    'adresse_livraison' => $adresse_livraison,
    'infos_livraison'   => $infos_livraison,
    'heure_souhaitee'   => $heure_souhaitee,
    'commentaire'       => $commentaire,
    'sous_total'        => $sous_total,
    'remise_pct'        => $remise,
    'reduction'         => $reduction,
    'total'             => $total,
    'statut'            => 'en_attente_paiement',
    'paiement_statut'   => 'en_attente',
    'date_commande'     => date('Y-m-d H:i:s'),
    'livreur_id'        => null,
    'date_paiement'     => null,
];

$commandes   = lire_json('commandes.json');
$commandes[] = $nouvelle_commande;
if (!ecrire_json('commandes.json', $commandes)) {
    header('Location: ../panier.php?erreur=erreur_serveur'); exit;
}

$_SESSION['commande_en_cours'] = ['id' => $commande_id, 'total' => $total];
header('Location: ../panier.php?msg=commande_creee');
exit;
