<?php
// actions/login.php
require_once '../includes/session.php';
require_once '../includes/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../connexion.php'); exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ../connexion.php?erreur=identifiants'); exit;
}

$user = trouver_utilisateur_par_email($email);
if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../connexion.php?erreur=identifiants'); exit;
}
if (($user['statut'] ?? 'actif') === 'bloque') {
    header('Location: ../connexion.php?erreur=bloque'); exit;
}

$tous_users = lire_json('users.json');
foreach ($tous_users as &$u) {
    if ($u['id'] === $user['id']) { $u['derniere_connexion'] = date('Y-m-d'); break; }
}
unset($u);
ecrire_json('users.json', $tous_users);

session_regenerate_id(true);
unset($user['password']);
$_SESSION['user'] = $user;

switch ($user['role']) {
    case 'admin':        header('Location: ../admin.php');     break;
    case 'restaurateur': header('Location: ../commandes.php'); break;
    case 'livreur':      header('Location: ../livraison.php'); break;
    default:             header('Location: ../index.php');     break;
}
exit;
