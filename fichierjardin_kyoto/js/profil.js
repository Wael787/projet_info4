// Page profil : édition des infos, de l'adresse, et modification d'une commande payée.

// id utilisateur récupéré depuis l'attribut data-user-id de <main>
const main   = document.querySelector('main');
const userId = (main && main.dataset.userId) ? main.dataset.userId : '';


// ---- Modifier profil (boutons afficher/masquer le formulaire) ----

document.getElementById('btn-modifier-infos').addEventListener('click', function () {
    document.getElementById('mode-lecture-infos').style.display = 'none';
    document.getElementById('mode-edition-infos').style.display = 'block';
    this.style.display = 'none';
});

document.getElementById('btn-annuler-infos').addEventListener('click', function () {
    document.getElementById('mode-edition-infos').style.display = 'none';
    document.getElementById('mode-lecture-infos').style.display = 'block';
    document.getElementById('btn-modifier-infos').style.display = 'inline-block';
    document.getElementById('msg-infos').style.display = 'none';
});

document.getElementById('btn-modifier-adresse').addEventListener('click', function () {
    document.getElementById('mode-lecture-adresse').style.display = 'none';
    document.getElementById('mode-edition-adresse').style.display = 'block';
    this.style.display = 'none';
});

document.getElementById('btn-annuler-adresse').addEventListener('click', function () {
    document.getElementById('mode-edition-adresse').style.display = 'none';
    document.getElementById('mode-lecture-adresse').style.display = 'block';
    document.getElementById('btn-modifier-adresse').style.display = 'inline-block';
    document.getElementById('msg-adresse').style.display = 'none';
});


// Petit utilitaire pour afficher un message d'erreur dans la zone <p id="...">
function afficherMessage(idMsg, texte, couleur) {
    const msg = document.getElementById(idMsg);
    msg.style.display = 'block';
    msg.style.color = couleur;
    msg.textContent = texte;
}


// Envoie les données modifiées au serveur via fetch (connexion asynchrone)
async function sauvegarderProfil(donnees, idMsg) {
    donnees.user_id = userId;

    afficherMessage(idMsg, 'Enregistrement...', '#888');

    try {
        const reponse = await fetch('actions/maj_profil.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(donnees)
        });
        const resultat = await reponse.json();

        if (resultat.succes) {
            afficherMessage(idMsg, '✅ ' + resultat.message, 'green');
            mettreAJourAffichage(donnees);
        } else {
            // Le serveur peut aussi refuser (ex : email invalide) -> on affiche son message
            afficherMessage(idMsg, '❌ ' + resultat.message, '#c0392b');
        }
    } catch (e) {
        afficherMessage(idMsg, '❌ Erreur de connexion.', '#c0392b');
    }
}

// Met à jour les valeurs affichées en mode lecture
function mettreAJourAffichage(donnees) {
    if (donnees.prenom     !== undefined) { document.getElementById('lecture-prenom').textContent     = donnees.prenom; }
    if (donnees.nom        !== undefined) { document.getElementById('lecture-nom').textContent        = donnees.nom; }
    if (donnees.email      !== undefined) { document.getElementById('lecture-email').textContent      = donnees.email; }
    if (donnees.telephone  !== undefined) { document.getElementById('lecture-telephone').textContent  = donnees.telephone; }
    if (donnees.adresse    !== undefined) { document.getElementById('lecture-adresse').textContent    = donnees.adresse || '—'; }
    if (donnees.infos_comp !== undefined) { document.getElementById('lecture-infos-comp').textContent = donnees.infos_comp || '—'; }

    const prenom = document.getElementById('lecture-prenom').textContent;
    const nom    = document.getElementById('lecture-nom').textContent;
    document.getElementById('affichage-prenom-nom').textContent = prenom + ' ' + nom;
}


// ---- Vérification des champs avant envoi ----

document.getElementById('btn-valider-infos').addEventListener('click', function () {
    const prenom    = document.getElementById('edit-prenom').value.trim();
    const nom       = document.getElementById('edit-nom').value.trim();
    const email     = document.getElementById('edit-email').value.trim();
    const telephone = document.getElementById('edit-telephone').value.trim();

    // 1) prénom et nom obligatoires
    if (prenom === '' || nom === '') {
        afficherMessage('msg-infos', '❌ Le prénom et le nom sont obligatoires.', '#c0392b');
        return;
    }
    // 2) email : doit ressembler à un email valide
    if (!JDK.validateurs.email(email)) {
        afficherMessage('msg-infos', '❌ Adresse email invalide (ex : prenom@exemple.com).', '#c0392b');
        return;
    }
    // 3) téléphone : facultatif, mais s'il est rempli il doit être un numéro français valide
    if (telephone !== '' && !JDK.validateurs.telephone(telephone)) {
        afficherMessage('msg-infos', '❌ Téléphone invalide (ex : 06 12 34 56 78).', '#c0392b');
        return;
    }

    sauvegarderProfil({ prenom: prenom, nom: nom, email: email, telephone: telephone }, 'msg-infos');
});

document.getElementById('btn-valider-adresse').addEventListener('click', function () {
    const adresse    = document.getElementById('edit-adresse').value.trim();
    const infosComp  = document.getElementById('edit-infos-comp').value.trim();

    // L'adresse, si renseignée, doit avoir une longueur minimale plausible
    if (adresse !== '' && !JDK.validateurs.adresse(adresse)) {
        afficherMessage('msg-adresse', '❌ Adresse trop courte (saisissez une adresse complète).', '#c0392b');
        return;
    }

    sauvegarderProfil({ adresse: adresse, infos_comp: infosComp }, 'msg-adresse');
});


