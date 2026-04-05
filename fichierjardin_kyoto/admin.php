<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['admin'];
include 'includes/auth_check.php';

$users     = lire_json('users.json');
$commandes = lire_json('commandes.json');

$nb_cmd_par_user = [];
foreach ($commandes as $cmd) {
    $uid = $cmd['client_id'] ?? '';
    $nb_cmd_par_user[$uid] = ($nb_cmd_par_user[$uid] ?? 0) + 1;
}

$page_title = 'Administration';
$body_class = 'admin-page';
include 'includes/header.php';
?>
<main class="admin">

    <h2 class="admin-subtitle">Tableau de bord — Administration</h2>

    <!-- STATS RAPIDES -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
        <?php
        // Compter chaque rôle manuellement
        $nb_clients = 0;
        $nb_admins  = 0;
        $nb_restos  = 0;
        $nb_livreurs = 0;
        foreach ($users as $u) {
            if ($u['role'] === 'client')       $nb_clients++;
            if ($u['role'] === 'admin')        $nb_admins++;
            if ($u['role'] === 'restaurateur') $nb_restos++;
            if ($u['role'] === 'livreur')      $nb_livreurs++;
        }
        $stats = [
            'Clients'       => $nb_clients,
            'Admins'        => $nb_admins,
            'Restaurateurs' => $nb_restos,
            'Livreurs'      => $nb_livreurs,
            'Commandes'     => count($commandes),
        ];
        foreach ($stats as $label => $val): ?>
        <div style="background:#f5f5f5;border-radius:8px;padding:16px 24px;min-width:100px;text-align:center;">
            <div style="font-size:1.8em;font-weight:bold;"><?= $val ?></div>
            <div style="font-size:.85em;color:#666;"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <h3>Liste des utilisateurs</h3>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Statut spécial</th>
                <th>Remise</th>
                <th>Commandes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr style="<?= ($u['statut'] === 'bloque') ? 'background:#fdecea;' : '' ?>">
                <td>
                    <a href="profil_admin.php?id=<?= htmlspecialchars($u['id']) ?>">
                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                <td>
                    <span style="color:<?= $u['statut']==='actif' ? 'green' : 'red' ?>">
                        <?= $u['statut'] === 'actif' ? '✅ Actif' : '🔒 Bloqué' ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($u['statut_special'] ?? '—') ?></td>
                <td><?= ($u['remise'] ?? 0) > 0 ? '-' . $u['remise'] . '%' : '—' ?></td>
                <td style="text-align:center;"><?= $nb_cmd_par_user[$u['id']] ?? '—' ?></td>
                <td>
                    <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                    <div style="display:flex;flex-direction:column;gap:6px;min-width:200px;">

                        <!-- Bloquer / Débloquer -->
                        <form action="actions/maj_utiilisateur.php" method="POST">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                            <input type="hidden" name="action"  value="<?= $u['statut'] === 'actif' ? 'bloquer' : 'debloquer' ?>">
                            <?php if ($u['statut'] === 'actif'): ?>
                                <button type="submit" class="btn-admin btn-danger"
                                        onclick="return confirm('Bloquer ce compte ?')">🔒 Bloquer</button>
                            <?php else: ?>
                                <button type="submit" class="btn-admin btn-success">🔓 Débloquer</button>
                            <?php endif; ?>
                        </form>

                        <!-- Statut spécial -->
                        <form action="actions/maj_utiilisateur.php" method="POST"
                              style="display:flex;gap:4px;align-items:center;">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                            <input type="hidden" name="action"  value="statut_special">
                            <select name="valeur" class="select-admin" style="flex:1;">
                                <option value=""        <?= ($u['statut_special']??'')=== ''       ? 'selected':'' ?>>Standard</option>
                                <option value="Premium" <?= ($u['statut_special']??'')==='Premium' ? 'selected':'' ?>>Premium</option>
                                <option value="VIP"     <?= ($u['statut_special']??'')==='VIP'     ? 'selected':'' ?>>VIP</option>
                            </select>
                            <button type="submit" class="btn-admin">Statut</button>
                        </form>

                        <!-- Remise manuelle (écrase la remise automatique liée au statut) -->
                        <form action="actions/maj_utiilisateur.php" method="POST"
                              style="display:flex;gap:4px;align-items:center;">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                            <input type="hidden" name="action"  value="remise">
                            <input type="number" name="valeur" min="0" max="50"
                                   value="<?= (int)($u['remise'] ?? 0) ?>"
                                   class="select-admin" style="width:60px;">
                            <span style="font-size:.85em;color:#888;">%</span>
                            <button type="submit" class="btn-admin">Remise</button>
                        </form>

                    </div>
                    <?php else: ?>
                        <em style="color:#aaa;font-size:.85em;">Votre compte</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</main>
<?php include 'includes/footer.php'; ?>
