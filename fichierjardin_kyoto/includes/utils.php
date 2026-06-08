<?php
// includes/utils.php

function lire_json($fichier) {
    $chemin = __DIR__ . '/../data/' . $fichier;
    if (!file_exists($chemin)) return [];
    $data = json_decode(file_get_contents($chemin), true);
    return is_array($data) ? $data : [];
}

function ecrire_json($fichier, $data) {
    $chemin = __DIR__ . '/../data/' . $fichier;
    return file_put_contents($chemin,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function trouver_utilisateur_par_email($email) {
    foreach (lire_json('users.json') as $user)
        if (isset($user['email']) && strtolower($user['email']) === strtolower($email))
            return $user;
    return null;
}

function trouver_utilisateur_par_id($id) {
    foreach (lire_json('users.json') as $user)
        if (isset($user['id']) && $user['id'] === $id)
            return $user;
    return null;
}

function generer_id() {
    return strtoupper(substr(md5(uniqid()), 0, 10));
}

function commandes_du_client($user_id) {
    $resultat = [];
    foreach (lire_json('commandes.json') as $c)
        if (($c['client_id'] ?? '') === $user_id)
            $resultat[] = $c;
    return $resultat;
}

function trouver_commande($commande_id) {
    foreach (lire_json('commandes.json') as $c)
        if (($c['id'] ?? '') === $commande_id) return $c;
    return null;
}

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

function nom_article($art, $index_plats) {
    if (!empty($art['nom'])) {
        $nom = $art['nom'];
        if (!empty($art['description'])) $nom .= ' (' . $art['description'] . ')';
        return $nom;
    }
    return $index_plats[$art['plat_id']]['nom'] ?? $art['plat_id'];
}
