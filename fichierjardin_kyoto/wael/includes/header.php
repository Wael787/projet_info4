<?php
// includes/header.php
// Header commun à toutes les pages.
// Gère aussi le thème (couleur + loupe) via 2 cookies.

$page_title = $page_title ?? 'Le Jardin de Kyoto';
$body_class = $body_class ?? '';

// --- thème couleur (light / dark / contrast) ---
$couleurs_autorisees = ['light', 'dark', 'contrast'];
$theme_couleur = 'light';
if (isset($_COOKIE['theme_couleur']) && in_array($_COOKIE['theme_couleur'], $couleurs_autorisees, true)) {
    $theme_couleur = $_COOKIE['theme_couleur'];
}

// --- mode loupe (on/off, indépendant du thème couleur) ---
$loupe_autorise = ['on', 'off'];
$theme_loupe = 'off';
if (isset($_COOKIE['theme_loupe']) && in_array($_COOKIE['theme_loupe'], $loupe_autorise, true)) {
    $theme_loupe = $_COOKIE['theme_loupe'];
}

// On construit la classe du body
$classes_theme = [];
if ($theme_couleur === 'dark')     $classes_theme[] = 'dark-mode';
if ($theme_couleur === 'contrast') $classes_theme[] = 'contrast-mode';
if ($theme_loupe === 'on')         $classes_theme[] = 'large-mode';

$body_class_finale = trim($body_class . ' ' . implode(' ', $classes_theme));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Le Jardin de Kyoto</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= htmlspecialchars($body_class_finale) ?>">

