<?php
// includes/header.php
// Inclure ce fichier en haut de chaque vue APRÈS session_start()
// Usage : $page_title = "Mon Titre"; include 'includes/header.php';
$page_title = $page_title ?? 'Le Jardin de Kyoto';
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Le Jardin de Kyoto</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= htmlspecialchars($body_class) ?>">

<header class="entete-principale">

    <div class="conteneur-logo">
        <img src="img/logo.png" alt="Logo Jardin De Kyoto" class="logo-principal">
        <p class="slogan-header">ART CULINAIRE &amp; SÉRÉNITÉ</p>
    </div>

    <div class="section-menu-burger">
        <button class="menu-burger" aria-label="Menu">☰</button>
        <nav class="navigation">
            <ul class="liste-du-haut">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="produit.php">Menu</a></li>

                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="profil.php">Mon profil</a></li>

                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <li><a href="admin.php">Administration</a></li>
                    <?php endif; ?>

                    <?php if ($_SESSION['user']['role'] === 'restaurateur'): ?>
                        <li><a href="commandes.php">Commandes</a></li>
                    <?php endif; ?>

                    <?php if ($_SESSION['user']['role'] === 'livreur'): ?>
                        <li><a href="livraison.php">Ma livraison</a></li>
                    <?php endif; ?>

                    <?php if ($_SESSION['user']['role'] === 'client'): ?>
                        <li><a href="panier.php">🛒 Mon panier</a></li>
                    <?php endif; ?>

                    <li>
                        <a href="actions/logout.php"
                           style="color: #c0392b;">
                            Déconnexion (<?= htmlspecialchars($_SESSION['user']['prenom']) ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="connexion.php">Connexion</a></li>
                    <li><a href="inscription.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

</header>