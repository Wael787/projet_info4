<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';

$page_title = 'Notre Menu';
$body_class = 'page-produits';

$tous_les_plats = lire_json('plats.json');

// Séparer les plats enfant des plats normaux
$plat_enfant   = [];
$plats_normaux = [];
foreach ($tous_les_plats as $p) {
    if (($p['categorie'] ?? '') === 'menus_enfant') {
        $plat_enfant[] = $p;
    } else {
        $plats_normaux[] = $p;
    }
}

// Grouper par catégorie pour l'affichage initial
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

// Plats pour le Menu Classique
$sushis_dispo = $brochettes_dispo = $desserts_dispo = [];
foreach ($tous_les_plats as $p) {
    if ($p['categorie'] === 'sushis'     && ($p['menu_classique'] ?? true)) { $sushis_dispo[]     = $p; }
    if ($p['categorie'] === 'brochettes' && ($p['menu_classique'] ?? true)) { $brochettes_dispo[] = $p; }
    if ($p['categorie'] === 'desserts'   && ($p['menu_classique'] ?? true)) { $desserts_dispo[]   = $p; }
}

// Nombre d'articles dans le panier
$nb_panier = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $nb_panier += $item['quantite'];
    }
}

$est_client = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'client';

include 'includes/header.php';
?>
<main>

    <section class="section-bienvenue">
        <div class="perso-accueil">
            <div class="bulle-bienvenue">Bon appétit !</div>
            <img src="img/Character_Teuchi.webp" alt="Cuisinier Ramen">
        </div>
        <h1>Découvrez Notre Carte</h1>
        <div class="boite-recherche">
            <form id="form-recherche">
                <input type="text" id="champ-recherche" name="recherche"
                       placeholder="Rechercher un plat" class="champ-recherche">
                <button type="submit" class="bouton-recherche">Rechercher</button>
                <button type="button" id="btn-effacer"
                        class="bouton-recherche" style="background:#888;margin-left:8px;display:none;">
                    ✕ Effacer
                </button>
            </form>
        </div>
    </section>

    <!-- Boutons catégories — plus de liens href, maintenant gérés par le JS -->
    <section class="conteneur-categories">
        <button class="carte-categorie filtre-cat" data-cat="sushis">
            <img src="img/Sushi.jpg" alt="Nos Sushis">
            <div class="overlay"><h3>Sushis</h3></div>
        </button>
        <button class="carte-categorie filtre-cat" data-cat="brochettes">
            <img src="img/Brochettes.jpg" alt="Nos Brochettes">
            <div class="overlay"><h3>Yakitoris</h3></div>
        </button>
        <button class="carte-categorie filtre-cat" data-cat="plats_chauds">
            <img src="img/PlatsChaud.jpg" alt="Plats Chauds">
            <div class="overlay"><h3>Plats Chauds</h3></div>
        </button>
        <button class="carte-categorie filtre-cat" data-cat="desserts">
            <img src="img/Dessert.jpg" alt="Nos Desserts">
            <div class="overlay"><h3>Desserts</h3></div>
        </button>
    </section>

    <!-- Barre de filtres régime + tris -->
    <div class="barre-filtres">
        <div class="groupe-filtres">
            <span class="label-filtre">Régime :</span>
            <button class="btn-filtre filtre-regime" data-regime="vege">🌿 Végé</button>
            <button class="btn-filtre filtre-regime" data-regime="sans_gluten">🚫 Sans gluten</button>
            <button class="btn-filtre filtre-regime" data-regime="mer">🐟 Mer</button>
        </div>
        <div class="groupe-filtres">
            <span class="label-filtre">Trier par :</span>
            <button class="btn-filtre btn-tri" data-tri="prix-asc">Prix ↑</button>
            <button class="btn-filtre btn-tri" data-tri="prix-desc">Prix ↓</button>
            <button class="btn-filtre btn-tri" data-tri="nom-az">Nom A→Z</button>
        </div>
        <button id="btn-tout-effacer" class="btn-filtre" style="background:#c0392b;color:#fff;display:none;">
            ✕ Tout effacer
        </button>
    </div>

    <p id="msg-vide" style="text-align:center;padding:40px;font-size:1.1em;color:#888;display:none;">
        Aucun plat ne correspond à votre recherche.
    </p>

    <!-- Zone des plats — le JS la vide et la remplit à chaque filtre -->
    <div id="zone-plats">

        <?php foreach ($categories as $cle => $titre): ?>
        <?php if (empty($par_categorie[$cle])) continue; ?>
        <h2 class="titre-categorie"><?= htmlspecialchars($titre) ?></h2>
        <section class="grille-produits">
            <?php foreach ($par_categorie[$cle] as $plat): ?>
            <article class="carte-produit"
                     data-prix="<?= $plat['prix'] ?>"
                     data-nom="<?= htmlspecialchars($plat['nom']) ?>"
                     data-categorie="<?= htmlspecialchars($plat['categorie']) ?>">
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
                                <input type="hidden" name="plat_id" value="<?= htmlspecialchars($plat['id']) ?>">
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

    </div>


    <!-- Menu Petit Samouraï — toujours visible, jamais filtré -->
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
                    <input type="hidden" name="plat_id" value="<?= htmlspecialchars($plat['id']) ?>">
                    <input type="hidden" name="quantite" value="1">
                    <button type="submit" class="btn-enfant">Ajouter au panier</button>
                </form>
            <?php else: ?>
                <a href="connexion.php" class="btn-enfant">Se connecter pour commander</a>
            <?php endif; ?>
        </div>
    </section>
    <?php endforeach; ?>


    <!-- Menu Duo Festif -->
    <section class="bandeau-enfant" style="background:linear-gradient(135deg,#E8F4FF 0%,#FFF0E5 100%);">
        <div class="contenu-enfant">
            <span class="emoji-samourai">🎉</span>
            <h2>Menu Duo Festif</h2>
            <p class="prix-enfant">43,40 €</p>
            <p class="desc-enfant">Pour 2 personnes — Plateau Kyoto Mix + Mix Yakitori + 2 Mochis.</p>
            <ul style="list-style:none;padding:0;margin:0 0 .8rem;text-align:left;display:inline-block;font-size:.95rem;color:#444;line-height:1.9;">
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


    <!-- Menu Classique -->
    <section class="bandeau-enfant" style="background:linear-gradient(135deg,#F0FFE8 0%,#FFFDE5 100%);">
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
                            <input type="radio" name="sushi" value="<?= htmlspecialchars($s['id']) ?>" required>
                            <span><?= htmlspecialchars($s['nom']) ?>
                                <em style="color:#888;font-size:.82em;"><?= number_format($s['prix'],2,',','') ?> €</em>
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
                            <input type="radio" name="brochette" value="<?= htmlspecialchars($b['id']) ?>" required>
                            <span><?= htmlspecialchars($b['nom']) ?>
                                <em style="color:#888;font-size:.82em;"><?= number_format($b['prix'],2,',','') ?> €</em>
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
                            <input type="radio" name="dessert" value="<?= htmlspecialchars($d['id']) ?>" required>
                            <span><?= htmlspecialchars($d['nom']) ?>
                                <em style="color:#888;font-size:.82em;"><?= number_format($d['prix'],2,',','') ?> €</em>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p style="color:#27ae60;font-weight:bold;margin:10px 0 14px;font-size:.9rem;">
                    🥤 Ramune offerte avec ce menu !
                </p>
                <button type="submit" class="btn-enfant">Ajouter ce menu au panier</button>
            </form>

            <?php else: ?>
                <a href="connexion.php" class="btn-enfant" style="display:inline-block;margin-top:14px;">
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


