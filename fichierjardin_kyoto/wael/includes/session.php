<?php
// includes/session.php
// À inclure tout en haut de chaque page (avant tout echo).
// Démarre la session + vérifie si l'utilisateur connecté n'a pas été
// bloqué par l'admin entre temps.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detruit la session courante et redirige vers connexion
function deconnecter_et_rediriger($code_erreur) {
    $_SESSION = [];

    // on vire aussi le cookie de session côté client
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $params['path'], $params['domain'],
                  $params['secure'], $params['httponly']);
    }

    session_destroy();

    // si on est dans actions/, il faut ajouter ../ devant le redirect
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $prefix = (strpos($script, '/actions/') !== false) ? '../' : '';

    // éviter une boucle si on est déjà sur connexion.php
    if (basename($script) !== 'connexion.php') {
        header('Location: ' . $prefix . 'connexion.php?erreur=' . urlencode($code_erreur));
        exit;
    }
}

// Vérif temps réel : si l'admin a bloqué l'utilisateur pendant qu'il était
// connecté, on le déconnecte direct.
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    require_once __DIR__ . '/utils.php';

    $user_id_session = $_SESSION['user']['id'];
    $users = lire_json('users.json');

    $user_trouve = null;
    foreach ($users as $u) {
        if ($u['id'] === $user_id_session) {
            $user_trouve = $u;
            break;
        }
    }

    // utilisateur supprimé entre temps ?
    if ($user_trouve === null) {
        deconnecter_et_rediriger('session_invalide');
    }

    // bloqué ?
    if (($user_trouve['statut'] ?? 'actif') === 'bloque') {
        deconnecter_et_rediriger('session_bloquee');
    }

    // sinon on rafraichit la session (statut spécial, remise, etc. ont pu changer)
    $u_propre = $user_trouve;
    unset($u_propre['password']);
    $_SESSION['user'] = $u_propre;
}
