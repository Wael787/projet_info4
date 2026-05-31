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
$scripts_page = ['admin.js'];
include 'includes/header.php';
?>
<main class="admin">

    <h2 class="admin-subtitle">Tableau de bord — Administration</h2>

    <!-- STATS rapides -->
    <div class="bloc-stats">
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
        <div class="stat-card">
            <div class="stat-valeur"><?= $val ?></div>
            <div class="stat-label"><?= $label ?></div>
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
            <tr data-user-id="<?= htmlspecialchars($u['id']) ?>"
                class="<?= $u['statut'] === 'bloque' ? 'ligne-bloquee' : '' ?>">
                <td>
                    <a href="profil_admin.php?id=<?= htmlspecialchars($u['id']) ?>">
                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                <td class="cellule-statut">
                    <?php if ($u['statut'] === 'actif'): ?>
                        <span class="badge-statut badge-actif">✅ Actif</span>
                    <?php else: ?>
                        <span class="badge-statut badge-bloque">🔒 Bloqué</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['statut_special'] ?? '—') ?></td>
                <td><?= ($u['remise'] ?? 0) > 0 ? '-' . $u['remise'] . '%' : '—' ?></td>
                <td style="text-align:center;"><?= $nb_cmd_par_user[$u['id']] ?? '—' ?></td>
                <td>
                    <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                    <div style="display:flex;flex-direction:column;gap:6px;min-width:200px;">

                        <!-- Bouton AJAX (data-* portent les infos, listener en bas de page) -->                        <button type="button"
                                class="btn-admin btn-ajax-statut <?= $u['statut'] === 'actif' ? 'btn-danger' : 'btn-success' ?>"
                                data-user-id="<?= htmlspecialchars($u['id']) ?>"
                                data-action="<?= $u['statut'] === 'actif' ? 'bloquer' : 'debloquer' ?>"
                                data-user-nom="<?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>">
                            <?= $u['statut'] === 'actif' ? '🔒 Bloquer' : '🔓 Débloquer' ?>
                        </button>

                        <!-- Statut spécial -->
                        <form action="actions/maj_utilisateur.php" method="POST"
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
                        <form action="actions/maj_utilisateur.php" method="POST"
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

<!-- Script AJAX pour le blocage : un seul listener sur le document
     intercepte les clics sur .btn-ajax-statut (event delegation) -->
<?php include 'includes/footer.php'; ?>