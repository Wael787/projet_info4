<?php
// actions/check_session.php
// Appelé en AJAX toutes les 10s par le navigateur.
// Si l'utilisateur est toujours connecté (et pas bloqué) -> répond {actif: true}
// Si l'utilisateur a été déconnecté (session expirée ou bloqué) -> répond {actif: false}
//
// Note : session.php inclus en haut s'occupe déjà de détruire la session
// si l'utilisateur est bloqué. Donc ici on a juste à regarder si _SESSION['user']
// existe encore.

require_once '../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user'])) {
    echo json_encode(['actif' => true]);
} else {
    echo json_encode(['actif' => false]);
}
