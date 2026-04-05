<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';

$page_title = 'Accueil';
$body_class = 'page-accueil';

// Message d'erreur éventuel (accès refusé depuis auth_check)
$erreur = $_GET['erreur'] ?? null;

include 'includes/header.php';
?>

<main>

    <?php if ($erreur === 'acces_refuse'): ?>
        <div class="message-erreur" style="background:#fdecea;color:#c0392b;padding:12px 20px;text-align:center;">
            Accès refusé. Vous n'avez pas les droits nécessaires pour accéder à cette page.
        </div>
    <?php endif; ?>

    <!-- SECTION BIENVENUE -->
    <section class="section-bienvenue">

        <div class="perso-accueil">
            <div class="bulle-bienvenue">
                <?php if (isset($_SESSION['user'])): ?>
                    Bonjour <?= htmlspecialchars($_SESSION['user']['prenom']) ?> !
                <?php else: ?>
                    Bienvenue !
                <?php endif; ?>
            </div>
            <img src="img/Character_Teuchi.webp" alt="Cuisinier Ramen">
        </div>

        <h1>Savourez l'excellence de la cuisine Japonaise</h1>

        <div class="boite-recherche">
            <form action="produit.php" method="get">
                <input type="text" id="nom_plat" name="recherche"
                       placeholder="Rechercher un plat" class="champ-recherche"
                       value="<?= htmlspecialchars($_GET['recherche'] ?? '') ?>">
                <button type="submit" class="bouton-recherche">Rechercher</button>
            </form>
        </div>

    </section>

    <!-- SECTION FAVORIS -->
    <section class="section-favoris">
        <h2>Les favoris du moment</h2>
        <div class="grille-presentation">

            <article class="produits-favoris">
                <img src="img/PlateauKyoto2.jpg" alt="Plateau Kyoto Mix">
                <h3>Plateau Kyoto Mix</h3>
                <p class="prix-produit">17,90 €</p>
            </article>

            <article class="produits-favoris">
                <img src="img/RamenIchiraku.jpg" alt="Ramen Ichiraku">
                <h3>Ramen Ichiraku</h3>
                <p class="prix-produit">14,50 €</p>
            </article>

            <article class="produits-favoris">
                <img src="img/MochiSakura.jpg" alt="Mochis Glacé">
                <h3>Perles de Sakura</h3>
                <p class="prix-produit">6,90 €</p>
            </article>

            <article class="produits-favoris carte-menu">
                <a href="produit.php">
                    <img src="img/TourJapon.jpg" alt="Voir le menu">
                    <div class="texte-survol">
                        <h3>Découvrir les autres plats</h3>
                        <p>Cliquez pour ouvrir le menu →</p>
                    </div>
                </a>
            </article>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>