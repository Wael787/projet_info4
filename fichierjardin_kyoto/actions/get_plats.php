<?php
// actions/get_plats.php
// Appelé par fetch depuis produit.php.
// Reçoit les filtres dans l'URL et renvoie les plats en JSON.

require_once '../includes/session.php';
require_once '../includes/utils.php';

header('Content-Type: application/json');

$tous_les_plats = lire_json('plats.json');

$categorie = $_GET['categorie'] ?? '';
$regime    = $_GET['regime']    ?? '';
$recherche = trim($_GET['recherche'] ?? '');

// On sépare les plats enfant des plats normaux.
// Les plats enfant ne sont jamais filtrés ils restent toujours visibles.
$plat_enfant   = [];
$plats_normaux = [];
foreach ($tous_les_plats as $p) {
    if (($p['categorie'] ?? '') === 'menus_enfant') {
        $plat_enfant[] = $p;
    } else {
        $plats_normaux[] = $p;
    }
}

// Filtre par catégorie (sushis, brochettes, plats_chauds, desserts)
if ($categorie !== '') {
    $tmp = [];
    foreach ($plats_normaux as $p) {
        if (($p['categorie'] ?? '') === $categorie) {
            $tmp[] = $p;
        }
    }
    $plats_normaux = $tmp;
}

// Filtre par régime alimentaire
if ($regime !== '') {
    $tmp = [];
    foreach ($plats_normaux as $p) {
        $badge      = strtolower($p['badge'] ?? '');
        $allergenes = $p['allergenes'] ?? [];

        if ($regime === 'vege') {
            // On garde si le badge contient "végé"
            if (strpos($badge, 'vég') !== false) {
                $tmp[] = $p;
            }

        } elseif ($regime === 'sans_gluten') {
            // On garde si "gluten" n'est pas dans les allergènes
            $a_gluten = false;
            foreach ($allergenes as $a) {
                if (strtolower($a) === 'gluten') {
                    $a_gluten = true;
                    break;
                }
            }
            if (!$a_gluten) {
                $tmp[] = $p;
            }

        } elseif ($regime === 'mer') {
            // On cherche "poisson" dans les allergènes
            // (plus fiable que le badge : Nigiri Saumon et Thon ont le badge "Cru", pas "Mer")
            $a_poisson = false;
            foreach ($allergenes as $a) {
                if (strtolower($a) === 'poisson') {
                    $a_poisson = true;
                    break;
                }
            }
            if ($a_poisson) {
                $tmp[] = $p;
            }
        }
    }
    $plats_normaux = $tmp;
}

// Filtre par texte (nom ou description)
if ($recherche !== '') {
    $tmp = [];
    foreach ($plats_normaux as $p) {
        if (stripos($p['nom'] ?? '', $recherche) !== false
         || stripos($p['description'] ?? '', $recherche) !== false) {
            $tmp[] = $p;
        }
    }
    $plats_normaux = $tmp;
}

echo json_encode([
    'plats'       => $plats_normaux,
    'plat_enfant' => $plat_enfant,
]);