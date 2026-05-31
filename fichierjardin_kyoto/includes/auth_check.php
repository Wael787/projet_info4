<?php
// includes/auth_check.php
// À inclure dans les pages protégées, après session.php.
// Le check de blocage est déjà fait dans session.php donc ici on s'occupe
// juste de la connexion et du rôle.
//
// Usage :
//   $roles_autorises = ['admin'];           // admin only
//   $roles_autorises = ['client','admin'];  // plusieurs roles ok
//   include 'includes/auth_check.php';

// Pas connecté
if (!isset($_SESSION['user'])) {
    header('Location: connexion.php?erreur=non_connecte');
    exit;
}

// Rôle pas autorisé
if (isset($roles_autorises)
    && is_array($roles_autorises)
    && !in_array($_SESSION['user']['role'], $roles_autorises, true)) {
    $role_actuel = $_SESSION['user']['role'];
    header('Location: index.php?erreur=acces_refuse&role_requis='
           . urlencode(implode(',', $roles_autorises))
           . '&role_actuel=' . urlencode($role_actuel));
    exit;
}

// Garde fou au cas où la session aurait un rôle bizarre
$roles_valides = ['client', 'admin', 'restaurateur', 'livreur'];
if (!in_array($_SESSION['user']['role'] ?? '', $roles_valides, true)) {
    session_unset();
    session_destroy();
    header('Location: connexion.php?erreur=session_invalide');
    exit;
}