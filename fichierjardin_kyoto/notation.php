<?php
require_once 'includes/session.php';
require_once 'includes/utils.php';
$roles_autorises = ['client'];
include 'includes/auth_check.php';

// La commande à noter doit être passée en GET
$commande_id = trim($_GET['commande_id'] ?? '');
if ($commande_id === '') {
    header('Location: profil.php');
    exit;
}

$cmd = trouver_commande($commande_id);
if (!$cmd || ($cmd['client_id'] ?? '') !== $_SESSION['user']['id']) {
    header('Location: profil.php?erreur=commande_introuvable');
    exit;
}
if (($cmd['statut'] ?? '') !== 'livre') {
    header('Location: profil.php?erreur=non_livree');
    exit;
}

// Vérifier pas déjà noté
$notations = lire_json('notations.json');
foreach ($notations as $n) {
    if (($n['commande_id'] ?? '') === $commande_id) {
        header('Location: profil.php?msg=deja_note');
        exit;
    }
}

$erreur = $_GET['erreur'] ?? null;

$page_title = 'Noter ma commande';
$body_class = 'page-deco';
include 'includes/header.php';
?>
<main class="rating-container">

    <form action="actions/noter.php" method="POST" class="boite-formulaire">
        <h2>⭐ Évaluation de la commande #<?= htmlspecialchars($commande_id) ?></h2>

        <?php if ($erreur === 'champs_manquants'): ?>
            <p class="message-erreur">Veuillez noter à la fois la livraison et les produits.</p>
        <?php endif; ?>

        <input type="hidden" name="commande_id" value="<?= htmlspecialchars($commande_id) ?>">

        <fieldset>
            <legend>* La Livraison</legend>
            <p>Comment évaluez-vous la prestation du livreur ?</p>
            <div class="stars">
                <?php foreach ([4=>'⭐⭐⭐⭐ Excellent', 3=>'⭐⭐⭐ Bien', 2=>'⭐⭐ Passable', 1=>'⭐ Décevant'] as $v => $l): ?>
                <label>
                    <input type="radio" name="note_livraison" value="<?= $v ?>" required>
                    <?= $l ?>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset>
            <legend>* Qualité des Produits</legend>
            <p>Les plats reçus étaient-ils à la hauteur de vos attentes ?</p>
            <div class="stars">
                <?php foreach ([5=>'⭐⭐⭐⭐⭐ Délicieux', 4=>'⭐⭐⭐⭐ Très bon', 3=>'⭐⭐⭐ Correct', 2=>'⭐⭐ Décevant', 1=>'⭐ Médiocre'] as $v => $l): ?>
                <label>
                    <input type="radio" name="note_produit" value="<?= $v ?>" required>
                    <?= $l ?>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset>
            <legend>Commentaires (optionnel)</legend>
            <label for="avis_texte">Dites-nous en plus :</label>
            <textarea id="avis_texte" name="avis_texte" rows="4"
                      placeholder="Partagez votre expérience avec nous..."></textarea>
        </fieldset>

        <button type="submit" class="bouton-validation">Envoyer mon avis</button>
    </form>

    <div class="teuchi-bubble">
        <p>Votre avis nous aide à améliorer nos plats ! ⭐</p>
    </div>

</main>
<footer>
    <p><a href="profil.php">← Retour à mes commandes</a></p>
    <p>&copy; 2025-2026 Le Jardin de Kyoto</p>
</footer>
</body>
</html>
