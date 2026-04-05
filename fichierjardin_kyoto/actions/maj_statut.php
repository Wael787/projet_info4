<?php
// actions/maj_statut.php
require_once '../includes/session.php';
require_once '../includes/utils.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../connexion.php?erreur=non_connecte');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$role           = $_SESSION['user']['role'];
$commande_id    = trim($_POST['commande_id']    ?? '');
$nouveau_statut = trim($_POST['nouveau_statut'] ?? '');
$action         = trim($_POST['action']         ?? '');
$redirect       = $_POST['redirect'] ?? 'index.php';

// Sécuriser la redirection
if (!preg_match('/^[a-zA-Z0-9_.\/\-?=&]+$/', $redirect)) {
    $redirect = 'index.php';
}

if ($commande_id === '') {
    header('Location: ../' . $redirect . '?erreur=commande_manquante');
    exit;
}

$commande = trouver_commande($commande_id);
if (!$commande) {
    header('Location: ../' . $redirect . '?erreur=commande_introuvable');
    exit;
}

$sep = strpos($redirect, '?') !== false ? '&' : '?';

// ---------------------------------------------------------------
// ACTION SPÉCIALE : attribuer livreur + passer en_livraison
// ---------------------------------------------------------------
if ($action === 'attribuer_et_livrer') {
    if (!in_array($role, ['restaurateur', 'admin'])) {
        header('Location: ../' . $redirect . '?erreur=acces_refuse');
        exit;
    }
    $livreur_id = trim($_POST['livreur_id'] ?? '');
    if ($livreur_id === '') {
        header('Location: ../' . $redirect . '?erreur=livreur_manquant');
        exit;
    }
    // Vérifier que c'est bien un livreur actif
    $users = lire_json('users.json');
    $valide = false;
    foreach ($users as $u) {
        if ($u['id'] === $livreur_id && $u['role'] === 'livreur' && $u['statut'] === 'actif') {
            $valide = true;
            break;
        }
    }
    if (!$valide) {
        header('Location: ../' . $redirect . '?erreur=livreur_invalide');
        exit;
    }
    maj_commande($commande_id, 'livreur_id', $livreur_id);
    maj_commande($commande_id, 'statut', 'en_livraison');
    header('Location: ../' . $redirect . $sep . 'msg=statut_ok');
    exit;
}

// ---------------------------------------------------------------
// CHANGEMENT DE STATUT STANDARD
// ---------------------------------------------------------------
$statuts_valides = ['en_attente', 'en_preparation', 'en_livraison', 'pret', 'servi', 'livre', 'abandonne'];

if ($nouveau_statut !== '' && in_array($nouveau_statut, $statuts_valides)) {

    $autorise = false;
    $type     = $commande['type_livraison'] ?? 'livraison';

    if (in_array($role, ['restaurateur', 'admin'])) {
        // Restaurateur : peut faire avancer la commande selon le type
        $statuts_resto = ['en_preparation', 'abandonne'];
        if ($type === 'livraison')  $statuts_resto[] = 'en_livraison';
        if ($type === 'emporter')   $statuts_resto[] = 'pret';
        if ($type === 'sur_place')  $statuts_resto[] = 'servi';
        $autorise = in_array($nouveau_statut, $statuts_resto);
    }

    if (in_array($role, ['livreur', 'admin'])) {
        // Livreur : peut marquer livré ou abandonné
        $autorise = $autorise || in_array($nouveau_statut, ['livre', 'abandonne']);
    }

    if ($role === 'admin') {
        $autorise = true;
    }

    if (!$autorise) {
        header('Location: ../' . $redirect . '?erreur=acces_refuse');
        exit;
    }

    maj_commande($commande_id, 'statut', $nouveau_statut);

    // Dates selon le statut final
    if ($nouveau_statut === 'livre') {
        maj_commande($commande_id, 'date_livraison', date('Y-m-d H:i:s'));
    }
    if ($nouveau_statut === 'servi') {
        maj_commande($commande_id, 'date_service', date('Y-m-d H:i:s'));
    }
    if ($nouveau_statut === 'pret') {
        maj_commande($commande_id, 'date_pret', date('Y-m-d H:i:s'));
    }

    header('Location: ../' . $redirect . $sep . 'msg=statut_ok');
    exit;
}

// ---------------------------------------------------------------
// ATTRIBUTION LIVREUR SEULE (sans changer le statut)
// ---------------------------------------------------------------
if ($action === 'attribuer_livreur' && in_array($role, ['restaurateur', 'admin'])) {
    $livreur_id = trim($_POST['livreur_id'] ?? '');
    if ($livreur_id !== '') {
        $users = lire_json('users.json');
        foreach ($users as $u) {
            if ($u['id'] === $livreur_id && $u['role'] === 'livreur') {
                maj_commande($commande_id, 'livreur_id', $livreur_id);
                break;
            }
        }
    }
    header('Location: ../' . $redirect . $sep . 'msg=statut_ok');
    exit;
}

header('Location: ../' . $redirect . '?erreur=action_inconnue');
exit;