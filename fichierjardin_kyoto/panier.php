<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
require_once 'includes/cybank.php';
$roles_autorises = ['client'];
include 'includes/auth_check.php';

$plats_json  = lire_json('plats.json');
$index_plats = array_column($plats_json, null, 'id');
$user        = trouver_utilisateur_par_id($_SESSION['user']['id']) ?? $_SESSION['user'];
$panier      = $_SESSION['panier'] ?? [];

// Calcul du total — gère les plats normaux ET les menus
$sous_total = 0.0;
foreach ($panier as $item) {
    if (($item['type'] ?? '') === 'menu') {
        $sous_total += (float)($item['prix'] ?? 0) * (int)$item['quantite'];
    } else {
        $prix = (float)($index_plats[$item['plat_id']]['prix'] ?? 0);
        $sous_total += $prix * (int)$item['quantite'];
    }
}
$remise    = (int)($user['remise'] ?? 0);
$reduction = $remise > 0 ? round($sous_total * $remise / 100, 2) : 0;
$total     = round($sous_total - $reduction, 2);

$msg    = $_GET['msg']    ?? null;
$erreur = $_GET['erreur'] ?? null;

// URL retour CYBank
$protocole  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base       = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$url_retour = $protocole . '://' . $host . $base . '/actions/retour_paiement.php';

$commande_id_temp = $_SESSION['commande_en_cours']['id'] ?? null;