<header class="entete-principale">

    <div class="conteneur-logo">
        <img src="img/logo.png" alt="Logo Jardin De Kyoto" class="logo-principal">
        <p class="slogan-header">ART CULINAIRE &amp; SÉRÉNITÉ</p>
    </div>

    <div class="section-menu-burger">
        <button class="menu-burger" aria-label="Menu">☰</button>
        <nav class="navigation">
            <ul class="liste-du-haut">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="produit.php">Menu</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="profil.php">Mon profil</a></li>
                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <li><a href="admin.php">Administration</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role'] === 'restaurateur'): ?>
                        <li><a href="commandes.php">Commandes</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role'] === 'livreur'): ?>
                        <li><a href="livraison.php">Ma livraison</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role'] === 'client'): ?>
                        <li><a href="panier.php">🛒 Mon panier</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="actions/logout.php" style="color: #c0392b;">
                            Déconnexion (<?= htmlspecialchars($_SESSION['user']['prenom']) ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="connexion.php">Connexion</a></li>
                    <li><a href="inscription.php">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Selecteur de thème : 3 boutons exclusifs + 1 toggle loupe à part -->
    <div class="theme-selector" role="toolbar" aria-label="Préférences d'affichage">

        <div class="theme-group theme-group-couleur" role="radiogroup" aria-label="Schéma de couleur">
            <button type="button" class="theme-btn" data-couleur="light"
                    <?= $theme_couleur === 'light' ? 'aria-current="true"' : '' ?>
                    title="Mode clair">
                <span class="theme-icon">☀️</span>
                <span class="theme-label">Clair</span>
            </button>
            <button type="button" class="theme-btn" data-couleur="dark"
                    <?= $theme_couleur === 'dark' ? 'aria-current="true"' : '' ?>
                    title="Mode sombre">
                <span class="theme-icon">🌙</span>
                <span class="theme-label">Sombre</span>
            </button>
            <button type="button" class="theme-btn" data-couleur="contrast"
                    <?= $theme_couleur === 'contrast' ? 'aria-current="true"' : '' ?>
                    title="Mode contrasté">
                <span class="theme-icon">🌗</span>
                <span class="theme-label">Contraste</span>
            </button>
        </div>

        <div class="theme-separator" aria-hidden="true"></div>

        <div class="theme-group theme-group-loupe">
            <button type="button" class="theme-btn theme-btn-loupe" data-loupe="toggle"
                    <?= $theme_loupe === 'on' ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>
                    title="Activer/désactiver le mode loupe">
                <span class="theme-icon">🔍</span>
                <span class="theme-label">Loupe</span>
            </button>
        </div>

    </div>

</header>

<script>
// ============== GESTION DU THEME ==============
(function() {
    'use strict';
    var body = document.body;
    var couleursAutorisees = ['light', 'dark', 'contrast'];

    function ecrireCookie(nom, valeur) {
        var unAn = 60 * 60 * 24 * 365;
        document.cookie = nom + '=' + encodeURIComponent(valeur) +
                          '; path=/; max-age=' + unAn + '; SameSite=Lax';
    }

    function appliquerCouleur(couleur) {
        // whitelist côté JS aussi, on est jamais trop prudent
        if (couleursAutorisees.indexOf(couleur) === -1) couleur = 'light';

        body.classList.remove('dark-mode', 'contrast-mode');
        if (couleur === 'dark')     body.classList.add('dark-mode');
        if (couleur === 'contrast') body.classList.add('contrast-mode');

        var btns = document.querySelectorAll('.theme-btn[data-couleur]');
        for (var i = 0; i < btns.length; i++) {
            if (btns[i].getAttribute('data-couleur') === couleur) {
                btns[i].setAttribute('aria-current', 'true');
            } else {
                btns[i].removeAttribute('aria-current');
            }
        }
        ecrireCookie('theme_couleur', couleur);
    }

    function appliquerLoupe(active) {
        if (active) {
            body.classList.add('large-mode');
            ecrireCookie('theme_loupe', 'on');
        } else {
            body.classList.remove('large-mode');
            ecrireCookie('theme_loupe', 'off');
        }
        var btn = document.querySelector('.theme-btn-loupe');
        if (btn) btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    }

    // listener sur chaque bouton couleur
    var btnsCouleur = document.querySelectorAll('.theme-btn[data-couleur]');
    for (var i = 0; i < btnsCouleur.length; i++) {
        (function(btn) {
            btn.addEventListener('click', function() {
                appliquerCouleur(btn.getAttribute('data-couleur'));
            });
        })(btnsCouleur[i]);
    }

    // toggle loupe
    var btnLoupe = document.querySelector('.theme-btn-loupe');
    if (btnLoupe) {
        btnLoupe.addEventListener('click', function() {
            var dejaActive = body.classList.contains('large-mode');
            appliquerLoupe(!dejaActive);
        });
    }
})();


// ============== VALIDATEURS REUTILISABLES ==============
window.JDK = window.JDK || {};

window.JDK.validateurs = {
    nonVide:      function(v) { return v !== null && v !== undefined && String(v).trim().length > 0; },
    email:        function(v) { return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v.trim()); },
    telephone:    function(v) { var s = v.replace(/[\s.\-]/g, ''); return /^0[1-9][0-9]{8}$/.test(s); },
    nomPropre:    function(v) { return /^[A-Za-zÀ-ÿ\s\-']{2,50}$/.test(v.trim()); },
    adresse:      function(v) { return v.trim().length >= 5 && v.trim().length <= 200; },
    longueurMin:  function(v, min) { return String(v).length >= min; },
    longueurMax:  function(v, max) { return String(v).length <= max; },
    entierEntre:  function(v, min, max) { var n = Number(v); return Number.isInteger(n) && n >= min && n <= max; },
    dateValide:   function(v) { if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false; return !isNaN(new Date(v).getTime()); }
};

// Fonction générique appelée depuis chaque page de formulaire
window.JDK.validerFormulaire = function(formId, regles) {
    var form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(event) {
        var ok = true;
        // on nettoie les anciennes erreurs
        form.querySelectorAll('.erreur-champ').forEach(function(el) { el.remove(); });
        form.querySelectorAll('.champ-invalide').forEach(function(el) { el.classList.remove('champ-invalide'); });

        for (var id in regles) {
            if (!regles.hasOwnProperty(id)) continue;
            var input = form.querySelector('#' + id);
            if (!input) continue;
            if (!regles[id].test(input.value)) {
                ok = false;
                afficherErreurChamp(input, regles[id].msg);
            }
        }

        if (!ok) {
            event.preventDefault();
            var premier = form.querySelector('.champ-invalide');
            if (premier) premier.focus();
        }
    });
};

function afficherErreurChamp(input, message) {
    input.classList.add('champ-invalide');
    var div = document.createElement('div');
    div.className = 'erreur-champ';
    div.setAttribute('role', 'alert');
    div.textContent = '⚠ ' + message;
    // si le champ est wrappé pour l'oeil, on met le msg après le wrapper
    var parent = input.closest('.password-wrapper') || input;
    parent.parentNode.insertBefore(div, parent.nextSibling);
}


// ============== ICONE OEIL POUR LES MOTS DE PASSE ==============
// On parcours tous les inputs password et on leur ajoute un toggle
document.addEventListener('DOMContentLoaded', function() {
    var motsDePasse = document.querySelectorAll('input[type="password"]');
    motsDePasse.forEach(function(input) {
        if (input.parentNode.classList.contains('password-wrapper')) return;

        var wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var oeil = document.createElement('button');
        oeil.type = 'button';  // sinon il submit le form
        oeil.className = 'toggle-password';
        oeil.setAttribute('aria-label', 'Afficher le mot de passe');
        oeil.textContent = '👁';
        wrapper.appendChild(oeil);

        oeil.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                oeil.textContent = '🙈';
            } else {
                input.type = 'password';
                oeil.textContent = '👁';
            }
        });
    });
});


