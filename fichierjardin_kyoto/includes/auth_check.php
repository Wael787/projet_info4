<?php
// includes/auth_check.php
// Usage : $roles_autorises = ['client']; include 'includes/auth_check.php';

if (!isset($_SESSION['user'])) {
    header('Location: connexion.php?erreur=non_connecte');
    exit;
}

if (isset($roles_autorises) && is_array($roles_autorises)
    && !in_array($_SESSION['user']['role'], $roles_autorises, true)) {
    header('Location: index.php?erreur=acces_refuse');
    exit;
}

$roles_valides = ['client', 'admin', 'restaurateur', 'livreur'];
if (!in_array($_SESSION['user']['role'] ?? '', $roles_valides, true)) {
    session_unset(); session_destroy();
    header('Location: connexion.php?erreur=session_invalide');
    exit;
}
