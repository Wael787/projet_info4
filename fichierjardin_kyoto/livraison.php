<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['livreur', 'admin'];
include 'includes/auth_check.php';

$livreur_id = $_SESSION['user']['id'];
$commandes  = lire_json('commandes.json');
$plats_json = lire_json('plats.json');
$users      = lire_json('users.json');
$index_plats = array_column($plats_json, null, 'id');
$index_users = array_column($users, null, 'id');

// Trouver la commande assignée à ce livreur en cours de livraison
$ma_commande = null;
foreach ($commandes as $cmd) {
    if (($cmd['livreur_id'] ?? '') === $livreur_id
        && ($cmd['statut'] ?? '') === 'en_livraison') {
        $ma_commande = $cmd;
        break;
    }
}

$msg = $_GET['msg'] ?? null;

$page_title = 'Ma livraison';
$body_class = 'delivery';
include 'includes/header.php';
?>
<main>

    <header class="delivery-header">
        <h1>LIVRAISON</h1>
    </header>

    <?php if ($msg === 'livre_ok'): ?>
        <p class="message-succes" style="text-align:center;padding:12px;">Livraison marquée comme effectuée !</p>
    <?php elseif ($msg === 'abandonne_ok'): ?>
        <p class="message-erreur" style="text-align:center;padding:12px;">Commande marquée comme abandonnée.</p>
    <?php endif; ?>

    <?php if ($ma_commande === null): ?>
        <div style="text-align:center;padding:60px 20px;color:#888;">
            <p style="font-size:1.4em;">Aucune livraison en cours.</p>
            <p>Attendez qu'une commande vous soit attribuée par le restaurateur.</p>
        </div>

    <?php else:
        $client = $index_users[$ma_commande['client_id'] ?? ''] ?? [];
        $adresse_brute = $client['adresse'] ?? '';
        $adresse_maps  = urlencode($adresse_brute);
        $lignes = [];
        foreach ($ma_commande['articles'] ?? [] as $art) {
            $nom = nom_article($art, $index_plats);
            $lignes[] = htmlspecialchars($nom) . ' × ' . $art['quantite'];
        }
    ?>
    <section class="delivery-card">

        <h2>Commande #<?= htmlspecialchars($ma_commande['id']) ?></h2>

        <p><strong>Adresse :</strong><br><?= htmlspecialchars($adresse_brute) ?></p>

        <?php if ($client['infos_comp'] ?? ''): ?>
            <p><strong>Infos :</strong> <?= htmlspecialchars($client['infos_comp']) ?></p>
        <?php endif; ?>

        <p><strong>Téléphone client :</strong> <?= htmlspecialchars($client['telephone'] ?? '—') ?></p>

        <p><strong>Articles :</strong><br><?= implode('<br>', $lignes) ?></p>

        <p><strong>Total :</strong> <?= number_format($ma_commande['total'] ?? 0, 2, ',', ' ') ?> €</p>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
            <a href="https://www.google.com/maps/search/?api=1&query=<?= $adresse_maps ?>"
               target="_blank" class="btn primary" rel="noopener">
                📍 Ouvrir dans Google Maps
            </a>
            <a href="https://waze.com/ul?q=<?= $adresse_maps ?>"
               target="_blank" class="btn primary" style="background:#33ccff;" rel="noopener">
                🗺️ Ouvrir dans Waze
            </a>
        </div>

        <!-- ACTIONS LIVREUR -->
        <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
            <form action="actions/maj_statut.php" method="POST">
                <input type="hidden" name="commande_id"   value="<?= htmlspecialchars($ma_commande['id']) ?>">
                <input type="hidden" name="nouveau_statut" value="livre">
                <input type="hidden" name="redirect"       value="livraison.php">
                <button type="submit" class="bouton-validation"
                        onclick="return confirm('Confirmer la livraison ?')">
                    ✅ Livraison effectuée
                </button>
            </form>

            <form action="actions/maj_statut.php" method="POST">
                <input type="hidden" name="commande_id"   value="<?= htmlspecialchars($ma_commande['id']) ?>">
                <input type="hidden" name="nouveau_statut" value="abandonne">
                <input type="hidden" name="redirect"       value="livraison.php">
                <button type="submit" style="background:#c0392b;color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;"
                        onclick="return confirm('Marquer comme abandonnée (adresse introuvable) ?')">
                    ❌ Adresse introuvable
                </button>
            </form>
        </div>

    </section>
    <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>