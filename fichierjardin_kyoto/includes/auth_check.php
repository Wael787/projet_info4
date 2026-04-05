<?php
// includes/auth_check.php
// Vérifie que l'utilisateur est connecté et a le bon rôle.
//
// Usage (à mettre juste après include 'includes/session.php') :
//   $roles_autorises = ['client'];          // une seule page client
//   $roles_autorises = ['admin'];           // admin seulement
//   $roles_autorises = ['restaurateur'];    // restaurateur seulement
//   $roles_autorises = ['livreur'];         // livreur seulement
//   $roles_autorises = ['client','admin'];  // plusieurs rôles acceptés
//   include 'includes/auth_check.php';
//
// Si $roles_autorises n'est pas défini, la page est accessible à tout
// utilisateur connecté (peu importe le rôle).

// 1. Pas connecté du tout → connexion
if (!isset($_SESSION['user'])) {
    header('Location: connexion.php?erreur=non_connecte');
    exit;
}

// 2. Rôle insuffisant → accueil avec message
if (isset($roles_autorises) && !in_array($_SESSION['user']['role'], $roles_autorises)) {
    header('Location: index.php?erreur=acces_refuse');
    exit;
}