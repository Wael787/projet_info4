<?php
// actions/maj_utilisateur.php — réservé à l'admin
// Actions : bloquer, debloquer, statut_special, remise
    header('Location: ../connexion.php?erreur=non_connecte');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin.php');
    exit;
}

$user_id = trim($_POST['user_id'] ?? '');
$action  = trim($_POST['action']  ?? '');

if ($user_id === '' || $action === '') {
    header('Location: ../admin.php?erreur=donnees_manquantes');
    exit;
}

// Un admin ne peut pas se bloquer lui-même
if ($user_id === $_SESSION['user']['id'] && $action === 'bloquer') {
    header('Location: ../admin.php?erreur=auto_blocage');
    exit;
}

$users   = lire_json('users.json');
$trouve  = false;

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
                // Mise à jour de la remise selon le statut
                // Remise selon le statut spécial
                if ($valeur === 'VIP') {
                    $u['remise'] = 15;
                } elseif ($valeur === 'Premium') {
                    $u['remise'] = 5;
                } else {
                    $u['remise'] = 0;
                }
            }
            break;
    }
    break;
}
unset($u);

if (!$trouve) {
    header('Location: ../admin.php?erreur=utilisateur_introuvable');
    exit;
}

ecrire_json('users.json', $users);
header('Location: ../admin.php?msg=statut_ok');
exit;