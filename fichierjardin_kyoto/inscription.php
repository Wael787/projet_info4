<?php
require_once 'includes/session.php';

// Déjà connecté → accueil
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Inscription';
$body_class = 'page-deco';

$erreur = $_GET['erreur'] ?? null;

include 'includes/header.php';
?>

<main>
    <form class="boite-formulaire" action="actions/register.php" method="POST">
        <h2>✨ Créer un compte JDK</h2>

        <?php if ($erreur === 'email_pris'): ?>
            <p class="message-erreur">Cet email est déjà utilisé. <a href="connexion.php">Se connecter</a></p>
        <?php elseif ($erreur === 'champs_manquants'): ?>
            <p class="message-erreur">Veuillez remplir tous les champs obligatoires (*).</p>
        <?php elseif ($erreur === 'mdp_court'): ?>
            <p class="message-erreur">Le mot de passe doit contenir au moins 6 caractères.</p>
        <?php endif; ?>

        <fieldset>
            <legend>Informations Personnelles</legend>

            <div class="form-group">
                <label for="nom">* Nom :</label>
                <input type="text" id="nom" name="nom"
                       placeholder="Yamamoto" required
                       value="<?= htmlspecialchars($_GET['nom'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="prenom">* Prénom :</label>
                <input type="text" id="prenom" name="prenom"
                       placeholder="Hiroshi" required
                       value="<?= htmlspecialchars($_GET['prenom'] ?? '') ?>">
            </div>

            <label for="tel">* Numéro de téléphone :</label>
            <input type="tel" id="tel" name="tel"
                   placeholder="06 12 34 56 78" required
                   value="<?= htmlspecialchars($_GET['tel'] ?? '') ?>">
        </fieldset>

        <fieldset>
            <legend>Coordonnées de Livraison</legend>

            <label for="adresse">* Adresse complète :</label>
            <input type="text" id="adresse" name="adresse"
                   placeholder="15 rue de la Paix, 75002 Paris" required
                   value="<?= htmlspecialchars($_GET['adresse'] ?? '') ?>">

            <label for="infos_comp">Informations complémentaires (code, étage...) :</label>
            <textarea id="infos_comp" name="infos_comp" rows="3"
                      placeholder="Code: A1234, 3ème étage, porte droite"></textarea>
        </fieldset>

        <fieldset>
            <legend>Sécurité</legend>

            <label for="email">* E-mail :</label>
            <input type="email" id="email" name="email"
                   placeholder="votre.email@exemple.com" required
                   value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">

            <label for="password">* Mot de passe (min. 6 caractères) :</label>
            <input type="password" id="password" name="password"
                   placeholder="Min. 6 caractères" required>
        </fieldset>

        <button type="submit" class="bouton-validation">S'inscrire</button>
    </form>

    <div class="teuchi-bubble">
        <p>Rejoignez le Jardin de Kyoto et profitez de 10% de réduction ! 🎉</p>
    </div>
</main>

<footer>
    <p>&copy; 2025-2026 Le Jardin de Kyoto</p>
</footer>
</body>
</html>

inscription.php
Affichage de connexion.php en cours...