<script>
// Filtres actifs en ce moment
var filtreCat    = '';
var filtreRegime = '';
var triActif     = '';
var recherche    = '';

// true si l'utilisateur est connecté en tant que client
var estClient = <?= $est_client ? 'true' : 'false' ?>;

// Noms affichés des catégories
var nomsCategories = {
    'sushis':       'Nos Sushis',
    'brochettes':   'Nos Brochettes',
    'plats_chauds': 'Nos Plats Chauds',
    'desserts':     'Nos Desserts'
};


// Envoie une requête à get_plats.php avec les filtres en cours,
// puis affiche les plats reçus
async function chargerPlats() {
    var url = 'actions/get_plats.php'
            + '?categorie=' + filtreCat
            + '&regime='    + filtreRegime
            + '&recherche=' + encodeURIComponent(recherche);

    try {
        var reponse = await fetch(url);
        if (!reponse.ok) return;

        var data  = await reponse.json();
        var plats = data.plats;

        // Si un tri est actif, on réorganise le tableau avant d'afficher
        if (triActif !== '') {
            plats = trierPlats(plats);
        }

        afficherPlats(plats);

    } catch (e) {
        console.error('Erreur réseau :', e);
    }
}


// Trie le tableau de plats selon triActif
function trierPlats(plats) {
    if (triActif === 'prix-asc') {
        plats.sort(function(a, b) { return a.prix - b.prix; });

    } else if (triActif === 'prix-desc') {
        plats.sort(function(a, b) { return b.prix - a.prix; });

    } else if (triActif === 'nom-az') {
        plats.sort(function(a, b) {
            if (a.nom < b.nom) return -1;
            if (a.nom > b.nom) return 1;
            return 0;
        });
    }
    return plats;
}


// Construit le HTML d'une carte produit
function construireCarte(plat) {
    var prixFormate = plat.prix.toFixed(2).replace('.', ',');

    var bouton = '';
    if (estClient) {
        bouton = '<form action="actions/ajouter_panier.php" method="POST" style="display:inline">'
               + '<input type="hidden" name="plat_id" value="' + plat.id + '">'
               + '<input type="hidden" name="quantite" value="1">'
               + '<button type="submit" class="btn-ajouter">Ajouter</button>'
               + '</form>';
    } else {
        bouton = '<a href="connexion.php" class="btn-ajouter">Commandez</a>';
    }

    return '<article class="carte-produit">'
         + '<div class="image-conteneur"><img src="' + plat.image + '" alt="' + plat.nom + '"></div>'
         + '<div class="infos-produit">'
         + '<div class="entete-carte"><h3>' + plat.nom + '</h3><span class="prix">' + prixFormate + ' €</span></div>'
         + '<p class="description">' + plat.description + '</p>'
         + '<div class="pied-carte"><span class="badge">' + plat.badge + '</span>' + bouton + '</div>'
         + '</div>'
         + '</article>';
}


