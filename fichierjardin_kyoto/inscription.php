<?php
require_once 'includes/session.php';

// Si déjà connecté → redirige vers la page adaptée au rôle
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin')            header('Location: admin.php');
    elseif ($role === 'restaurateur') header('Location: commandes.php');
    elseif ($role === 'livreur')      header('Location: livraison.php');
    else                              header('Location: index.php');
    exit;
}

$page_title = 'Connexion';
$body_class = 'page-deco';

// Messages
$erreur  = $_GET['erreur']  ?? null;
$message = $_GET['message'] ?? null;

$scripts_page = ['inscription.js'];
include 'includes/header.php';
?>

<main class="login-container">

    <!-- novalidate désactive la validation HTML5 du navigateur :
         on préfère la nôtre qui affiche des messages cohérents -->
    <form id="form-connexion"
          action="actions/login.php"
          method="POST"
          class="boite-formulaire"
          novalidate>
        <h2>🔐 Accès à votre compte JDK</h2>

        <?php if ($erreur === 'identifiants'): ?>
            <p class="message-erreur">Email ou mot de passe incorrect.</p>
        <?php elseif ($erreur === 'bloque'): ?>
            <p class="message-erreur">Votre compte est bloqué. Contactez l'administration.</p>
        <?php elseif ($erreur === 'session_bloquee'): ?>
            <!-- message si l'admin a bloqué pendant que l'user était connecté -->
            <div class="bandeau-alerte" role="alert">
                <div class="bandeau-alerte-icone">🔒</div>
                <div class="bandeau-alerte-contenu">
                    <h3 class="bandeau-alerte-titre">Session terminée</h3>
                    <p class="bandeau-alerte-message">
                        Votre compte a été bloqué par l'administration.
                    </p>
                    <p class="bandeau-alerte-conseil">
                        👉 Contactez l'administration pour plus d'informations.
                    </p>
                </div>
            </div>
        <?php elseif ($erreur === 'session_invalide'): ?>
            <p class="message-erreur">Votre session n'est plus valide. Veuillez vous reconnecter.</p>
        <?php elseif ($erreur === 'non_connecte'): ?>
            <p class="message-erreur">Vous devez être connecté pour accéder à cette page.</p>
        <?php endif; ?>

        <?php if ($message === 'inscription_ok'): ?>
            <p class="message-succes">Inscription réussie ! Vous pouvez maintenant vous connecter.</p>
        <?php elseif ($message === 'deconnexion'): ?>
            <p class="message-succes">Vous avez bien été déconnecté.</p>
        <?php endif; ?>

        <label for="email">📧 E-mail :</label>
        <input type="email" id="email" name="email"
               data-max="100"
               placeholder="votre.email@exemple.com"
               value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">

        <label for="password">🔑 Mot de passe :</label>
        <input type="password" id="password" name="password"
               data-max="64"
               placeholder="••••••••">
        <!-- L'icône œil est ajoutée automatiquement par le script du header -->

        <div class="options">
            <input type="checkbox" id="se_souvenir" name="se_souvenir">
            <label for="se_souvenir">Se souvenir de moi</label>
        </div>

        <button type="submit" class="bouton-validation">Se connecter</button>

        <p class="footer-form">
            Nouveau sur JDK ? <a href="inscription.php">Créer un compte</a>
        </p>
    </form>

    <div class="teuchi-bubble">
        <p>Connectez-vous pour bénéficier de mes offres alléchantes ! 🍜</p>
    </div>

</main>

<!-- Validation côté client : on bloque le submit tant que c'est pas valide -->
<footer>
    <p>&copy; 2025-2026 Le Jardin de Kyoto</p>
</footer>
</body>
</html>