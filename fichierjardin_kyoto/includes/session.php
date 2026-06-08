<?php
// includes/session.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function deconnecter_et_rediriger($code_erreur) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $params['path'], $params['domain'],
                  $params['secure'], $params['httponly']);
    }
    session_destroy();

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Si on est dans actions/, remonter d'un niveau
    $prefix = (strpos($script, '/actions/') !== false) ? '../' : '';

    if (basename($script) !== 'connexion.php') {
        header('Location: ' . $prefix . 'connexion.php?erreur=' . urlencode($code_erreur));
        exit;
    }
}

if (isset($_SESSION['user']['id'])) {
    require_once __DIR__ . '/utils.php';

    $user_id_session = $_SESSION['user']['id'];
    $user_trouve = null;
    foreach (lire_json('users.json') as $u) {
        if ($u['id'] === $user_id_session) { $user_trouve = $u; break; }
    }

    if ($user_trouve === null) {
        deconnecter_et_rediriger('session_invalide');
    }
    if (($user_trouve['statut'] ?? 'actif') === 'bloque') {
        deconnecter_et_rediriger('session_bloquee');
    }

    $u_propre = $user_trouve;
    unset($u_propre['password']);
    $_SESSION['user'] = $u_propre;
}
