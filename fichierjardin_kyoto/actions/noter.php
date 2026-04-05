<?php
// actions/noter.php — enregistre une notation dans notations.json
require_once '../includes/session.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header('Location: ../connexion.php?erreur=non_connecte');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profil.php');
    exit;
}

$commande_id    = trim($_POST['commande_id']    ?? '');
$note_livraison = (int)($_POST['note_livraison'] ?? 0);
$note_produit   = (int)($_POST['note_produit']   ?? 0);
$avis_texte     = trim($_POST['avis_texte']      ?? '');

// Validation
if ($commande_id === '' || $note_livraison < 1 || $note_livraison > 5
    || $note_produit < 1 || $note_produit > 5) {
    header('Location: ../notation.php?commande_id=' . urlencode($commande_id) . '&erreur=champs_manquants');
    exit;
}

// Vérifier que la commande appartient bien à ce client
$commande = trouver_commande($commande_id);
if (!$commande || ($commande['client_id'] ?? '') !== $_SESSION['user']['id']) {
    header('Location: ../profil.php?erreur=commande_introuvable');
    exit;
}
if (($commande['statut'] ?? '') !== 'livre') {
    header('Location: ../profil.php?erreur=non_livree');
    exit;
}

// Vérifier pas déjà noté
$notations = lire_json('notations.json');
foreach ($notations as $n) {
    if (($n['commande_id'] ?? '') === $commande_id) {
        header('Location: ../profil.php?msg=deja_note');
        exit;
    }
}

// Enregistrer la notation
$notation = [
    'id'              => generer_id(),
    'commande_id'     => $commande_id,
    'client_id'       => $_SESSION['user']['id'],
    'note_livraison'  => $note_livraison,
    'note_produit'    => $note_produit,
    'avis_texte'      => $avis_texte,
    'date'            => date('Y-m-d H:i:s'),
];

$notations[] = $notation;
ecrire_json('notations.json', $notations);

header('Location: ../profil.php?msg=note_ok');
exit;