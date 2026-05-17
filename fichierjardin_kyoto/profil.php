<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['client', 'admin', 'restaurateur', 'livreur'];
include 'includes/auth_check.php';

$user = trouver_utilisateur_par_id($_SESSION['user']['id']) ?? $_SESSION['user'];

$mes_commandes = [];
if ($user['role'] === 'client') {
    $mes_commandes = commandes_du_client($user['id']);
    usort($mes_commandes, function($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
}

$plats_json  = lire_json('plats.json');
$index_plats = [];
foreach ($plats_json as $p) {
    $index_plats[$p['id']] = $p;
}
$notations = lire_json('notations.json');

$badge = '⭐ Membre';
if (($user['statut_special'] ?? '') === 'VIP')     { $badge = '👑 Membre VIP'; }
if (($user['statut_special'] ?? '') === 'Premium') { $badge = '💎 Membre Premium'; }

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
            <h1>Bonjour, <span id="affichage-prenom-nom">
                <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
            </span> !</h1>
            <p class="membre-depuis">Membre depuis <?= date('d/m/Y', strtotime($user['date_inscription'] ?? 'now')) ?></p>
            <p style="font-size:.85em;color:#888;">Rôle : <?= ucfirst($user['role']) ?></p>
        </div>
    </section>

    <div class="grille-profil">
        <div class="colonne-gauche">

            <!-- Bloc informations personnelles -->
            <section class="bloc-profil">
                <div class="bloc-entete">
                    <h2>Informations personnelles</h2>
                    <button id="btn-modifier-infos" class="btn-modifier-profil">✏️ Modifier</button>
                </div>

                <!-- Mode lecture -->
                <div id="mode-lecture-infos" class="liste-infos">
                    <div class="ligne-info">
                        <span class="label-info">Prénom</span>
                        <span class="valeur-info" id="lecture-prenom"><?= htmlspecialchars($user['prenom'] ?? '—') ?></span>
                    </div>
                    <div class="ligne-info">
                        <span class="label-info">Nom</span>
                        <span class="valeur-info" id="lecture-nom"><?= htmlspecialchars($user['nom'] ?? '—') ?></span>
                    </div>
                    <div class="ligne-info">
                        <span class="label-info">Email</span>
                        <span class="valeur-info" id="lecture-email"><?= htmlspecialchars($user['email'] ?? '—') ?></span>
                    </div>
                    <div class="ligne-info">
                        <span class="label-info">Téléphone</span>
                        <span class="valeur-info" id="lecture-telephone"><?= htmlspecialchars($user['telephone'] ?? '—') ?></span>
                    </div>
                </div>

                <!-- Mode édition (caché par défaut) -->
                <div id="mode-edition-infos" style="display:none;padding:12px 0;">
                    <div class="ligne-info" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <label class="label-info" for="edit-prenom">Prénom</label>
                        <input type="text" id="edit-prenom" class="champ-edit" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                    </div>
                    <div class="ligne-info" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <label class="label-info" for="edit-nom">Nom</label>
                        <input type="text" id="edit-nom" class="champ-edit" value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
                    </div>
                    <div class="ligne-info" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <label class="label-info" for="edit-email">Email</label>
                        <input type="email" id="edit-email" class="champ-edit" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="ligne-info" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <label class="label-info" for="edit-telephone">Téléphone</label>
                        <input type="text" id="edit-telephone" class="champ-edit"
                               value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" placeholder="06 XX XX XX XX">
                    </div>
                    <div style="display:flex;gap:10px;margin-top:14px;">
                        <button id="btn-valider-infos" class="bouton-validation" style="flex:1;">✅ Valider</button>
                        <button id="btn-annuler-infos" class="btn-modifier-profil" style="flex:1;background:#888;">✕ Annuler</button>
                    </div>
                    <p id="msg-infos" style="margin-top:10px;font-size:.9em;display:none;"></p>
                </div>
            </section>


            <!-- Bloc adresse de livraison -->
            <section class="bloc-profil">
                <div class="bloc-entete">
                    <h2>Adresse de livraison</h2>
                    <button id="btn-modifier-adresse" class="btn-modifier-profil">✏️ Modifier</button>
                </div>

                <!-- Mode lecture -->
                <div id="mode-lecture-adresse" class="liste-infos">
                    <div class="ligne-info">
                        <span class="label-info">Adresse</span>
                        <span class="valeur-info" id="lecture-adresse"><?= htmlspecialchars($user['adresse'] ?? '—') ?></span>
                    </div>
                    <div class="ligne-info">
                        <span class="label-info">Infos complémentaires</span>
                        <span class="valeur-info" id="lecture-infos-comp"><?= htmlspecialchars($user['infos_comp'] ?? '—') ?></span>
                    </div>
                </div>

                <!-- Mode édition -->
                <div id="mode-edition-adresse" style="display:none;padding:12px 0;">
                    <div class="ligne-info" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <label class="label-info" for="edit-adresse">Adresse</label>
                        <input type="text" id="edit-adresse" class="champ-edit"
                               value="<?= htmlspecialchars($user['adresse'] ?? '') ?>" placeholder="15 rue de la Paix, 75002 Paris">
                    </div>
                    <div class="ligne-info" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <label class="label-info" for="edit-infos-comp">Infos complémentaires</label>
                        <input type="text" id="edit-infos-comp" class="champ-edit"
                               value="<?= htmlspecialchars($user['infos_comp'] ?? '') ?>" placeholder="Bâtiment B, code 1234...">
                    </div>
                    <div style="display:flex;gap:10px;margin-top:14px;">
                        <button id="btn-valider-adresse" class="bouton-validation" style="flex:1;">✅ Valider</button>
                        <button id="btn-annuler-adresse" class="btn-modifier-profil" style="flex:1;background:#888;">✕ Annuler</button>
                    </div>
                    <p id="msg-adresse" style="margin-top:10px;font-size:.9em;display:none;"></p>
                </div>
            </section>

        </div>

        <div class="colonne-droite">

            <?php if ($user['role'] === 'client'): ?>
            <?php
            $points = (int)($user['points_fidelite'] ?? 0);
            $pct    = min(100, (int)round($points / 500 * 100));
            ?>

            <!-- Bloc fidélité -->
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

            <!-- Bloc mes commandes -->
            <section class="bloc-profil">
                <div class="bloc-entete"><h2>📦 Mes commandes</h2></div>
                <div class="liste-commandes">
                    <?php if (empty($mes_commandes)): ?>
                        <p style="color:#888;padding:12px 0;">Aucune commande pour l'instant. <a href="produit.php">Voir le menu →</a></p>
                    <?php else: ?>
                        <?php
                        $statut_map = [
                            'en_attente'     => ['En attente',    'statut-attente'],
                            'en_preparation' => ['En préparation','statut-preparation'],
                            'en_livraison'   => ['En livraison',  'statut-livraison'],
                            'livre'          => ['Livrée ✓',      'statut-livre'],
                            'abandonne'      => ['Abandonnée',    'statut-abandon'],
                        ];
                        foreach ($mes_commandes as $cmd):
                            $s = $statut_map[$cmd['statut'] ?? 'en_attente'] ?? ['En attente','statut-attente'];
                            $deja_note = false;
                            foreach ($notations as $n) {
                                if (($n['commande_id'] ?? '') === $cmd['id']) {
                                    $deja_note = true;
                                    break;
                                }
                            }
                            $cmd_id = htmlspecialchars($cmd['id']);
                        ?>
                        <div class="commande-item" id="commande-<?= $cmd_id ?>">
                            <div class="commande-entete">
                                <span class="commande-date"><?= date('d M Y', strtotime($cmd['date'] ?? 'now')) ?></span>
                                <span class="commande-statut <?= $s[1] ?>"><?= $s[0] ?></span>
                            </div>

                            <p class="commande-detail">
                                <?php
                                $lignes = [];
                                foreach ($cmd['articles'] ?? [] as $art) {
                                    $nom = isset($index_plats[$art['plat_id']]) ? $index_plats[$art['plat_id']]['nom'] : $art['plat_id'];
                                    $lignes[] = htmlspecialchars($nom) . ' × ' . $art['quantite'];
                                }
                                echo implode(' — ', $lignes) ?: '—';
                                ?>
                            </p>

                            <div class="commande-pied">
                                <span class="commande-total">
                                    Total : <?= number_format($cmd['total'] ?? 0, 2, ',', ' ') ?> €
                                </span>
                                <?php if ($cmd['statut'] === 'livre' && !$deja_note): ?>
                                    <a href="notation.php?commande_id=<?= $cmd_id ?>" class="commande-points">⭐ Noter</a>
                                <?php elseif ($cmd['statut'] === 'livre'): ?>
                                    <span class="commande-points" style="color:green;">✓ Noté</span>
                                <?php endif; ?>

                                <?php if (($cmd['statut'] ?? '') === 'en_attente'): ?>
                                    <button class="btn-modif-commande"
                                            onclick="ouvrirModif('<?= $cmd_id ?>')">
                                        ✏️ Modifier
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if (($cmd['statut'] ?? '') === 'en_attente'): ?>
                            <!-- Panneau de modification — caché par défaut -->
                            <div id="panneau-<?= $cmd_id ?>" style="display:none;margin-top:12px;background:#f9f5f0;border-radius:8px;padding:14px;">
                                <p style="font-size:.85em;color:#888;margin:0 0 6px;">
                                    Mets 0 pour supprimer un article.
                                </p>
                                <p style="font-size:.82em;color:#e67e22;margin:0 0 10px;">
                                    ⚠️ Si tu réduis ta commande, aucun remboursement ne sera effectué.
                                </p>

                                <?php foreach ($cmd['articles'] as $art):
                                    $nom_art = isset($index_plats[$art['plat_id']]) ? $index_plats[$art['plat_id']]['nom'] : $art['plat_id'];
                                    $pid     = htmlspecialchars($art['plat_id']);
                                ?>
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                    <span style="flex:1;font-size:.92em;"><?= htmlspecialchars($nom_art) ?></span>
                                    <button class="btn-qte" onclick="changerQte('<?= $cmd_id ?>', '<?= $pid ?>', -1)">−</button>
                                    <span id="qte-<?= $cmd_id ?>-<?= $pid ?>" style="min-width:24px;text-align:center;font-weight:bold;">
                                        <?= (int)$art['quantite'] ?>
                                    </span>
                                    <button class="btn-qte" onclick="changerQte('<?= $cmd_id ?>', '<?= $pid ?>', 1)">+</button>
                                </div>
                                <?php endforeach; ?>

                                <div style="display:flex;gap:8px;margin-top:12px;">
                                    <button class="bouton-validation" style="flex:1;"
                                            onclick="validerModif('<?= $cmd_id ?>')">
                                        ✅ Valider
                                    </button>
                                    <button class="btn-modifier-profil" style="flex:1;background:#888;"
                                            onclick="fermerModif('<?= $cmd_id ?>')">
                                        ✕ Annuler
                                    </button>
                                </div>
                                <p id="msg-cmd-<?= $cmd_id ?>" style="margin-top:8px;font-size:.88em;display:none;"></p>

                                <!-- Zone où le formulaire CYBank apparaît si le client doit payer la différence -->
                                <div id="zone-paiement-<?= $cmd_id ?>" style="display:none;margin-top:12px;
                                     padding:14px;background:#fff8e1;border-radius:8px;border:1px solid #f39c12;">
                                    <p style="margin:0 0 10px;font-size:.9em;color:#e67e22;font-weight:bold;">
                                        💳 Paiement de la différence requis
                                    </p>
                                    <!-- Le formulaire CYBank sera injecté ici par le JS -->
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php endif; ?>

        </div>
    </div>

</main>

<?php include 'includes/footer.php'; ?>


<style>
.btn-modifier-profil {
    background: var(--rouge, #c0392b);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 14px;
    cursor: pointer;
    font-size: .9em;
}
.btn-modifier-profil:hover { opacity: .85; }

.champ-edit {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: .95em;
    box-sizing: border-box;
}
.champ-edit:focus {
    outline: none;
    border-color: var(--rouge, #c0392b);
}

.bloc-entete {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.btn-modif-commande {
    background: #f39c12;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 4px 12px;
    font-size: .85em;
    cursor: pointer;
    margin-left: 8px;
}
.btn-modif-commande:hover { opacity: .85; }

.btn-qte {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-size: 1em;
}
.btn-qte:hover { border-color: var(--rouge, #c0392b); }
</style>


<script>
var userId = '<?= htmlspecialchars($_SESSION['user']['id']) ?>';

// ---- Modifier profil ----

document.getElementById('btn-modifier-infos').addEventListener('click', function() {
    document.getElementById('mode-lecture-infos').style.display = 'none';
    document.getElementById('mode-edition-infos').style.display = 'block';
    this.style.display = 'none';
});

document.getElementById('btn-annuler-infos').addEventListener('click', function() {
    document.getElementById('mode-edition-infos').style.display = 'none';
    document.getElementById('mode-lecture-infos').style.display = 'block';
    document.getElementById('btn-modifier-infos').style.display = 'inline-block';
    document.getElementById('msg-infos').style.display = 'none';
});

document.getElementById('btn-modifier-adresse').addEventListener('click', function() {
    document.getElementById('mode-lecture-adresse').style.display = 'none';
    document.getElementById('mode-edition-adresse').style.display = 'block';
    this.style.display = 'none';
});

document.getElementById('btn-annuler-adresse').addEventListener('click', function() {
    document.getElementById('mode-edition-adresse').style.display = 'none';
    document.getElementById('mode-lecture-adresse').style.display = 'block';
    document.getElementById('btn-modifier-adresse').style.display = 'inline-block';
    document.getElementById('msg-adresse').style.display = 'none';
});


// Envoie les données modifiées au serveur via fetch
async function sauvegarderProfil(donnees, idMsg) {
    donnees.user_id = userId;

    var msg = document.getElementById(idMsg);
    msg.style.display = 'block';
    msg.style.color   = '#888';
    msg.textContent   = 'Enregistrement...';

    try {
        var reponse  = await fetch('actions/maj_profil.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(donnees)
        });
        var resultat = await reponse.json();

        if (resultat.succes) {
            msg.style.color = 'green';
            msg.textContent = '✅ ' + resultat.message;
            mettreAJourAffichage(donnees);
        } else {
            msg.style.color = '#c0392b';
            msg.textContent = '❌ ' + resultat.message;
        }
    } catch (e) {
        msg.style.color = '#c0392b';
        msg.textContent = '❌ Erreur de connexion.';
    }
}

// Met à jour les valeurs affichées en mode lecture
function mettreAJourAffichage(donnees) {
    if (donnees.prenom    !== undefined) { document.getElementById('lecture-prenom').textContent    = donnees.prenom; }
    if (donnees.nom       !== undefined) { document.getElementById('lecture-nom').textContent       = donnees.nom; }
    if (donnees.email     !== undefined) { document.getElementById('lecture-email').textContent     = donnees.email; }
    if (donnees.telephone !== undefined) { document.getElementById('lecture-telephone').textContent = donnees.telephone; }
    if (donnees.adresse   !== undefined) { document.getElementById('lecture-adresse').textContent   = donnees.adresse || '—'; }
    if (donnees.infos_comp !== undefined) { document.getElementById('lecture-infos-comp').textContent = donnees.infos_comp || '—'; }

    var prenom = document.getElementById('lecture-prenom').textContent;
    var nom    = document.getElementById('lecture-nom').textContent;
    document.getElementById('affichage-prenom-nom').textContent = prenom + ' ' + nom;
}

document.getElementById('btn-valider-infos').addEventListener('click', function() {
    var prenom = document.getElementById('edit-prenom').value.trim();
    var nom    = document.getElementById('edit-nom').value.trim();

    if (prenom === '' || nom === '') {
        var msg = document.getElementById('msg-infos');
        msg.style.display = 'block';
        msg.style.color   = '#c0392b';
        msg.textContent   = '❌ Le prénom et le nom sont obligatoires.';
        return;
    }

    sauvegarderProfil({
        prenom:    prenom,
        nom:       nom,
        email:     document.getElementById('edit-email').value.trim(),
        telephone: document.getElementById('edit-telephone').value.trim()
    }, 'msg-infos');
});

document.getElementById('btn-valider-adresse').addEventListener('click', function() {
    sauvegarderProfil({
        adresse:   document.getElementById('edit-adresse').value.trim(),
        infos_comp: document.getElementById('edit-infos-comp').value.trim()
    }, 'msg-adresse');
});


// ---- Modifier commande ----

// On stocke les quantités en cours de modification pour chaque commande
// Ex : { 'ABC123': { 'P012': 2, 'P016': 0 } }
var quantites           = {};
var quantitesOriginales = {};

function ouvrirModif(commandeId) {
    document.getElementById('panneau-' + commandeId).style.display = 'block';

    // On mémorise les quantités actuellement affichées
    // quantitesOriginales sert à restaurer si le client clique Annuler
    quantites[commandeId]           = {};
    quantitesOriginales[commandeId] = {};

    var panneau = document.getElementById('panneau-' + commandeId);
    var spans   = panneau.querySelectorAll('span[id]');

    for (var i = 0; i < spans.length; i++) {
        var id = spans[i].id;
        if (id.indexOf('qte-') === 0) {
            var prefixe = 'qte-' + commandeId + '-';
            var platId  = id.slice(prefixe.length);
            var qte     = parseInt(spans[i].textContent);
            quantites[commandeId][platId]           = qte;
            quantitesOriginales[commandeId][platId] = qte;
        }
    }
}

function fermerModif(commandeId) {
    var panneau = document.getElementById('panneau-' + commandeId);
    panneau.style.display = 'none';

    // Remettre le message à zéro
    document.getElementById('msg-cmd-' + commandeId).style.display = 'none';

    // Cacher la zone de paiement CYBank si elle était affichée
    var zonePaiement = document.getElementById('zone-paiement-' + commandeId);
    if (zonePaiement) {
        zonePaiement.style.display = 'none';
        zonePaiement.innerHTML = '<p style="margin:0 0 10px;font-size:.9em;color:#e67e22;font-weight:bold;">💳 Paiement de la différence requis</p>';
    }

    // Remettre les quantités à leur valeur d'origine
    if (quantitesOriginales[commandeId]) {
        for (var platId in quantitesOriginales[commandeId]) {
            var span = document.getElementById('qte-' + commandeId + '-' + platId);
            if (span) span.textContent = quantitesOriginales[commandeId][platId];
        }
    }
    delete quantitesOriginales[commandeId];

    // Réactiver tous les boutons +/- et Valider
    var btnsQte    = panneau.querySelectorAll('.btn-qte');
    var btnValider = panneau.querySelector('.bouton-validation');
    for (var i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = false; }
    if (btnValider) btnValider.disabled = false;

    delete quantites[commandeId];
}

function changerQte(commandeId, platId, delta) {
    var span = document.getElementById('qte-' + commandeId + '-' + platId);
    if (!span) return;

    var qte  = parseInt(span.textContent) + delta;
    if (qte < 0) qte = 0;

    span.textContent = qte;

    if (!quantites[commandeId]) quantites[commandeId] = {};
    quantites[commandeId][platId] = qte;
}

async function validerModif(commandeId) {
    var msg = document.getElementById('msg-cmd-' + commandeId);
    msg.style.display = 'block';
    msg.style.color   = '#888';
    msg.textContent   = 'Enregistrement...';

    // On désactive uniquement les boutons +/- et Valider pendant l'envoi
    // Le bouton Annuler reste actif
    var panneau     = document.getElementById('panneau-' + commandeId);
    var btnsQte     = panneau.querySelectorAll('.btn-qte');
    var btnValider  = panneau.querySelector('.bouton-validation');
    for (var i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = true; }
    if (btnValider) btnValider.disabled = true;

    try {
        var reponse  = await fetch('actions/maj_commande_client.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                commande_id: commandeId,
                quantites:   quantites[commandeId] || {}
            })
        });
        var resultat = await reponse.json();

        if (!resultat.succes) {
            msg.style.color = '#c0392b';
            msg.textContent = '❌ ' + resultat.message;
            // On réactive les boutons en cas d'erreur
            for (var i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = false; }
            if (btnValider) btnValider.disabled = false;
            return;
        }

        // Cas 1 : le nouveau total est plus élevé → il faut payer la différence
        if (resultat.situation === 'plus_cher') {
            msg.style.color = '#e67e22';
            msg.textContent = '⚠️ ' + resultat.message;

            // On affiche le formulaire CYBank juste en dessous du message
            var zonePaiement = document.getElementById('zone-paiement-' + commandeId);
            if (zonePaiement) {
                zonePaiement.innerHTML = resultat.formulaire;
                zonePaiement.style.display = 'block';
            }
            // Les +/- et Valider restent désactivés : la commande est déjà sauvegardée,
            // le client doit payer avant de pouvoir modifier à nouveau

        // Cas 2 : le total a diminué → on informe, pas de remboursement
        } else if (resultat.situation === 'moins_cher') {
            msg.style.color = 'green';
            msg.textContent = '✅ ' + resultat.message;
            setTimeout(function() { window.location.reload(); }, 1500);

        // Cas 3 : même total → simple confirmation
        } else {
            msg.style.color = 'green';
            msg.textContent = '✅ ' + resultat.message;
            setTimeout(function() { window.location.reload(); }, 1000);
        }

    } catch (e) {
        msg.style.color = '#c0392b';
        msg.textContent = '❌ Erreur de connexion.';
        // On réactive les boutons en cas d'erreur réseau
        for (var i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = false; }
        if (btnValider) btnValider.disabled = false;
    }
}
</script>