$page_title = 'Mon Panier';
$body_class = 'page-deco';
include 'includes/header.php';
?>
<main style="max-width:800px;margin:30px auto;padding:0 16px;">

    <h1 style="margin-bottom:24px;">🛒 Mon Panier</h1>

    <!-- 🚨 ALERTE PAIEMENT — TRÈS VISIBLE
         On utilise une boîte spéciale (rouge, animée) au lieu du
         simple message-erreur qui passait inaperçu. -->
    <?php if ($erreur === 'paiement_refuse'): ?>
        <div class="alerte-paiement" role="alert">
            <div class="icone">⚠️</div>
            <div class="contenu">
                <h3>Paiement refusé par votre banque</h3>
                <p>
                    Votre commande n'a <strong>pas été validée</strong>.
                    Vérifiez les informations de votre carte (numéro, date, CVV)
                    puis réessayez. Aucune somme n'a été débitée.
                </p>
            </div>
            <a href="#zone-paiement" class="btn-reessayer">↻ Réessayer le paiement</a>
        </div>

    <?php elseif ($erreur === 'controle_invalide'): ?>
        <div class="alerte-paiement" role="alert">
            <div class="icone">🛡️</div>
            <div class="contenu">
                <h3>Erreur de sécurité lors du retour de paiement</h3>
                <p>
                    Les données reçues de la banque n'ont pas pu être vérifiées.
                    Par mesure de sécurité, votre paiement n'a pas été enregistré.
                    Réessayez ou contactez le support.
                </p>
            </div>
            <a href="#zone-paiement" class="btn-reessayer">↻ Réessayer</a>
        </div>

    <?php elseif ($erreur === 'retour_incomplet'): ?>
        <div class="alerte-paiement" role="alert">
            <div class="icone">⚠️</div>
            <div class="contenu">
                <h3>Retour de paiement incomplet</h3>
                <p>Certaines informations sont manquantes. Veuillez réessayer.</p>
            </div>
            <a href="#zone-paiement" class="btn-reessayer">↻ Réessayer</a>
        </div>

    <?php elseif ($erreur === 'commande_vide'): ?>
        <p class="message-erreur">Votre panier est vide.</p>
    <?php endif; ?>

    <?php if ($msg === 'ajout_ok'): ?>
        <p class="message-succes">Article ajouté au panier !</p>
    <?php elseif ($msg === 'commande_creee'): ?>
        <p class="message-succes">Commande enregistrée ! Procédez au paiement ci-dessous.</p>
    <?php endif; ?>

    <?php if (empty($panier)): ?>
        <div style="text-align:center;padding:60px 0;color:#888;">
            <p style="font-size:1.3em;">Votre panier est vide.</p>
            <a href="produit.php" class="bouton-validation"
               style="display:inline-block;margin-top:16px;">Voir le menu →</a>
        </div>

    <?php else: ?>

        <table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
            <thead>
                <tr style="border-bottom:2px solid #ddd;">
                    <th style="text-align:left;padding:8px;">Article</th>
                    <th style="text-align:center;padding:8px;">Qté</th>
                    <th style="text-align:right;padding:8px;">Prix unit.</th>
                    <th style="text-align:right;padding:8px;">Sous-total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($panier as $cle => $item):
                    $est_menu = ($item['type'] ?? '') === 'menu';

                    if ($est_menu) {
                        $nom         = $item['nom'] ?? 'Menu';
                        $pu          = (float)($item['prix'] ?? 0);
                        $description = $item['description'] ?? '';
                    } else {
                        $plat = $index_plats[$item['plat_id']] ?? null;
                        if (!$plat) continue;
                        $nom         = $plat['nom'];
                        $pu          = (float)$plat['prix'];
                        $description = '';
                    }
                    $qte = (int)$item['quantite'];
                ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px 8px;">
                        <strong><?= htmlspecialchars($nom) ?></strong>
                        <?php if ($description): ?>
                            <br><span style="font-size:.82em;color:#888;font-style:italic;">
                                <?= htmlspecialchars($description) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;padding:8px;">
                        <form action="actions/ajouter_panier.php" method="POST" style="display:inline">
                            <input type="hidden" name="plat_id"  value="<?= htmlspecialchars($cle) ?>">
                            <input type="hidden" name="quantite" value="-1">
                            <button type="submit"
                                    style="border:1px solid #ddd;background:#fff;width:24px;height:24px;cursor:pointer;border-radius:4px;">−</button>
                        </form>
                        <span style="margin:0 8px;"><?= $qte ?></span>
                        <form action="actions/ajouter_panier.php" method="POST" style="display:inline">
                            <input type="hidden" name="plat_id"  value="<?= htmlspecialchars($cle) ?>">
                            <input type="hidden" name="quantite" value="1">
                            <button type="submit"
                                    style="border:1px solid #ddd;background:#fff;width:24px;height:24px;cursor:pointer;border-radius:4px;">+</button>
                        </form>
                    </td>
                    <td style="text-align:right;padding:8px;"><?= number_format($pu, 2, ',', '') ?> €</td>
                    <td style="text-align:right;padding:8px;"><?= number_format($pu * $qte, 2, ',', '') ?> €</td>
                    <td style="text-align:center;padding:8px;">
                        <form action="actions/ajouter_panier.php" method="POST">
                            <input type="hidden" name="plat_id" value="<?= htmlspecialchars($cle) ?>">
                            <input type="hidden" name="action"  value="supprimer">
                            <button type="submit"
                                    style="background:none;border:none;cursor:pointer;color:#c0392b;font-size:1.1em;">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;padding:10px 8px;"><strong>Sous-total :</strong></td>
                    <td style="text-align:right;padding:10px 8px;"><?= number_format($sous_total, 2, ',', ' ') ?> €</td>
                    <td></td>
                </tr>
                <?php if ($reduction > 0): ?>
                <tr style="color:green;">
                    <td colspan="3" style="text-align:right;padding:4px 8px;"><strong>Remise (-<?= $remise ?>%) :</strong></td>
                    <td style="text-align:right;padding:4px 8px;">−<?= number_format($reduction, 2, ',', ' ') ?> €</td>
                    <td></td>
                </tr>
                <?php endif; ?>
                <tr style="font-size:1.15em;font-weight:bold;border-top:2px solid #333;">
                    <td colspan="3" style="text-align:right;padding:12px 8px;">Total à payer :</td>
                    <td style="text-align:right;padding:12px 8px;"><?= number_format($total, 2, ',', ' ') ?> €</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!$commande_id_temp): ?>
        <!-- ÉTAPE 1 : OPTIONS DE LIVRAISON -->
        <form action="actions/commander.php" method="POST">
            <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:20px;">
                <h3 style="margin-top:0;">Options de livraison</h3>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:bold;display:block;margin-bottom:8px;">Type de commande :</label>
                    <label style="margin-right:20px;"><input type="radio" name="type_livraison" value="livraison" checked> 🚴 Livraison</label>
                    <label style="margin-right:20px;"><input type="radio" name="type_livraison" value="emporter"> 🥡 À emporter</label>
                    <label><input type="radio" name="type_livraison" value="sur_place"> 🍽️ Sur place</label>
                </div>

                <div id="bloc-adresse">
                    <label style="font-weight:bold;display:block;margin-bottom:6px;">Adresse de livraison :</label>
                    <input type="text" name="adresse_livraison"
                           style="width:100%;padding:8px;box-sizing:border-box;border:1px solid #ddd;border-radius:4px;margin-bottom:8px;"
                           value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                    <textarea name="infos_livraison" rows="2"
                              style="width:100%;padding:8px;box-sizing:border-box;border:1px solid #ddd;border-radius:4px;"
                              placeholder="Code interphone, étage..."><?= htmlspecialchars($user['infos_comp'] ?? '') ?></textarea>
                </div>

                <div style="margin-top:16px;">
                    <label style="font-weight:bold;display:block;margin-bottom:8px;">Heure souhaitée :</label>
                    <label style="margin-right:20px;"><input type="radio" name="heure_type" value="immediat" checked> Dès que possible</label>
                    <label><input type="radio" name="heure_type" value="programmee"> À une heure précise</label>
                    <div id="bloc-heure" style="display:none;margin-top:8px;">
                        <input type="datetime-local" name="heure_souhaitee"
                               style="padding:8px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <label style="font-weight:bold;display:block;margin-bottom:6px;">Commentaires :</label>
                    <textarea name="commentaire" rows="2"
                              style="width:100%;padding:8px;box-sizing:border-box;border:1px solid #ddd;border-radius:4px;"
                              placeholder="Allergies, instructions..."></textarea>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <a href="produit.php" style="color:#888;">← Continuer mes achats</a>
                <button type="submit" class="bouton-validation" style="font-size:1.05em;">
                    Confirmer et procéder au paiement →
                </button>
            </div>
        </form>

        <?php else: ?>
        <!-- ÉTAPE 2 : PAIEMENT CYBANK -->
        <div id="zone-paiement"
             style="background:#f0f8f0;border:2px solid #27ae60;border-radius:8px;padding:24px;text-align:center;">
            <h3 style="color:#27ae60;margin-top:0;">
                ✅ Commande #<?= htmlspecialchars($commande_id_temp) ?> enregistrée
            </h3>
            <p style="color:#555;margin-bottom:20px;">
                Cliquez ci-dessous pour accéder au paiement sécurisé.
            </p>
            <?= cybank_formulaire_paiement($commande_id_temp, $total, $url_retour) ?>
            <p style="font-size:.8em;color:#888;margin-top:12px;">
                Carte de test : 5555 1234 5678 9000 — CVV : 555
            </p>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>

<script>
// 1. Toggle du bloc adresse selon le type de commande
document.querySelectorAll('[name="type_livraison"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('bloc-adresse').style.display =
            r.value === 'livraison' ? 'block' : 'none';
    });
});

// 2. Toggle du bloc heure programmée
document.querySelectorAll('[name="heure_type"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('bloc-heure').style.display =
            r.value === 'programmee' ? 'block' : 'none';
    });
});

// 3. Si on revient sur la page avec une alerte de paiement, on fait défiler
//    automatiquement la page pour que l'utilisateur la voie (priorité visuelle).
(function() {
    const alerte = document.querySelector('.alerte-paiement');
    if (alerte) {
        alerte.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
})();
</script>
