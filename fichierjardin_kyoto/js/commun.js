// Script commun à toutes les pages (thème, validation des formulaires,
// affichage des mots de passe, compteur de caractères, suivi de session).

// ============== GESTION DU THEME ==============
(function () {
    'use strict';
    const body = document.body;
    const couleursAutorisees = ['light', 'dark', 'contrast'];

    function ecrireCookie(nom, valeur) {
        const unAn = 60 * 60 * 24 * 365;
        document.cookie = nom + '=' + encodeURIComponent(valeur) +
            '; path=/; max-age=' + unAn + '; SameSite=Lax';
    }

    function appliquerCouleur(couleur) {
        if (couleursAutorisees.indexOf(couleur) === -1) couleur = 'light';

        body.classList.remove('dark-mode', 'contrast-mode');
        if (couleur === 'dark') body.classList.add('dark-mode');
        if (couleur === 'contrast') body.classList.add('contrast-mode');

        const btns = document.querySelectorAll('.theme-btn[data-couleur]');
        for (let i = 0; i < btns.length; i++) {
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
        const btn = document.querySelector('.theme-btn-loupe');
        if (btn) btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    }

    const btnsCouleur = document.querySelectorAll('.theme-btn[data-couleur]');
    for (let i = 0; i < btnsCouleur.length; i++) {
        const btn = btnsCouleur[i];
        btn.addEventListener('click', function () {
            appliquerCouleur(btn.getAttribute('data-couleur'));
        });
    }

    const btnLoupe = document.querySelector('.theme-btn-loupe');
    if (btnLoupe) {
        btnLoupe.addEventListener('click', function () {
            const dejaActive = body.classList.contains('large-mode');
            appliquerLoupe(!dejaActive);
        });
    }
})();


// ============== VALIDATEURS REUTILISABLES ==============
window.JDK = window.JDK || {};

window.JDK.validateurs = {
    nonVide:     function (v) { return v !== null && v !== undefined && String(v).trim().length > 0; },
    email:       function (v) { return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v.trim()); },
    telephone:   function (v) { const s = v.replace(/[\s.\-]/g, ''); return /^0[1-9][0-9]{8}$/.test(s); },
    nomPropre:   function (v) { return /^[A-Za-zÀ-ÿ\s\-']{2,50}$/.test(v.trim()); },
    adresse:     function (v) { return v.trim().length >= 5 && v.trim().length <= 200; },
    longueurMin: function (v, min) { return String(v).length >= min; },
    longueurMax: function (v, max) { return String(v).length <= max; },
    entierEntre: function (v, min, max) { const n = Number(v); return Number.isInteger(n) && n >= min && n <= max; },
    dateValide:  function (v) { if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false; return !isNaN(new Date(v).getTime()); }
};

window.JDK.validerFormulaire = function (formId, regles) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function (event) {
        let ok = true;
        form.querySelectorAll('.erreur-champ').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.champ-invalide').forEach(function (el) { el.classList.remove('champ-invalide'); });

        for (const id in regles) {
            if (!regles.hasOwnProperty(id)) continue;
            const input = form.querySelector('#' + id);
            if (!input) continue;
            if (!regles[id].test(input.value)) {
                ok = false;
                afficherErreurChamp(input, regles[id].msg);
            }
        }

        if (!ok) {
            event.preventDefault();
            const premier = form.querySelector('.champ-invalide');
            if (premier) premier.focus();
        }
    });
};

function afficherErreurChamp(input, message) {
    input.classList.add('champ-invalide');
    const div = document.createElement('div');
    div.className = 'erreur-champ';
    div.setAttribute('role', 'alert');
    div.textContent = '⚠ ' + message;
    const parent = input.closest('.password-wrapper') || input;
    parent.parentNode.insertBefore(div, parent.nextSibling);
}


// ============== ICONE OEIL POUR LES MOTS DE PASSE ==============
document.addEventListener('DOMContentLoaded', function () {
    const motsDePasse = document.querySelectorAll('input[type="password"]');
    motsDePasse.forEach(function (input) {
        if (input.parentNode.classList.contains('password-wrapper')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const oeil = document.createElement('button');
        oeil.type = 'button';
        oeil.className = 'toggle-password';
        oeil.setAttribute('aria-label', 'Afficher le mot de passe');
        oeil.textContent = '👁';
        wrapper.appendChild(oeil);

        oeil.addEventListener('click', function () {
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
document.addEventListener('DOMContentLoaded', function () {
    const champs = document.querySelectorAll('[data-max]');

    champs.forEach(function (champ) {
        const max = parseInt(champ.getAttribute('data-max'), 10);
        if (isNaN(max) || max <= 0) return;

        if (!champ.hasAttribute('maxlength')) {
            champ.setAttribute('maxlength', max);
        }

        const compteur = document.createElement('div');
        compteur.className = 'compteur-chars';
        compteur.setAttribute('aria-live', 'polite');

        const reference = champ.closest('.password-wrapper') || champ;
        reference.parentNode.insertBefore(compteur, reference.nextSibling);

        function maj() {
            const n = champ.value.length;
            compteur.textContent = n + ' / ' + max + ' caractères';
            const ratio = n / max;
            compteur.classList.remove('compteur-warning', 'compteur-danger');
            if (ratio >= 1) compteur.classList.add('compteur-danger');
            else if (ratio >= 0.8) compteur.classList.add('compteur-warning');
        }

        maj();
        champ.addEventListener('input', maj);
    });
});


// ============== HELPER AJAX ==============
window.JDK.fetch = function (url, options) {
    options = options || {};
    options.headers = options.headers || {};
    options.headers['Accept'] = 'application/json';
    options.headers['X-Requested-With'] = 'XMLHttpRequest';

    return fetch(url, options).then(function (response) {
        if (!response.ok) {
            return response.text().then(function (text) {
                throw new Error('HTTP ' + response.status + ' : ' + text);
            });
        }
        return response.json();
    });
};


// ============== POLLING : check de session toutes les 10s ==============
// On ne lance le suivi que si l'utilisateur est connecté (data-connecte="1").
(function () {
    if (document.body.dataset.connecte !== '1') return;

    setInterval(function () {
        fetch('/jardin_kyoto/actions/check_session.php', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.actif) {
                    window.location.href = '/jardin_kyoto/connexion.php?erreur=session_bloquee';
                }
            })
            .catch(function () { });
    }, 10000);
})();