// ============== COMPTEUR DE CARACTERES ==============
// Sur tout champ avec data-max="N", on affiche "X / N caractères" en dessous.
// Devient orange à 80%, rouge à 100%.
document.addEventListener('DOMContentLoaded', function() {
    var champs = document.querySelectorAll('[data-max]');

    champs.forEach(function(champ) {
        var max = parseInt(champ.getAttribute('data-max'), 10);
        if (isNaN(max) || max <= 0) return;

        // double sécurité : le navigateur bloque aussi la saisie
        if (!champ.hasAttribute('maxlength')) {
            champ.setAttribute('maxlength', max);
        }

        var compteur = document.createElement('div');
        compteur.className = 'compteur-chars';
        compteur.setAttribute('aria-live', 'polite');

        // pour les password wrappés, on insère après le wrapper pas dedans
        var reference = champ.closest('.password-wrapper') || champ;
        reference.parentNode.insertBefore(compteur, reference.nextSibling);

        function maj() {
            var n = champ.value.length;
            compteur.textContent = n + ' / ' + max + ' caractères';
            var ratio = n / max;
            compteur.classList.remove('compteur-warning', 'compteur-danger');
            if (ratio >= 1)        compteur.classList.add('compteur-danger');
            else if (ratio >= 0.8) compteur.classList.add('compteur-warning');
        }

        maj();
        champ.addEventListener('input', maj);
    });
});


// ============== HELPER AJAX ==============
// Wrapper autour de fetch qui ajoute les bons headers et parse le JSON
window.JDK.fetch = function(url, options) {
    options = options || {};
    options.headers = options.headers || {};
    options.headers['Accept'] = 'application/json';
    options.headers['X-Requested-With'] = 'XMLHttpRequest';

    return fetch(url, options).then(function(response) {
        if (!response.ok) {
            return response.text().then(function(text) {
                throw new Error('HTTP ' + response.status + ' : ' + text);
            });
        }
        return response.json();
    });
};


// ============== POLLING : check de session toutes les 10s ==============
// Si l'admin bloque un utilisateur pendant qu'il est sur une page sans
// rien cliquer, ce check le déconnecte automatiquement.
<?php if (isset($_SESSION['user'])): ?>
setInterval(function() {
    fetch('actions/check_session.php', { headers: { 'Accept': 'application/json' }})
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.actif) {
                // la session n'est plus valide -> on redirige
                window.location.href = 'connexion.php?erreur=session_bloquee';
            }
        })
        .catch(function() { /* erreur réseau, on ignore */ });
}, 10000);  // 10 secondes
<?php endif; ?>
</script>
