<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';

$page_title = 'Accueil';
$body_class = 'page-accueil';

$erreur = $_GET['erreur'] ?? null;

// Calcul des 3 plats les plus commandés
$toutes_commandes = lire_json('commandes.json');
$plats_json       = lire_json('plats.json');

// On compte combien de fois chaque plat a été commandé
$compteur = [];
foreach ($toutes_commandes as $commande) {
    foreach ($commande['articles'] ?? [] as $article) {
        $id = $article['plat_id'] ?? '';
        if ($id === '') continue;
        if (isset($compteur[$id])) {
            $compteur[$id] += (int)$article['quantite'];
        } else {
            $compteur[$id] = (int)$article['quantite'];
        }
    }
}

// Du plus commandé au moins commandé
arsort($compteur);

// Index des plats par id
$index_plats = [];
foreach ($plats_json as $p) {
    $index_plats[$p['id']] = $p;
}

// On prend les 3 premiers
$favoris = [];
foreach ($compteur as $plat_id => $nb) {
    if (isset($index_plats[$plat_id])) {
        $favoris[] = $index_plats[$plat_id];
    }
    if (count($favoris) >= 3) break;
}

// Si moins de 3 résultats, on complète avec des plats par défaut
$defaut = ['P006', 'P012', 'P016'];
foreach ($defaut as $id_defaut) {
    if (count($favoris) >= 3) break;
    $deja_la = false;
    foreach ($favoris as $f) {
        if ($f['id'] === $id_defaut) {
            $deja_la = true;
            break;
        }
    }
    if (!$deja_la && isset($index_plats[$id_defaut])) {
        $favoris[] = $index_plats[$id_defaut];
    }
}

include 'includes/header.php';
?>

<main>

    <?php if ($erreur === 'acces_refuse'): ?>
        <div class="message-erreur" style="background:#fdecea;color:#c0392b;padding:12px 20px;text-align:center;">
            Accès refusé. Vous n'avez pas les droits nécessaires pour accéder à cette page.
        </div>
    <?php endif; ?>

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

    <section class="section-favoris">
        <h2>Les favoris du moment</h2>
        <div class="grille-presentation">

            <?php foreach ($favoris as $plat): ?>
            <article class="produits-favoris">
                <img src="<?= htmlspecialchars($plat['image']) ?>"
                     alt="<?= htmlspecialchars($plat['nom']) ?>">
                <h3><?= htmlspecialchars($plat['nom']) ?></h3>
                <p class="prix-produit"><?= number_format($plat['prix'], 2, ',', '') ?> €</p>
            </article>
            <?php endforeach; ?>

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
