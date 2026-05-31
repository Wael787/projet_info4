<?php
// actions/maj_commande_client.php
// Reçoit les nouvelles quantités d'une commande "en_attente" en JSON.
// Si le total augmente : stocke les changements en SESSION (pas encore dans le JSON)
//et renvoie le formulaire CYBank pour payer la différence.
// La vraie sauvegarde se fait dans retour_paiement.php après confirmation du paiement.
// Si le total diminue ou est identique : sauvegarde directement dans le JSON.

require_once '../includes/session.php';
require_once '../includes/utils.php';
require_once '../includes/cybank.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['succes' => false, 'message' => 'Non connecté.']);
    exit;
}

$corps   = file_get_contents('php://input');
$donnees = json_decode($corps, true);

if (!$donnees) {
    echo json_encode(['succes' => false, 'message' => 'Données invalides.']);
    exit;
}

$commande_id    = $donnees['commande_id'] ?? '';
$nouvelles_qtes = $donnees['quantites']   ?? [];

if ($commande_id === '') {
    echo json_encode(['succes' => false, 'message' => 'Commande introuvable.']);
    exit;
}

$commandes = lire_json('commandes.json');
$cmd_trouvee = null;

foreach ($commandes as $cmd) {
    if ($cmd['id'] === $commande_id) {
        $cmd_trouvee = $cmd;
        break;
    }
}

if (!$cmd_trouvee) {
    echo json_encode(['succes' => false, 'message' => 'Commande introuvable.']);
    exit;
}

if ($cmd_trouvee['client_id'] !== $_SESSION['user']['id']) {
    echo json_encode(['succes' => false, 'message' => 'Action non autorisée.']);
    exit;
}

if (($cmd_trouvee['statut'] ?? '') !== 'en_attente') {
    echo json_encode(['succes' => false, 'message' => 'La commande est déjà en préparation, elle ne peut plus être modifiée.']);
    exit;
}

$ancien_total = (float)($cmd_trouvee['total'] ?? 0);

// Charger les plats pour recalculer les prix
$plats_json  = lire_json('plats.json');
$index_plats = [];
foreach ($plats_json as $p) {
    $index_plats[$p['id']] = $p;
}

// Calculer les nouveaux articles
$nouveaux_articles = [];
foreach ($cmd_trouvee['articles'] as $article) {
    $pid      = $article['plat_id'];
    $nouvelle = isset($nouvelles_qtes[$pid]) ? (int)$nouvelles_qtes[$pid] : (int)$article['quantite'];

    if ($nouvelle <= 0) continue;

    $article['quantite'] = $nouvelle;
    $nouveaux_articles[] = $article;
}

if (empty($nouveaux_articles)) {
    echo json_encode(['succes' => false, 'message' => 'Impossible de tout supprimer. Annulez la commande plutôt.']);
    exit;
}

// Recalculer le total
$sous_total = 0;
foreach ($nouveaux_articles as $art) {
    $prix        = (float)($index_plats[$art['plat_id']]['prix'] ?? $art['prix_unit'] ?? 0);
    $sous_total += $prix * (int)$art['quantite'];
}

$users  = lire_json('users.json');
$remise = 0;
foreach ($users as $u) {
    if ($u['id'] === $_SESSION['user']['id']) {
        $remise = (int)($u['remise'] ?? 0);
        break;
    }
}

$reduction     = $remise > 0 ? round($sous_total * $remise / 100, 2) : 0;
$nouveau_total = round($sous_total - $reduction, 2);
$difference    = round($nouveau_total - $ancien_total, 2);

// URL de retour CYBank
$protocole  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base       = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$url_retour = $protocole . '://' . $host . $base . '/actions/retour_paiement.php';

if ($difference > 0) {
    // Le total a augmenté.
    // On NE sauvegarde PAS encore dans le JSON.
    // On stocke les nouvelles données en session pour que retour_paiement.php
    // puisse les appliquer après confirmation du paiement.
    $_SESSION['modif_commande_en_attente'] = [
        'commande_id'       => $commande_id,
        'nouveaux_articles' => $nouveaux_articles,
        'sous_total'        => $sous_total,
        'reduction'         => $reduction,
        'total'             => $nouveau_total,
    ];

    // L'ID de transaction pour CYBank : ID commande + "D" (sans underscore)
    $id_diff    = $commande_id . 'D';
    $formulaire = cybank_formulaire_paiement($id_diff, $difference, $url_retour);

    echo json_encode([
        'succes'     => true,
        'situation'  => 'plus_cher',
        'difference' => $difference,
        'formulaire' => $formulaire,
        'message'    => 'Votre commande a augmenté de ' . number_format($difference, 2, ',', '') . ' €. Veuillez payer la différence.'
    ]);

} elseif ($difference < 0) {
    // Le total a diminué : on sauvegarde directement, pas de paiement requis
    foreach ($commandes as &$cmd) {
        if ($cmd['id'] !== $commande_id) continue;
        $cmd['articles']   = $nouveaux_articles;
        $cmd['sous_total'] = $sous_total;
        $cmd['reduction']  = $reduction;
        $cmd['total']      = $nouveau_total;
        break;
    }
    unset($cmd);

    if (ecrire_json('commandes.json', $commandes)) {
        echo json_encode([
            'succes'    => true,
            'situation' => 'moins_cher',
            'message'   => 'Commande réduite. Aucun remboursement n\'est prévu.'
        ]);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de la sauvegarde.']);
    }

} else {
    // Même total : on sauvegarde directement
    foreach ($commandes as &$cmd) {
        if ($cmd['id'] !== $commande_id) continue;
        $cmd['articles']   = $nouveaux_articles;
        $cmd['sous_total'] = $sous_total;
        $cmd['reduction']  = $reduction;
        $cmd['total']      = $nouveau_total;
        break;
    }
    unset($cmd);

    if (ecrire_json('commandes.json', $commandes)) {
        echo json_encode([
            'succes'    => true,
            'situation' => 'identique',
            'message'   => 'Commande mise à jour !'
        ]);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Erreur lors de la sauvegarde.']);
    }
}