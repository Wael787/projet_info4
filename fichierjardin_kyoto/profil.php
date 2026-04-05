<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['client', 'admin', 'restaurateur', 'livreur'];
include 'includes/auth_check.php';

$user = trouver_utilisateur_par_id($_SESSION['user']['id']) ?? $_SESSION['user'];

$mes_commandes = [];
if ($user['role'] === 'client') {
    $mes_commandes = commandes_du_client($user['id']);
    // Trier les commandes par date décroissante
    usort($mes_commandes, function($a, $b) {
        $dateA = $a['date'] ?? '';
        $dateB = $b['date'] ?? '';
        return strcmp($dateB, $dateA);
    });
}

$plats_json  = lire_json('plats.json');
$index_plats = array_column($plats_json, null, 'id');
$notations   = lire_json('notations.json');

// Détermine le badge selon le statut spécial
$badge = '⭐ Membre';
if ($user['statut_special'] === 'VIP') {
    $badge = '👑 Membre VIP';
} elseif ($user['statut_special'] === 'Premium') {
    $badge = '💎 Membre Premium';
}

$page_title = 'Mon Profil';
$body_class = 'page-profil';
include 'includes/header.php';
?>
<main>

    <section class="section-hero-profil">
        <div class="avatar-conteneur">
            <div class="avatar">🧑</div>
            <div class="badge-fidelite"><?= $badge ?></div>
        </div>
        <div class="hero-infos">
            <h1>Bonjour, <span class="nom-utilisateur"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span> !</h1>
            <p class="membre-depuis">Membre depuis <?= date('d/m/Y', strtotime($user['date_inscription'] ?? 'now')) ?></p>
            <p style="font-size:.85em;color:#888;">Rôle : <?= ucfirst($user['role']) ?></p>
        </div>
    </section>

    <div class="grille-profil">
        <div class="colonne-gauche">

            <section class="bloc-profil">
                <div class="bloc-entete"><h2>Informations personnelles</h2></div>
                <div class="liste-infos">
                    <?php foreach (['prenom'=>'Prénom','nom'=>'Nom','email'=>'Email','telephone'=>'Téléphone'] as $k=>$l): ?>
                    <div class="ligne-info">
                        <span class="label-info"><?= $l ?></span>
                        <span class="valeur-info"><?= htmlspecialchars($user[$k] ?? '—') ?></span>
                        <button class="btn-crayon" onclick="afficherPopupPhase3()">✏️</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bloc-profil">
                <div class="bloc-entete"><h2>Adresse de livraison</h2></div>
                <div class="liste-infos">
                    <div class="ligne-info">
                        <span class="label-info">Adresse</span>
                        <span class="valeur-info"><?= htmlspecialchars($user['adresse'] ?? '—') ?></span>
                        <button class="btn-crayon" onclick="afficherPopupPhase3()">✏️</button>
                    </div>
                    <div class="ligne-info">
                        <span class="label-info">Infos complémentaires</span>
                        <span class="valeur-info"><?= htmlspecialchars($user['infos_comp'] ?? '—') ?></span>
                        <button class="btn-crayon" onclick="afficherPopupPhase3()">✏️</button>
                    </div>
                </div>
            </section>

        </div>

        <div class="colonne-droite">

            <?php if ($user['role'] === 'client'): ?>
            <?php
            $points = (int)($user['points_fidelite'] ?? 0);
            $pct    = min(100, round($points / 500 * 100));
            ?>
            <section class="bloc-profil bloc-fidelite">
                <div class="bloc-entete"><h2>🎁 Mon compte fidélité</h2></div>
                <div class="fidelite-contenu">
                    <div class="points-cercle">
                        <span class="points-nombre"><?= $points ?></span>
                        <span class="points-label">points</span>
                    </div>
                    <div class="fidelite-details">
                        <p class="fidelite-statut">Statut : <strong><?= $badge ?></strong></p>
                        <p class="fidelite-info">Prochain palier : <strong>Membre Platine</strong> à 500 pts</p>
                        <div class="barre-progression-conteneur">
                            <div class="barre-progression">
                                <div class="barre-remplie" style="width:<?= $pct ?>%"></div>
                            </div>
                            <span class="progression-texte"><?= $points ?> / 500 pts</span>
                        </div>
                        <?php if (($user['remise'] ?? 0) > 0): ?>
                            <p style="color:green;margin-top:8px;">✅ Remise active : -<?= $user['remise'] ?>%</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="bloc-profil">
                <div class="bloc-entete"><h2>📦 Mes commandes</h2></div>
                <div class="liste-commandes">
                    <?php if (empty($mes_commandes)): ?>
                        <p style="color:#888;padding:12px 0;">Aucune commande pour l'instant. <a href="produit.php">Voir le menu →</a></p>
                    <?php else: ?>
                        <?php
                        $statut_map = [
                            'en_attente'    => ['En attente',    'statut-attente'],
                            'en_preparation'=> ['En préparation','statut-preparation'],
                            'en_livraison'  => ['En livraison',  'statut-livraison'],
                            'livre'         => ['Livrée ✓',      'statut-livre'],
                            'abandonne'     => ['Abandonnée',    'statut-abandon'],
                        ];
                        foreach ($mes_commandes as $cmd):
                            $s = $statut_map[$cmd['statut'] ?? 'en_attente'] ?? ['En attente','statut-attente'];
                            $deja_note = false;
                            foreach ($notations as $n) { if (($n['commande_id']??'') === $cmd['id']) { $deja_note=true; break; } }
                        ?>
                        <div class="commande-item">
                            <div class="commande-entete">
                                <span class="commande-date"><?= date('d M Y', strtotime($cmd['date']??'now')) ?></span>
                                <span class="commande-statut <?= $s[1] ?>"><?= $s[0] ?></span>
                            </div>
                            <p class="commande-detail">
                                <?php
                                $lignes = [];
                                foreach ($cmd['articles'] ?? [] as $art) {
                                    $nom = nom_article($art, $index_plats);
                                    $lignes[] = htmlspecialchars($nom) . ' × ' . $art['quantite'];
                                }
                                echo implode(' — ', $lignes) ?: '—';
                                ?>
                            </p>
                            <div class="commande-pied">
                                <span class="commande-total">Total : <?= number_format($cmd['total']??0,2,',',' ') ?> €</span>
                                <?php if ($cmd['statut'] === 'livre' && !$deja_note): ?>
                                    <a href="notation.php?commande_id=<?= htmlspecialchars($cmd['id']) ?>" class="commande-points">⭐ Noter</a>
                                <?php elseif ($cmd['statut'] === 'livre'): ?>
                                    <span class="commande-points" style="color:green;">✓ Noté</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </div>

    <div class="popup-overlay" id="popupOverlay" style="display:none">
        <div class="popup">
            <p>✏️ La modification sera disponible en <strong>phase 3</strong> du projet.</p>
            <button class="popup-fermer" onclick="document.getElementById('popupOverlay').style.display='none'">Compris !</button>
        </div>
    </div>

</main>
<?php include 'includes/footer.php'; ?>
<script>
function afficherPopupPhase3() { document.getElementById('popupOverlay').style.display='flex'; }
document.getElementById('popupOverlay').addEventListener('click', function(e) { if(e.target===this) this.style.display='none'; });
</script>