// Page menu : filtres, tris et recherche des produits.

// Filtres actifs en ce moment
let filtreCat    = '';
let filtreRegime = '';
let triActif     = '';
let recherche    = '';

// true si l'utilisateur est connecté en tant que client
// vrai si l'utilisateur connecté est un client
const estClient = document.querySelector('main').dataset.estClient === '1';

// Noms affichés des catégories
let nomsCategories = {
    'sushis':       'Nos Sushis',
    'brochettes':   'Nos Brochettes',
    'plats_chauds': 'Nos Plats Chauds',
    'desserts':     'Nos Desserts'
};


// Envoie une requête à get_plats.php avec les filtres en cours,
// puis affiche les plats reçus
async function chargerPlats() {
    let url = 'actions/get_plats.php'
            + '?categorie=' + filtreCat
            + '&regime='    + filtreRegime
            + '&recherche=' + encodeURIComponent(recherche);

    try {
        let reponse = await fetch(url);
        if (!reponse.ok) return;

        let data  = await reponse.json();
        let plats = data.plats;

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
    let prixFormate = plat.prix.toFixed(2).replace('.', ',');

    let bouton = '';
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
    let zone    = document.getElementById('zone-plats');
    let msgVide = document.getElementById('msg-vide');

    if (plats.length === 0) {
        zone.innerHTML = '';
        msgVide.style.display = 'block';
        return;
    }
    msgVide.style.display = 'none';

    // Si un tri est actif : tout dans une seule grille, sans titre de catégorie
    if (triActif !== '') {
        let html = '<section class="grille-produits">';
        for (let i = 0; i < plats.length; i++) {
            html += construireCarte(plats[i]);
        }
        html += '</section>';
        zone.innerHTML = html;
        return;
    }

    // Sans tri : on regroupe par catégorie
    let html       = '';
    let categories = ['sushis', 'brochettes', 'plats_chauds', 'desserts'];

    for (let i = 0; i < categories.length; i++) {
        let cat           = categories[i];
        let platsDeCat    = [];

        for (let j = 0; j < plats.length; j++) {
            if (plats[j].categorie === cat) {
                platsDeCat.push(plats[j]);
            }
        }

        if (platsDeCat.length === 0) continue;

        html += '<h2 class="titre-categorie">' + nomsCategories[cat] + '</h2>';
        html += '<section class="grille-produits">';
        for (let k = 0; k < platsDeCat.length; k++) {
            html += construireCarte(platsDeCat[k]);
        }
        html += '</section>';
    }

    zone.innerHTML = html;
}


// --- Boutons catégorie ---
let boutonsCat = document.querySelectorAll('.filtre-cat');
for (let i = 0; i < boutonsCat.length; i++) {
    boutonsCat[i].addEventListener('click', function() {
        let cat = this.getAttribute('data-cat');

        if (filtreCat === cat) {
            filtreCat = '';
            this.classList.remove('active');
        } else {
            filtreCat = cat;
            for (let j = 0; j < boutonsCat.length; j++) {
                boutonsCat[j].classList.remove('active');
            }
            this.classList.add('active');
        }

        majBoutonEffacer();
        chargerPlats();
    });
}

// --- Boutons régime ---
let boutonsRegime = document.querySelectorAll('.filtre-regime');
for (let i = 0; i < boutonsRegime.length; i++) {
    boutonsRegime[i].addEventListener('click', function() {
        let regime = this.getAttribute('data-regime');

        if (filtreRegime === regime) {
            filtreRegime = '';
            this.classList.remove('active');
        } else {
            filtreRegime = regime;
            for (let j = 0; j < boutonsRegime.length; j++) {
                boutonsRegime[j].classList.remove('active');
            }
            this.classList.add('active');
        }

        majBoutonEffacer();
        chargerPlats();
    });
}

// --- Boutons tri ---
let boutonsTri = document.querySelectorAll('.btn-tri');
for (let i = 0; i < boutonsTri.length; i++) {
    boutonsTri[i].addEventListener('click', function() {
        let tri = this.getAttribute('data-tri');

        if (triActif === tri) {
            triActif = '';
            this.classList.remove('active');
        } else {
            triActif = tri;
            for (let j = 0; j < boutonsTri.length; j++) {
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

    let tousLesBoutons = document.querySelectorAll('.filtre-cat, .filtre-regime, .btn-tri');
    for (let i = 0; i < tousLesBoutons.length; i++) {
        tousLesBoutons[i].classList.remove('active');
    }

    this.style.display = 'none';
    chargerPlats();
});

// Affiche ou cache le bouton "tout effacer"
function majBoutonEffacer() {
    let actif = filtreCat !== '' || filtreRegime !== '' || triActif !== '' || recherche !== '';
    document.getElementById('btn-tout-effacer').style.display = actif ? 'inline-block' : 'none';
}