// ---- Modifier une commande déjà payée ----

// On stocke les quantités en cours de modification pour chaque commande
// Ex : { 'ABC123': { 'P012': 2, 'P016': 0 } }
let quantites           = {};
let quantitesOriginales = {};

function ouvrirModif(commandeId) {
    document.getElementById('panneau-' + commandeId).style.display = 'block';

    // On mémorise les quantités actuellement affichées
    // quantitesOriginales sert à restaurer si le client clique Annuler
    quantites[commandeId]           = {};
    quantitesOriginales[commandeId] = {};

    const panneau = document.getElementById('panneau-' + commandeId);
    const spans   = panneau.querySelectorAll('span[id]');

    for (let i = 0; i < spans.length; i++) {
        const id = spans[i].id;
        if (id.indexOf('qte-') === 0) {
            const prefixe = 'qte-' + commandeId + '-';
            const platId  = id.slice(prefixe.length);
            const qte     = parseInt(spans[i].textContent);
            quantites[commandeId][platId]           = qte;
            quantitesOriginales[commandeId][platId] = qte;
        }
    }
}

function fermerModif(commandeId) {
    const panneau = document.getElementById('panneau-' + commandeId);
    panneau.style.display = 'none';

    // Remettre le message à zéro
    document.getElementById('msg-cmd-' + commandeId).style.display = 'none';

    // Cacher la zone de paiement CYBank si elle était affichée
    const zonePaiement = document.getElementById('zone-paiement-' + commandeId);
    if (zonePaiement) {
        zonePaiement.style.display = 'none';
        zonePaiement.innerHTML = '<p style="margin:0 0 10px;font-size:.9em;color:#e67e22;font-weight:bold;">💳 Paiement de la différence requis</p>';
    }

    // Remettre les quantités à leur valeur d'origine
    if (quantitesOriginales[commandeId]) {
        for (const platId in quantitesOriginales[commandeId]) {
            const span = document.getElementById('qte-' + commandeId + '-' + platId);
            if (span) span.textContent = quantitesOriginales[commandeId][platId];
        }
    }
    delete quantitesOriginales[commandeId];

    // Réactiver tous les boutons +/- et Valider
    const btnsQte    = panneau.querySelectorAll('.btn-qte');
    const btnValider = panneau.querySelector('.bouton-validation');
    for (let i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = false; }
    if (btnValider) btnValider.disabled = false;

    delete quantites[commandeId];
}

function changerQte(commandeId, platId, delta) {
    const span = document.getElementById('qte-' + commandeId + '-' + platId);
    if (!span) return;

    let qte = parseInt(span.textContent) + delta;
    if (qte < 0) qte = 0;

    span.textContent = qte;

    if (!quantites[commandeId]) quantites[commandeId] = {};
    quantites[commandeId][platId] = qte;
}

async function validerModif(commandeId) {
    const msg = document.getElementById('msg-cmd-' + commandeId);
    msg.style.display = 'block';
    msg.style.color   = '#888';
    msg.textContent   = 'Enregistrement...';

    // On désactive uniquement les boutons +/- et Valider pendant l'envoi
    // Le bouton Annuler reste actif
    const panneau    = document.getElementById('panneau-' + commandeId);
    const btnsQte    = panneau.querySelectorAll('.btn-qte');
    const btnValider = panneau.querySelector('.bouton-validation');
    for (let i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = true; }
    if (btnValider) btnValider.disabled = true;

    try {
        const reponse = await fetch('actions/maj_commande_client.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                commande_id: commandeId,
                quantites:   quantites[commandeId] || {}
            })
        });
        const resultat = await reponse.json();

        if (!resultat.succes) {
            msg.style.color = '#c0392b';
            msg.textContent = '❌ ' + resultat.message;
            // On réactive les boutons en cas d'erreur
            for (let i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = false; }
            if (btnValider) btnValider.disabled = false;
            return;
        }

        // Cas 1 : le nouveau total est plus élevé -> il faut payer la différence
        if (resultat.situation === 'plus_cher') {
            msg.style.color = '#e67e22';
            msg.textContent = '⚠️ ' + resultat.message;

            // On affiche le formulaire CYBank juste en dessous du message
            const zonePaiement = document.getElementById('zone-paiement-' + commandeId);
            if (zonePaiement) {
                zonePaiement.innerHTML = resultat.formulaire;
                zonePaiement.style.display = 'block';
            }
            // Les +/- et Valider restent désactivés : la commande est déjà sauvegardée,
            // le client doit payer avant de pouvoir modifier à nouveau

        // Cas 2 : le total a diminué -> on informe, pas de remboursement
        } else if (resultat.situation === 'moins_cher') {
            msg.style.color = 'green';
            msg.textContent = '✅ ' + resultat.message;
            setTimeout(function () { window.location.reload(); }, 1500);

        // Cas 3 : même total -> simple confirmation
        } else {
            msg.style.color = 'green';
            msg.textContent = '✅ ' + resultat.message;
            setTimeout(function () { window.location.reload(); }, 1000);
        }

    } catch (e) {
        msg.style.color = '#c0392b';
        msg.textContent = '❌ Erreur de connexion.';
        // On réactive les boutons en cas d'erreur réseau
        for (let i = 0; i < btnsQte.length; i++) { btnsQte[i].disabled = false; }
        if (btnValider) btnValider.disabled = false;
    }
}
