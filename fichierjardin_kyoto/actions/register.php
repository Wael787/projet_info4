<?php
// actions/register.php
require_once '../includes/session.php';
require_once '../includes/utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../inscription.php'); exit;
}

$nom      = trim($_POST['nom']        ?? '');
$prenom   = trim($_POST['prenom']     ?? '');
$tel      = trim($_POST['tel']        ?? '');
$adresse  = trim($_POST['adresse']    ?? '');
$infos    = trim($_POST['infos_comp'] ?? '');
$email    = trim($_POST['email']      ?? '');
$password = $_POST['password']  ?? '';
$password2= $_POST['password2'] ?? '';

if ($nom===''||$prenom===''||$tel===''||$adresse===''||$email===''||$password==='') {
    header('Location: ../inscription.php?erreur=champs_manquants'); exit;
}
if (strlen($password) < 6) {
    header('Location: ../inscription.php?erreur=mdp_court'); exit;
}
if ($password !== $password2) {
    header('Location: ../inscription.php?erreur=mdp_court'); exit;
}
if (trouver_utilisateur_par_email($email) !== null) {
    header('Location: ../inscription.php?erreur=email_pris&email='.urlencode($email)); exit;
}

$nouveau = [
    'id'                => 'U'.strtoupper(generer_id()),
    'nom'               => $nom,
    'prenom'            => $prenom,
    'email'             => strtolower($email),
    'password'          => password_hash($password, PASSWORD_DEFAULT),
    'role'              => 'client',
    'telephone'         => $tel,
    'adresse'           => $adresse,
    'infos_comp'        => $infos,
    'statut'            => 'actif',
    'statut_special'    => null,
    'remise'            => 0,
    'points_fidelite'   => 0,
    'date_inscription'  => date('Y-m-d'),
    'derniere_connexion'=> date('Y-m-d'),
];

$users   = lire_json('users.json');
$users[] = $nouveau;
if (!ecrire_json('users.json', $users)) {
    header('Location: ../inscription.php?erreur=erreur_serveur'); exit;
}

header('Location: ../connexion.php?message=inscription_ok&email='.urlencode($email));
exit;
