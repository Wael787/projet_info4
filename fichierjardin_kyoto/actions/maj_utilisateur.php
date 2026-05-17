<?php
// actions/maj_utilisateur.php — admin uniquement
// Gère le blocage/déblocage et le changement de statut spécial.
// Répond en JSON si appelé en AJAX, sinon redirige vers admin.php.

require_once '../includes/session.php';
require_once '../includes/utils.php';

// On detecte AJAX via le header Accept ou X-Requested-With
$est_ajax =
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    ||
    (isset($_SERVER['HTTP_ACCEPT']) &&
     strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

function repondre($est_ajax, $ok, $cle_msg, $message_humain = '', $extra = []) {
    if ($est_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        if (!$ok) http_response_code(400);
        $payload = array_merge([
            'ok'      => $ok,
            'code'    => $cle_msg,
            'message' => $message_humain,
        ], $extra);
        echo json_encode($payload);
    } else {
        $param = $ok ? 'msg' : 'erreur';
        header('Location: ../admin.php?' . $param . '=' . urlencode($cle_msg));
    }
    exit;
}

// ===== controles d'accès =====
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    repondre($est_ajax, false, 'non_autorise', 'Accès réservé aux administrateurs.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondre($est_ajax, false, 'methode_invalide', 'Méthode HTTP non autorisée.');
}

$user_id = trim($_POST['user_id'] ?? '');
$action  = trim($_POST['action']  ?? '');

if ($user_id === '' || $action === '') {
    repondre($est_ajax, false, 'donnees_manquantes', 'Données manquantes.');
}

// un admin peut pas se bloquer lui meme
if ($user_id === $_SESSION['user']['id'] && $action === 'bloquer') {
    repondre($est_ajax, false, 'auto_blocage', 'Vous ne pouvez pas vous bloquer vous-même.');
}

// ===== modification =====
$users  = lire_json('users.json');
$trouve = false;

foreach ($users as &$u) {
    if ($u['id'] !== $user_id) continue;
    $trouve = true;

    switch ($action) {
        case 'bloquer':
            $u['statut'] = 'bloque';
            break;

        case 'debloquer':
            $u['statut'] = 'actif';
            break;

        case 'statut_special':
            $valeur = trim($_POST['valeur'] ?? '');
            $valeurs_autorisees = ['', 'Premium', 'VIP'];
            if (in_array($valeur, $valeurs_autorisees, true)) {
                $u['statut_special'] = $valeur === '' ? null : $valeur;
                // remise auto suivant le statut
                if ($valeur === 'VIP')          $u['remise'] = 15;
                elseif ($valeur === 'Premium')  $u['remise'] = 5;
                else                            $u['remise'] = 0;
            }
            break;

        case 'remise':
            $valeur = (int)($_POST['valeur'] ?? 0);
            if ($valeur >= 0 && $valeur <= 50) {
                $u['remise'] = $valeur;
            }
            break;

        default:
            repondre($est_ajax, false, 'action_inconnue', 'Action inconnue.');
    }
    break;
}
unset($u);

if (!$trouve) {
    repondre($est_ajax, false, 'utilisateur_introuvable', 'Utilisateur introuvable.');
}

ecrire_json('users.json', $users);

// on renvoie le user à jour pour que le JS puisse mettre à jour la ligne
$user_a_jour = null;
foreach ($users as $u) {
    if ($u['id'] === $user_id) { $user_a_jour = $u; break; }
}

repondre($est_ajax, true, 'statut_ok', 'Mise à jour effectuée.', [
    'user' => [
        'id'             => $user_a_jour['id'],
        'statut'         => $user_a_jour['statut']         ?? 'actif',
        'statut_special' => $user_a_jour['statut_special'] ?? null,
        'remise'         => $user_a_jour['remise']         ?? 0,
    ],
]);
