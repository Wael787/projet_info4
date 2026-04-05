<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';

$page_title = 'Notre Menu';
$body_class = 'page-produits';

$tous_les_plats = lire_json('plats.json');

$recherche = trim($_GET['recherche'] ?? '');
$categorie = $_GET['categorie'] ?? '';

// Menu enfant séparé AVANT tout filtrage
$plat_enfant = [];
foreach ($tous_les_plats as $p) {
    if (($p['categorie'] ?? '') === 'menus_enfant') {
        $plat_enfant[] = $p;
    }
}
$plats_normaux = [];
foreach ($tous_les_plats as $p) {
    if (($p['categorie'] ?? '') !== 'menus_enfant') {
        $plats_normaux[] = $p;
    }
}

// Filtrage uniquement sur les plats normaux
if ($recherche !== '') {
    $tmp = [];
    foreach ($plats_normaux as $p) {
        if (stripos($p['nom'], $recherche) !== false || stripos($p['description'], $recherche) !== false) {
            $tmp[] = $p;
        }
    }
    $plats_normaux = $tmp;
}
if ($categorie !== '') {
    $tmp = [];
    foreach ($plats_normaux as $p) {
        if (($p['categorie'] ?? '') === $categorie) {
            $tmp[] = $p;
        }
    }
    $plats_normaux = $tmp;
}

$categories = [
    'sushis'       => 'Nos Sushis',
    'brochettes'   => 'Nos Brochettes',
    'plats_chauds' => 'Nos Plats Chauds',
    'desserts'     => 'Nos Desserts',
];
$par_categorie = [];
foreach ($plats_normaux as $plat) {
    $par_categorie[$plat['categorie']][] = $plat;
}

// Plats par catégorie pour le menu classique
$sushis_dispo = [];
foreach ($tous_les_plats as $p) {
    if ($p['categorie'] === 'sushis' && ($p['menu_classique'] ?? true)) {
        $sushis_dispo[] = $p;
    }
}
$brochettes_dispo = [];
foreach ($tous_les_plats as $p) {
    if ($p['categorie'] === 'brochettes' && ($p['menu_classique'] ?? true)) {
        $brochettes_dispo[] = $p;
    }
}
$desserts_dispo = [];
foreach ($tous_les_plats as $p) {
    if ($p['categorie'] === 'desserts' && ($p['menu_classique'] ?? true)) {
        $desserts_dispo[] = $p;
    }
}

$nb_panier = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) $nb_panier += $item['quantite'];
}

$est_client = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'client';

