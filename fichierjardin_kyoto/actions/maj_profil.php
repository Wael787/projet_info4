<?php
// actions/maj_profil.php
// Reçoit les nouvelles infos du profil en JSON (envoyées par fetch).
// Met à jour users.json et répond en JSON.

require_once '../includes/session.php';
require_once '../includes/utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['succes' => false, 'message' => 'Vous n\'êtes pas connecté.']);
    exit;
}

// Lire les données envoyées par fetch (format JSON)
$corps   = file_get_contents('php://input');
$donnees = json_decode($corps, true);

if (!$donnees) {
    echo json_encode(['succes' => false, 'message' => 'Données invalides.']);
    exit;
}

// Vérifier que l'ID envoyé correspond bien à l'utilisateur connecté
if (($donnees['user_id'] ?? '') !== $_SESSION['user']['id']) {
    echo json_encode(['succes' => false, 'message' => 'Action non autorisée.']);
    exit;
}

$users  = lire_json('users.json');
$trouve = false;

foreach ($users as &$user) {
    if ($user['id'] !== $_SESSION['user']['id']) continue;

    $trouve = true;

    // On ne met à jour que les champs autorisés (pas le mot de passe, pas le rôle)
    $champs = ['prenom', 'nom', 'email', 'telephone', 'adresse', 'infos_comp'];
    foreach ($champs as $champ) {
        if (isset($donnees[$champ])) {
            $user[$champ] = trim($donnees[$champ]);
        }
    }

    // Mettre aussi à jour la session pour que le header reflète les changements
    $_SESSION['user']['prenom'] = $user['prenom'];
    $_SESSION['user']['nom']    = $user['nom'];
    $_SESSION['user']['email']  = $user['email'];

    break;
}
unset($user);

if (!$trouve) {
    echo json_encode(['succes' => false, 'message' => 'Utilisateur introuvable.']);
    exit;
}

if (ecrire_json('users.json', $users)) {
    echo json_encode(['succes' => true, 'message' => 'Profil mis à jour !']);
} else {
    echo json_encode(['succes' => false, 'message' => 'Erreur lors de la sauvegarde.']);
}
