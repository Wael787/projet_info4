<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['admin'];
include 'includes/auth_check.php';

$id   = trim($_GET['id'] ?? '');
$user = $id !== '' ? trouver_utilisateur_par_id($id) : null;

if (!$user) {
    header('Location: admin.php?erreur=utilisateur_introuvable');
    exit;
}

$plats_json     = lire_json('plats.json');
$index_plats    = array_column($plats_json, null, 'id');
$commandes_user = commandes_du_client($user['id']);
usort($commandes_user, function($a, $b) {
    $dateA = $a['date'] ?? '';
    $dateB = $b['date'] ?? '';
    return strcmp($dateB, $dateA);
});

$page_title = 'Profil — ' . $user['prenom'] . ' ' . $user['nom'];
$body_class = 'admin-page';
include 'includes/header.php';
?>
<main class="admin">

    <p><a href="admin.php">← Retour à la liste</a></p>

    <h2 class="admin-subtitle">
        Profil de <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
    </h2>

    <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:32px;">

        <!-- INFOS -->
        <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:20px;flex:1;min-width:260px;">
            <h3 style="margin-top:0;">Informations</h3>
            <table style="width:100%;border-collapse:collapse;">
                <?php
                $champs = [
                    'id'                => 'ID',
                    'email'             => 'Email',
                    'role'              => 'Rôle',
                    'telephone'         => 'Téléphone',
                    'adresse'           => 'Adresse',
                    'infos_comp'        => 'Infos comp.',
                    'statut'            => 'Statut',
                    'statut_special'    => 'Statut spécial',
                    'remise'            => 'Remise',
                    'points_fidelite'   => 'Points fidélité',
                    'date_inscription'  => 'Inscrit le',
                    'derniere_connexion' => 'Dernière connexion',
                ];
                foreach ($champs as $k => $l): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px 8px;color:#888;font-size:.9em;"><?= $l ?></td>
                    <td style="padding:6px 8px;">
                        <?php
                        $v = $user[$k] ?? '—';
                        if ($k === 'statut')
                            echo $v === 'actif'
                                ? '<span style="color:green">✅ Actif</span>'
                                : '<span style="color:red">🔒 Bloqué</span>';
                        elseif ($k === 'remise')
                            echo $v > 0 ? '-' . $v . '%' : '—';
                        else
                            echo htmlspecialchars((string)($v ?? '—'));
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- ACTIONS -->
        <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
        <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:20px;min-width:240px;">
            <h3 style="margin-top:0;">Actions</h3>

            <!-- Bloquer / Débloquer -->
            <div style="margin-bottom:12px;">
                <p style="font-size:.85em;font-weight:bold;color:#555;margin-bottom:6px;">Statut du compte :</p>
                <form action="actions/maj_utiilisateur.php" method="POST">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <input type="hidden" name="action"  value="<?= $user['statut'] === 'actif' ? 'bloquer' : 'debloquer' ?>">
                    <?php if ($user['statut'] === 'actif'): ?>
                        <button type="submit" class="btn-admin btn-danger" style="width:100%;"
                                onclick="return confirm('Bloquer ce compte ?')">🔒 Bloquer le compte</button>
                    <?php else: ?>
                        <button type="submit" class="btn-admin btn-success" style="width:100%;">🔓 Débloquer le compte</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Statut spécial -->
            <div style="margin-bottom:12px;">
                <label style="font-size:.85em;font-weight:bold;color:#555;display:block;margin-bottom:6px;">
                    Statut spécial :
                </label>
                <form action="actions/maj_utiilisateur.php" method="POST">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <input type="hidden" name="action"  value="statut_special">
                    <select name="valeur" class="select-admin" style="width:100%;margin-bottom:6px;">
                        <option value=""        <?= ($user['statut_special']??'')=== ''       ? 'selected':'' ?>>Standard</option>
                        <option value="Premium" <?= ($user['statut_special']??'')==='Premium' ? 'selected':'' ?>>Premium</option>
                        <option value="VIP"     <?= ($user['statut_special']??'')==='VIP'     ? 'selected':'' ?>>VIP</option>
                    </select>
                    <button type="submit" class="btn-admin" style="width:100%;">Modifier le statut</button>
                </form>
            </div>

            <!-- Remise manuelle -->
            <div>
                <label style="font-size:.85em;font-weight:bold;color:#555;display:block;margin-bottom:6px;">
                    Accorder une remise :
                </label>
                <form action="actions/maj_utiilisateur.php" method="POST"
                      style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <input type="hidden" name="action"  value="remise">
                    <input type="number" name="valeur" min="0" max="50"
                           value="<?= (int)($user['remise'] ?? 0) ?>"
                           class="select-admin" style="width:70px;">
                    <span style="font-size:.9em;color:#555;">%</span>
                    <button type="submit" class="btn-admin">Appliquer</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- HISTORIQUE COMMANDES -->
    <h3>Commandes (<?= count($commandes_user) ?>)</h3>

    <?php if (empty($commandes_user)): ?>
        <p style="color:#888;">Aucune commande enregistrée.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $statut_map = [
                    'en_attente'    => '🕐 En attente',
                    'en_preparation'=> '🔥 En préparation',
                    'en_livraison'  => '🚴 En livraison',
                    'pret'          => '📦 Prêt à récupérer',
                    'servi'         => '✅ Servi',
                    'livre'         => '✅ Livrée',
                    'abandonne'     => '❌ Abandonnée',
                ];
                foreach ($commandes_user as $cmd): ?>
                <tr>
                    <td style="font-family:monospace;font-size:.85em;"><?= htmlspecialchars($cmd['id']) ?></td>
                    <td><?= isset($cmd['date']) ? date('d/m/Y H:i', strtotime($cmd['date'])) : '—' ?></td>
                    <td><?= number_format($cmd['total'] ?? 0, 2, ',', ' ') ?> €</td>
                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cmd['type_livraison'] ?? '—'))) ?></td>
                    <td><?= $statut_map[$cmd['statut'] ?? ''] ?? '—' ?></td>
                    <td><?= ($cmd['paiement_statut'] ?? '') === 'paye' ? '✅ Payé' : '⏳ En attente' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>