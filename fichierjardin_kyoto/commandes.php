<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['restaurateur', 'admin'];
include 'includes/auth_check.php';

$commandes   = lire_json('commandes.json');
$plats_json  = lire_json('plats.json');
$users       = lire_json('users.json');
$index_plats = array_column($plats_json, null, 'id');
$index_users = array_column($users, null, 'id');

usort($commandes, function($a, $b) {
    $dateA = $a['date'] ?? '';
    $dateB = $b['date'] ?? '';
    return strcmp($dateB, $dateA);
});

$filtre = $_GET['statut'] ?? 'tous';

$statuts_labels = [
    'tous'          => 'Toutes',
    'en_attente'    => 'À préparer',
    'en_preparation'=> 'En préparation',
    'en_livraison'  => 'En livraison',
    'pret'          => 'Prêt à récupérer',
    'servi'         => 'Servi',
    'livre'         => 'Livrées',
];

if ($filtre === 'tous') {
    $commandes_filtrees = $commandes;
} else {
    $commandes_filtrees = [];
    foreach ($commandes as $c) {
        if (($c['statut'] ?? '') === $filtre) {
            $commandes_filtrees[] = $c;
        }
    }
}

$msg = $_GET['msg'] ?? null;

$page_title = 'Commandes';
$body_class = 'orders-page';
include 'includes/header.php';
?>
<main class="admin">

    <h2 class="admin-subtitle">Gestion des commandes</h2>

    <?php if ($msg === 'statut_ok'): ?>
        <p class="message-succes">Statut de la commande mis à jour.</p>
    <?php endif; ?>

    <!-- FILTRES -->
    <nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
        <?php foreach ($statuts_labels as $cle => $libelle):
            // Compter les commandes pour ce statut
            if ($cle === 'tous') {
                $nb = count($commandes);
            } else {
                $nb = 0;
                foreach ($commandes as $c_tmp) {
                    if (($c_tmp['statut'] ?? '') === $cle) {
                        $nb++;
                    }
                }
            }
        ?>
            <a href="commandes.php?statut=<?= $cle ?>"
               class="btn-admin <?= $filtre === $cle ? 'btn-actif' : '' ?>"
               style="text-decoration:none;">
                <?= $libelle ?><?= $nb > 0 ? " ($nb)" : '' ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (empty($commandes_filtrees)): ?>
        <p style="color:#888;padding:20px 0;">Aucune commande pour ce filtre.</p>
    <?php endif; ?>

    <?php foreach ($commandes_filtrees as $cmd):
        $type   = $cmd['type_livraison'] ?? 'livraison';
        $statut = $cmd['statut'] ?? 'en_attente';
        $client = $index_users[$cmd['client_id'] ?? ''] ?? null;

        // Libellé selon le type de commande
        if ($type === 'sur_place') {
            $type_label = '🍽️ Sur place';
        } elseif ($type === 'emporter') {
            $type_label = '🥡 À emporter';
        } else {
            $type_label = '🚴 Livraison';
        }

        $sc = [
            'en_attente'    => ['🕐 À préparer',       '#e67e22'],
            'en_preparation'=> ['🔥 En préparation',   '#2980b9'],
            'en_livraison'  => ['🚴 En livraison',     '#8e44ad'],
            'pret'          => ['📦 Prêt à récupérer', '#16a085'],
            'servi'         => ['✅ Servi',             '#27ae60'],
            'livre'         => ['✅ Livrée',            '#27ae60'],
            'abandonne'     => ['❌ Abandonnée',        '#c0392b'],
        ];
        [$slabel, $scolor] = $sc[$statut] ?? ['?', '#888'];
        $terminee = in_array($statut, ['servi', 'livre', 'pret', 'abandonne']);
    ?>
    <div class="order-card" style="margin-bottom:16px;padding:16px;border:1px solid #ddd;border-radius:8px;<?= $terminee ? 'opacity:.75;' : '' ?>">

        <!-- EN-TÊTE -->
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h3 style="margin:0;">Commande #<?= htmlspecialchars($cmd['id']) ?></h3>
            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:.9em;font-weight:bold;"><?= $type_label ?></span>
                <span style="background:<?= $scolor ?>;color:#fff;padding:4px 10px;border-radius:12px;font-size:.85em;">
                    <?= $slabel ?>
                </span>
            </div>
        </div>

        <!-- CLIENT -->
        <p style="margin:8px 0;color:#555;">
            <strong>Client :</strong>
            <?= $client ? htmlspecialchars($client['prenom'] . ' ' . $client['nom']) : '(inconnu)' ?>
            <?php if ($type === 'livraison'): ?>
                — <?= htmlspecialchars($cmd['adresse'] ?? '') ?>
                <?php if ($cmd['infos_livraison'] ?? ''): ?>
                    (<?= htmlspecialchars($cmd['infos_livraison']) ?>)
                <?php endif; ?>
            <?php endif; ?>
        </p>

        <!-- ARTICLES -->
        <p style="margin:4px 0;color:#555;">
            <strong>Articles :</strong>
            <?php
            $lignes = [];
            foreach ($cmd['articles'] ?? [] as $art) {
                $nom = nom_article($art, $index_plats);
                $lignes[] = htmlspecialchars($nom) . ' × ' . $art['quantite'];
            }
            echo implode(', ', $lignes) ?: '—';
            ?>
        </p>

        <?php if ($cmd['commentaire'] ?? ''): ?>
        <p style="margin:4px 0;color:#c0392b;font-weight:bold;">
            ⚠️ Note client : <?= htmlspecialchars($cmd['commentaire']) ?>
        </p>
        <?php endif; ?>

        <!-- INFOS -->
        <p style="margin:4px 0;color:#555;font-size:.9em;">
            <strong>Total :</strong> <?= number_format($cmd['total'] ?? 0, 2, ',', ' ') ?> €
            &nbsp;|&nbsp;
            <strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($cmd['date'] ?? 'now')) ?>
            &nbsp;|&nbsp;
            <strong>Paiement :</strong>
            <?= ($cmd['paiement_statut'] ?? '') === 'paye' ? '✅ Payé' : '⏳ En attente' ?>
            <?php if ($type === 'livraison' && ($cmd['heure_souhaitee'] ?? '')): ?>
                &nbsp;|&nbsp;<strong>Heure souhaitée :</strong>
                <?= date('d/m H:i', strtotime($cmd['heure_souhaitee'])) ?>
            <?php endif; ?>
        </p>

        <?php if ($type === 'livraison' && ($cmd['livreur_id'] ?? '')): ?>
        <p style="margin:4px 0;color:#555;font-size:.9em;">
            <strong>Livreur :</strong>
            <?php $lv = $index_users[$cmd['livreur_id']] ?? null;
            echo $lv ? htmlspecialchars($lv['prenom'] . ' ' . $lv['nom']) : '(inconnu)'; ?>
        </p>
        <?php endif; ?>

        <!-- ============================================================
             ACTIONS selon TYPE et STATUT
             ============================================================ -->
        <?php if (in_array($statut, ['en_attente', 'en_preparation'])): ?>
        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">

            <?php if ($type === 'livraison'): ?>
                <!-- LIVRAISON -->
                <?php if ($statut === 'en_attente'): ?>
                    <form action="actions/maj_statut.php" method="POST">
                        <input type="hidden" name="commande_id"    value="<?= htmlspecialchars($cmd['id']) ?>">
                        <input type="hidden" name="nouveau_statut" value="en_preparation">
                        <input type="hidden" name="redirect"       value="commandes.php">
                        <button type="submit" class="btn-admin btn-success">🔥 Commencer la préparation</button>
                    </form>
                <?php endif; ?>
                <?php if ($statut === 'en_preparation'): ?>
                    <form action="actions/maj_statut.php" method="POST"
                          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="hidden" name="commande_id" value="<?= htmlspecialchars($cmd['id']) ?>">
                        <input type="hidden" name="action"      value="attribuer_et_livrer">
                        <input type="hidden" name="redirect"    value="commandes.php">
                        <select name="livreur_id" class="select-admin" required>
                            <option value="">Choisir un livreur…</option>
                            <?php foreach ($users as $u):
                                if ($u['role'] !== 'livreur' || $u['statut'] !== 'actif') continue; ?>
                                <option value="<?= htmlspecialchars($u['id']) ?>"
                                    <?= ($cmd['livreur_id'] ?? '') === $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-admin btn-success"
                                onclick="return confirm('Attribuer le livreur et passer en livraison ?')">
                            🚴 Envoyer en livraison
                        </button>
                    </form>
                <?php endif; ?>

            <?php elseif ($type === 'emporter'): ?>
                <!-- À EMPORTER -->
                <?php if ($statut === 'en_attente'): ?>
                    <form action="actions/maj_statut.php" method="POST">
                        <input type="hidden" name="commande_id"    value="<?= htmlspecialchars($cmd['id']) ?>">
                        <input type="hidden" name="nouveau_statut" value="en_preparation">
                        <input type="hidden" name="redirect"       value="commandes.php">
                        <button type="submit" class="btn-admin btn-success">🔥 Commencer la préparation</button>
                    </form>
                <?php endif; ?>
                <?php if ($statut === 'en_preparation'): ?>
                    <form action="actions/maj_statut.php" method="POST">
                        <input type="hidden" name="commande_id"    value="<?= htmlspecialchars($cmd['id']) ?>">
                        <input type="hidden" name="nouveau_statut" value="pret">
                        <input type="hidden" name="redirect"       value="commandes.php">
                        <button type="submit" class="btn-admin btn-success"
                                onclick="return confirm('Marquer comme prête à récupérer ?')">
                            📦 Prête à récupérer
                        </button>
                    </form>
                <?php endif; ?>

            <?php elseif ($type === 'sur_place'): ?>
                <!-- SUR PLACE -->
                <?php if ($statut === 'en_attente'): ?>
                    <form action="actions/maj_statut.php" method="POST">
                        <input type="hidden" name="commande_id"    value="<?= htmlspecialchars($cmd['id']) ?>">
                        <input type="hidden" name="nouveau_statut" value="en_preparation">
                        <input type="hidden" name="redirect"       value="commandes.php">
                        <button type="submit" class="btn-admin btn-success">🔥 Commencer la préparation</button>
                    </form>
                <?php endif; ?>
                <?php if ($statut === 'en_preparation'): ?>
                    <form action="actions/maj_statut.php" method="POST">
                        <input type="hidden" name="commande_id"    value="<?= htmlspecialchars($cmd['id']) ?>">
                        <input type="hidden" name="nouveau_statut" value="servi">
                        <input type="hidden" name="redirect"       value="commandes.php">
                        <button type="submit" class="btn-admin btn-success"
                                onclick="return confirm('Marquer la commande comme servie ?')">
                            ✅ Commande servie
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Abandonner (tous types) -->
            <form action="actions/maj_statut.php" method="POST">
                <input type="hidden" name="commande_id"    value="<?= htmlspecialchars($cmd['id']) ?>">
                <input type="hidden" name="nouveau_statut" value="abandonne">
                <input type="hidden" name="redirect"       value="commandes.php">
                <button type="submit" class="btn-admin btn-danger"
                        onclick="return confirm('Abandonner cette commande ?')">
                    ❌ Abandonner
                </button>
            </form>

        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

</main>
<?php include 'includes/footer.php'; ?>