<?php
// includes/utils.php
// Fonctions utilitaires partagées par toutes les pages

function lire_json($fichier) {
    $chemin = __DIR__ . '/../data/' . $fichier;
    if (!file_exists($chemin)) {
        return [];
    }
    $contenu = file_get_contents($chemin);
    $data = json_decode($contenu, true);
    return is_array($data) ? $data : [];
}

function ecrire_json($fichier, $data) {
    $chemin = __DIR__ . '/../data/' . $fichier;
    $contenu = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($chemin, $contenu) !== false;
}

function trouver_utilisateur_par_email($email) {
    $users = lire_json('users.json');
    foreach ($users as $user) {
        if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
            return $user;
        }
    }
    return null;
}

function trouver_utilisateur_par_id($id) {
    $users = lire_json('users.json');
    foreach ($users as $user) {
        if (isset($user['id']) && $user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

// uniqid() génère une valeur basée sur l'heure, md5() la transforme en chaîne fixe,
// on prend les 10 premiers caractères pour avoir un ID court
function generer_id() {
    return strtoupper(substr(md5(uniqid()), 0, 10));
}

function commandes_du_client($user_id) {
    $commandes = lire_json('commandes.json');
    $resultat = [];
    foreach ($commandes as $c) {
        if (($c['client_id'] ?? '') === $user_id) {
            $resultat[] = $c;
        }
    }
    return $resultat;
}

function trouver_commande($commande_id) {
    $commandes = lire_json('commandes.json');
    foreach ($commandes as $c) {
        if (($c['id'] ?? '') === $commande_id) return $c;
    }
    return null;
}

// Le & devant $c est important car sans lui, on modifie une copie et rien ne se sauvegardent
function maj_commande($commande_id, $champ, $valeur) {
    $commandes = lire_json('commandes.json');
    foreach ($commandes as &$c) {
        if (($c['id'] ?? '') === $commande_id) {
            $c[$champ] = $valeur;
            return ecrire_json('commandes.json', $commandes);
        }
    }
    return false;
}

// Les article de type "menu" ont leur nom stocké directement dans la commandes.
// Les plats normaux n'ont qu'un plat_id, il faut chercher leur nom dans $index_plats.
function nom_article($art, $index_plats) {
    if (!empty($art['nom'])) {
        $nom = $art['nom'];
        if (!empty($art['description'])) {
            $nom .= ' (' . $art['description'] . ')';
        }
        return $nom;
    }
    return $index_plats[$art['plat_id']]['nom'] ?? $art['plat_id'];
}