include 'includes/header.php';
?>
<main>

    <!-- HERO + RECHERCHE -->
    <section class="section-bienvenue">
        <div class="perso-accueil">
            <div class="bulle-bienvenue">Bon appétit !</div>
            <img src="img/Character_Teuchi.webp" alt="Cuisinier Ramen">
        </div>
        <h1>Découvrez Notre Carte</h1>
        <div class="boite-recherche">
            <form action="produit.php" method="get">
                <input type="text" id="nom_plat" name="recherche"
                       placeholder="Rechercher un plat" class="champ-recherche"
                       value="<?= htmlspecialchars($recherche) ?>">
                <button type="submit" class="bouton-recherche">Rechercher</button>
                <?php if ($recherche !== '' || $categorie !== ''): ?>
                    <a href="produit.php" class="bouton-recherche"
                       style="background:#888;margin-left:8px;">✕ Effacer</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- NAVIGATION CATÉGORIES -->
    <section class="conteneur-categories">
        <a href="produit.php?categorie=sushis"
           class="carte-categorie <?= $categorie==='sushis' ? 'active':'' ?>">
            <img src="img/Sushi.jpg" alt="Nos Sushis">
            <div class="overlay"><h3>Sushis</h3></div>
        </a>
        <a href="produit.php?categorie=brochettes"
           class="carte-categorie <?= $categorie==='brochettes' ? 'active':'' ?>">
            <img src="img/Brochettes.jpg" alt="Nos Brochettes">
            <div class="overlay"><h3>Yakitoris</h3></div>
        </a>
        <a href="produit.php?categorie=plats_chauds"
           class="carte-categorie <?= $categorie==='plats_chauds' ? 'active':'' ?>">
            <img src="img/PlatsChaud.jpg" alt="Plats Chauds">
            <div class="overlay"><h3>Plats Chauds</h3></div>
        </a>
        <a href="produit.php?categorie=desserts"
           class="carte-categorie <?= $categorie==='desserts' ? 'active':'' ?>">
            <img src="img/Dessert.jpg" alt="Nos Desserts">
            <div class="overlay"><h3>Desserts</h3></div>
        </a>
    </section>

    <!-- PLATS FILTRÉS -->
    <?php if (empty($plats_normaux)): ?>
        <p style="text-align:center;padding:40px;font-size:1.1em;color:#888;">
            Aucun plat ne correspond à votre recherche.
        </p>
    <?php endif; ?>

    <?php foreach ($categories as $cle => $titre): ?>
        <?php if (empty($par_categorie[$cle])) continue; ?>
        <h2 id="<?= $cle ?>" class="titre-categorie"><?= htmlspecialchars($titre) ?></h2>
        <section class="grille-produits">
            <?php foreach ($par_categorie[$cle] as $plat): ?>
            <article class="carte-produit">
                <div class="image-conteneur">
                    <img src="<?= htmlspecialchars($plat['image']) ?>"
                         alt="<?= htmlspecialchars($plat['nom']) ?>">
                </div>
                <div class="infos-produit">
                    <div class="entete-carte">
                        <h3><?= htmlspecialchars($plat['nom']) ?></h3>
                        <span class="prix"><?= number_format($plat['prix'], 2, ',', '') ?> €</span>
                    </div>
                    <p class="description"><?= htmlspecialchars($plat['description']) ?></p>
                    <div class="pied-carte">
                        <span class="badge"><?= htmlspecialchars($plat['badge']) ?></span>
                        <?php if ($est_client): ?>
                            <form action="actions/ajouter_panier.php" method="POST" style="display:inline">
                                <input type="hidden" name="plat_id"  value="<?= htmlspecialchars($plat['id']) ?>">
                                <input type="hidden" name="quantite" value="1">
                                <button type="submit" class="btn-ajouter">Ajouter</button>
                            </form>
                        <?php else: ?>
                            <a href="connexion.php" class="btn-ajouter">Commandez</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>


    <!-- ============================================================
         BANDEAU 1 — MENU PETIT SAMOURAÏ (toujours visible)
         ============================================================ -->
    <?php foreach ($plat_enfant as $plat): ?>
    <section class="bandeau-enfant">
        <img src="img/SamouraiEnfant.jpg" alt="" class="samourai-deco">
        <div class="contenu-enfant">
            <span class="emoji-samourai">🥢</span>
            <h2><?= htmlspecialchars($plat['nom']) ?></h2>
            <p class="prix-enfant"><?= number_format($plat['prix'], 2, ',', '') ?> €</p>
            <p class="desc-enfant"><?= htmlspecialchars($plat['description']) ?></p>
            <?php if ($est_client): ?>
                <form action="actions/ajouter_panier.php" method="POST">
                    <input type="hidden" name="plat_id"  value="<?= htmlspecialchars($plat['id']) ?>">
                    <input type="hidden" name="quantite" value="1">
                    <button type="submit" class="btn-enfant">Ajouter au panier</button>
                </form>
            <?php else: ?>
                <a href="connexion.php" class="btn-enfant">Se connecter pour commander</a>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>


    <!-- ============================================================
         BANDEAU 2 — MENU DUO FESTIF (fixe, toujours visible)
         ============================================================ -->
    <section class="bandeau-enfant"
             style="background:linear-gradient(135deg,#E8F4FF 0%,#FFF0E5 100%);">
        <div class="contenu-enfant">
            <span class="emoji-samourai">🎉</span>
            <h2>Menu Duo Festif</h2>
            <p class="prix-enfant">43,40 €</p>
            <p class="desc-enfant">
                Pour 2 personnes — Plateau Kyoto Mix (18 pièces) + Mix Yakitori + 2 Mochis Glacés.
            </p>
            <ul style="list-style:none;padding:0;margin:0 0 .8rem;
                       text-align:left;display:inline-block;font-size:.95rem;color:#444;line-height:1.9;">
                <li>🍣 Plateau Kyoto Mix — 18 pièces variées</li>
                <li>🍢 Mix Yakitori — toutes brochettes + riz</li>
                <li>🍡 Mochi Glacé × 2</li>
                <li style="color:#27ae60;font-weight:bold;">💚 Économie de 4 € vs la carte</li>
            </ul>
            <?php if ($est_client): ?>
                <form action="actions/ajouter_menu_panier.php" method="POST">
                    <input type="hidden" name="menu_id" value="M003">
                    <button type="submit" class="btn-enfant">Ajouter au panier</button>
                </form>
            <?php else: ?>
                <a href="connexion.php" class="btn-enfant">Se connecter pour commander</a>
            <?php endif; ?>
        </div>
    </section>


    <!-- ============================================================
         BANDEAU 3 — MENU CLASSIQUE ADAPTATIF (choix fonctionnels)
         Prix : 17,90 € — sushi + brochette + dessert + Ramune offerte
         ============================================================ -->
    <section class="bandeau-enfant"
             style="background:linear-gradient(135deg,#F0FFE8 0%,#FFFDE5 100%);">
        <div class="contenu-enfant">
            <span class="emoji-samourai">🍱</span>
            <h2>Menu Classique</h2>
            <p class="prix-enfant">17,90 €</p>
            <p class="desc-enfant">
                Composez votre repas parmi nos spécialités.
                <strong style="color:var(--rouge);">🥤 Ramune offerte !</strong>
            </p>

            <?php if ($est_client): ?>
            <form action="actions/ajouter_menu_classique.php" method="POST"
                  style="text-align:left;display:inline-block;max-width:440px;width:100%;">

                <div class="choix-menu">
                    <label class="choix-label">🍣 Votre sushi :</label>
                    <div class="choix-options">
                        <?php foreach ($sushis_dispo as $s): ?>
                        <label class="option-radio">
                            <input type="radio" name="sushi"
                                   value="<?= htmlspecialchars($s['id']) ?>" required>
                            <span><?= htmlspecialchars($s['nom']) ?>
                                <em style="color:#888;font-size:.82em;">
                                    <?= number_format($s['prix'],2,',','') ?> €
                                </em>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="choix-menu">
                    <label class="choix-label">🍢 Votre brochette :</label>
                    <div class="choix-options">
                        <?php foreach ($brochettes_dispo as $b): ?>
                        <label class="option-radio">
                            <input type="radio" name="brochette"
                                   value="<?= htmlspecialchars($b['id']) ?>" required>
                            <span><?= htmlspecialchars($b['nom']) ?>
                                <em style="color:#888;font-size:.82em;">
                                    <?= number_format($b['prix'],2,',','') ?> €
                                </em>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="choix-menu">
                    <label class="choix-label">🍡 Votre dessert :</label>
                    <div class="choix-options">
                        <?php foreach ($desserts_dispo as $d): ?>
                        <label class="option-radio">
                            <input type="radio" name="dessert"
                                   value="<?= htmlspecialchars($d['id']) ?>" required>
                            <span><?= htmlspecialchars($d['nom']) ?>
                                <em style="color:#888;font-size:.82em;">
                                    <?= number_format($d['prix'],2,',','') ?> €
                                </em>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p style="color:#27ae60;font-weight:bold;margin:10px 0 14px;font-size:.9rem;">
                    🥤 Ramune (boisson japonaise pétillante) offerte avec ce menu !
                </p>

                <button type="submit" class="btn-enfant">
                    Ajouter ce menu au panier
                </button>
            </form>

            <?php else: ?>
                <a href="connexion.php" class="btn-enfant"
                   style="display:inline-block;margin-top:14px;">
                    Se connecter pour composer votre menu
                </a>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($nb_panier > 0): ?>
    <a href="panier.php" class="btn-panier-flottant">
        🛒 Mon panier (<?= $nb_panier ?> article<?= $nb_panier > 1 ? 's' : '' ?>)
    </a>
    <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>