// Met à jour la zone #zone-plats avec les plats reçus
function afficherPlats(plats) {
    var zone    = document.getElementById('zone-plats');
    var msgVide = document.getElementById('msg-vide');

    if (plats.length === 0) {
        zone.innerHTML = '';
        msgVide.style.display = 'block';
        return;
    }
    msgVide.style.display = 'none';

    // Si un tri est actif : tout dans une seule grille, sans titre de catégorie
    if (triActif !== '') {
        var html = '<section class="grille-produits">';
        for (var i = 0; i < plats.length; i++) {
            html += construireCarte(plats[i]);
        }
        html += '</section>';
        zone.innerHTML = html;
        return;
    }

    // Sans tri : on regroupe par catégorie
    var html       = '';
    var categories = ['sushis', 'brochettes', 'plats_chauds', 'desserts'];

    for (var i = 0; i < categories.length; i++) {
        var cat           = categories[i];
        var platsDeCat    = [];

        for (var j = 0; j < plats.length; j++) {
            if (plats[j].categorie === cat) {
                platsDeCat.push(plats[j]);
            }
        }

        if (platsDeCat.length === 0) continue;

        html += '<h2 class="titre-categorie">' + nomsCategories[cat] + '</h2>';
        html += '<section class="grille-produits">';
        for (var k = 0; k < platsDeCat.length; k++) {
            html += construireCarte(platsDeCat[k]);
        }
        html += '</section>';
    }

    zone.innerHTML = html;
}


// --- Boutons catégorie ---
var boutonsCat = document.querySelectorAll('.filtre-cat');
for (var i = 0; i < boutonsCat.length; i++) {
    boutonsCat[i].addEventListener('click', function() {
        var cat = this.getAttribute('data-cat');

        if (filtreCat === cat) {
            filtreCat = '';
            this.classList.remove('active');
        } else {
            filtreCat = cat;
            for (var j = 0; j < boutonsCat.length; j++) {
                boutonsCat[j].classList.remove('active');
            }
            this.classList.add('active');
        }

        majBoutonEffacer();
        chargerPlats();
    });
}

// --- Boutons régime ---
var boutonsRegime = document.querySelectorAll('.filtre-regime');
for (var i = 0; i < boutonsRegime.length; i++) {
    boutonsRegime[i].addEventListener('click', function() {
        var regime = this.getAttribute('data-regime');

        if (filtreRegime === regime) {
            filtreRegime = '';
            this.classList.remove('active');
        } else {
            filtreRegime = regime;
            for (var j = 0; j < boutonsRegime.length; j++) {
                boutonsRegime[j].classList.remove('active');
            }
            this.classList.add('active');
        }

        majBoutonEffacer();
        chargerPlats();
    });
}

// --- Boutons tri ---
var boutonsTri = document.querySelectorAll('.btn-tri');
for (var i = 0; i < boutonsTri.length; i++) {
    boutonsTri[i].addEventListener('click', function() {
        var tri = this.getAttribute('data-tri');

        if (triActif === tri) {
            triActif = '';
            this.classList.remove('active');
        } else {
            triActif = tri;
            for (var j = 0; j < boutonsTri.length; j++) {
                boutonsTri[j].classList.remove('active');
            }
            this.classList.add('active');
        }

        majBoutonEffacer();
        chargerPlats();
    });
}

// --- Recherche texte ---
document.getElementById('form-recherche').addEventListener('submit', function(e) {
    e.preventDefault();
    recherche = document.getElementById('champ-recherche').value.trim();
    document.getElementById('btn-effacer').style.display = recherche !== '' ? 'inline-block' : 'none';
    majBoutonEffacer();
    chargerPlats();
});

document.getElementById('btn-effacer').addEventListener('click', function() {
    recherche = '';
    document.getElementById('champ-recherche').value = '';
    this.style.display = 'none';
    majBoutonEffacer();
    chargerPlats();
});

// --- Tout effacer ---
document.getElementById('btn-tout-effacer').addEventListener('click', function() {
    filtreCat    = '';
    filtreRegime = '';
    triActif     = '';
    recherche    = '';

    document.getElementById('champ-recherche').value = '';
    document.getElementById('btn-effacer').style.display = 'none';

    var tousLesBoutons = document.querySelectorAll('.filtre-cat, .filtre-regime, .btn-tri');
    for (var i = 0; i < tousLesBoutons.length; i++) {
        tousLesBoutons[i].classList.remove('active');
    }

    this.style.display = 'none';
    chargerPlats();
});

// Affiche ou cache le bouton "tout effacer"
function majBoutonEffacer() {
    var actif = filtreCat !== '' || filtreRegime !== '' || triActif !== '' || recherche !== '';
    document.getElementById('btn-tout-effacer').style.display = actif ? 'inline-block' : 'none';
}
</script>
