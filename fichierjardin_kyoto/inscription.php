<?php
require_once 'includes/session.php';

if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin')            header('Location: admin.php');
    elseif ($role === 'restaurateur') header('Location: commandes.php');
    elseif ($role === 'livreur')      header('Location: livraison.php');
    else                              header('Location: index.php');
    exit;
}

$page_title = 'Inscription';
$body_class = 'page-deco';
$erreur     = $_GET['erreur']  ?? null;
$message    = $_GET['message'] ?? null;
include 'includes/header.php';
?>
<main class="login-container">
    <form id="form-inscription"
          action="actions/register.php"
          method="POST"
          class="boite-formulaire"
          novalidate>
        <h2>📝 Créer un compte JDK</h2>

        <?php if ($erreur === 'champs_manquants'): ?>
            <p class="message-erreur">Veuillez remplir tous les champs obligatoires.</p>
        <?php elseif ($erreur === 'mdp_court'): ?>
            <p class="message-erreur">Le mot de passe doit contenir au moins 6 caractères (ou les deux mots de passe ne correspondent pas).</p>
        <?php elseif ($erreur === 'email_pris'): ?>
            <p class="message-erreur">Cette adresse e-mail est déjà utilisée.</p>
        <?php elseif ($erreur === 'erreur_serveur'): ?>
            <p class="message-erreur">Une erreur serveur s'est produite. Veuillez réessayer.</p>
        <?php endif; ?>

        <div style="display:flex;gap:12px;">
            <div style="flex:1;">
                <label for="prenom">👤 Prénom :</label>
                <input type="text" id="prenom" name="prenom" data-max="50"
                       placeholder="Votre prénom"
                       value="<?= htmlspecialchars($_GET['prenom'] ?? '') ?>" required>
            </div>
            <div style="flex:1;">
                <label for="nom">👤 Nom :</label>
                <input type="text" id="nom" name="nom" data-max="50"
                       placeholder="Votre nom"
                       value="<?= htmlspecialchars($_GET['nom'] ?? '') ?>" required>
            </div>
        </div>

        <label for="email">📧 E-mail :</label>
        <input type="email" id="email" name="email" data-max="100"
               placeholder="votre.email@exemple.com"
               value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" required>

        <label for="tel">📞 Téléphone :</label>
        <input type="tel" id="tel" name="tel" data-max="20"
               placeholder="06 12 34 56 78"
               value="<?= htmlspecialchars($_GET['tel'] ?? '') ?>" required>

        <label for="adresse">🏠 Adresse de livraison :</label>
        <input type="text" id="adresse" name="adresse" data-max="200"
               placeholder="Numéro, rue, ville, code postal"
               value="<?= htmlspecialchars($_GET['adresse'] ?? '') ?>" required>

        <label for="infos_comp">ℹ️ Informations complémentaires :</label>
        <textarea id="infos_comp" name="infos_comp" data-max="200" rows="2"
                  placeholder="Code interphone, étage, digicode..."><?= htmlspecialchars($_GET['infos_comp'] ?? '') ?></textarea>

        <label for="password">🔑 Mot de passe :</label>
        <input type="password" id="password" name="password" data-max="64"
               placeholder="Au moins 6 caractères" required>

        <label for="password2">🔑 Confirmer le mot de passe :</label>
        <input type="password" id="password2" name="password2" data-max="64"
               placeholder="Répétez votre mot de passe" required>

        <button type="submit" class="bouton-validation">Créer mon compte</button>

        <p class="footer-form">
            Déjà inscrit ? <a href="connexion.php">Se connecter</a>
        </p>
    </form>
    <div class="teuchi-bubble">
        <p>Rejoignez-nous et découvrez mes plats raffinés ! 🍱</p>
    </div>
</main>
<script>
JDK.validerFormulaire('form-inscription', {
    prenom:    { test: v => JDK.validateurs.nonVide(v), msg: "Le prénom est obligatoire" },
    nom:       { test: v => JDK.validateurs.nonVide(v), msg: "Le nom est obligatoire" },
    email:     { test: v => JDK.validateurs.email(v),   msg: "Adresse email invalide" },
    tel:       { test: v => JDK.validateurs.nonVide(v), msg: "Le téléphone est obligatoire" },
    adresse:   { test: v => JDK.validateurs.nonVide(v), msg: "L'adresse est obligatoire" },
    password:  { test: v => v.length >= 6,              msg: "Au moins 6 caractères" },
    password2: {
        test: v => { const p = document.getElementById('password'); return p && v === p.value && v.length >= 6; },
        msg: "Les mots de passe ne correspondent pas"
    }
});
</script>
<footer><p>&copy; 2025-2026 Le Jardin de Kyoto</p></footer>
</body>
</html>
