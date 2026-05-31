<?php
// includes/header.php
// Header commun à toutes les pages.
// Gère aussi le thème (couleur + loupe) via 2 cookies.

$page_title = $page_title ?? 'Le Jardin de Kyoto';
$body_class = $body_class ?? '';

// --- thème couleur (light / dark / contrast) ---
$couleurs_autorisees = ['light', 'dark', 'contrast'];
$theme_couleur = 'light';
if (isset($_COOKIE['theme_couleur']) && in_array($_COOKIE['theme_couleur'], $couleurs_autorisees, true)) {
    $theme_couleur = $_COOKIE['theme_couleur'];
}

// --- mode loupe (on/off, indépendant du thème couleur) ---
$loupe_autorise = ['on', 'off'];
$theme_loupe = 'off';
if (isset($_COOKIE['theme_loupe']) && in_array($_COOKIE['theme_loupe'], $loupe_autorise, true)) {
    $theme_loupe = $_COOKIE['theme_loupe'];
}

// On construit la classe du body
$classes_theme = [];
if ($theme_couleur === 'dark')     $classes_theme[] = 'dark-mode';
if ($theme_couleur === 'contrast') $classes_theme[] = 'contrast-mode';
if ($theme_loupe === 'on')         $classes_theme[] = 'large-mode';

$body_class_finale = trim($body_class . ' ' . implode(' ', $classes_theme));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Le Jardin de Kyoto</title>
    <link rel="stylesheet" href="/jardin_kyoto/style.css">

    <!-- Scripts chargés en defer : ils s'exécutent une fois la page lue -->
    <script src="/jardin_kyoto/js/commun.js" defer></script>
    <?php foreach (($scripts_page ?? []) as $script_page): ?>
        <script src="/jardin_kyoto/js/<?= htmlspecialchars($script_page) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($body_class_finale) ?>"
      data-connecte="<?= isset($_SESSION['user']) ? '1' : '0' ?>">

<header class="entete-principale">

    <div class="conteneur-logo">
        <img src="/jardin_kyoto/img/logo.png" alt="Logo Jardin De Kyoto" class="logo-principal">
        <p class="slogan-header">ART CULINAIRE &amp; SÉRÉNITÉ</p>
    </div>

    <div class="section-menu-burger">
        <button class="menu-burger" aria-label="Menu">☰</button>
        <nav class="navigation">
            <ul class="liste-du-haut">
                <li><a href="/jardin_kyoto/index.php">Accueil</a></li>
                <li><a href="/jardin_kyoto/produit.php">Menu</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="/jardin_kyoto/profil.php">Mon profil</a></li>
                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <li><a href="/jardin_kyoto/admin.php">Administration</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role'] === 'restaurateur'): ?>
                        <li><a href="/jardin_kyoto/commandes.php">Commandes</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role'] === 'livreur'): ?>
                        <li><a href="/jardin_kyoto/livraison.php">Ma livraison</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role'] === 'client'): ?>
                        <li><a href="/jardin_kyoto/panier.php">🛒 Mon panier</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="/jardin_kyoto/actions/logout.php" style="color: #c0392b;">
                            Déconnexion (<?= htmlspecialchars($_SESSION['user']['prenom']) ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="/jardin_kyoto/connexion.php">Connexion</a></li>
                    <li><a href="/jardin_kyoto/inscription.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Selecteur de thème : 3 boutons exclusifs + 1 toggle loupe à part -->
    <div class="theme-selector" role="toolbar" aria-label="Préférences d'affichage">

        <div class="theme-group theme-group-couleur" role="radiogroup" aria-label="Schéma de couleur">
            <button type="button" class="theme-btn" data-couleur="light"
                    <?= $theme_couleur === 'light' ? 'aria-current="true"' : '' ?>
                    title="Mode clair">
                <span class="theme-icon">☀️</span>
                <span class="theme-label">Clair</span>
            </button>
            <button type="button" class="theme-btn" data-couleur="dark"
                    <?= $theme_couleur === 'dark' ? 'aria-current="true"' : '' ?>
                    title="Mode sombre">
                <span class="theme-icon">🌙</span>
                <span class="theme-label">Sombre</span>
            </button>
            <button type="button" class="theme-btn" data-couleur="contrast"
                    <?= $theme_couleur === 'contrast' ? 'aria-current="true"' : '' ?>
                    title="Mode contrasté">
                <span class="theme-icon">🌗</span>
                <span class="theme-label">Contraste</span>
            </button>
        </div>

        <div class="theme-separator" aria-hidden="true"></div>

        <div class="theme-group theme-group-loupe">
            <button type="button" class="theme-btn theme-btn-loupe" data-loupe="toggle"
                    <?= $theme_loupe === 'on' ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>
                    title="Activer/désactiver le mode loupe">
                <span class="theme-icon">🔍</span>
                <span class="theme-label">Loupe</span>
            </button>
        </div>

    </div>

</header>

