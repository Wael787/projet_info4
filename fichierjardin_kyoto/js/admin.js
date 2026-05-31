document.addEventListener('click', function(event) {
    let bouton = event.target.closest('.btn-ajax-statut');
    if (!bouton) return;

    let userId   = bouton.dataset.userId;
    let action   = bouton.dataset.action;
    let userNom  = bouton.dataset.userNom;

    // confirm uniquement pour le blocage
    if (action === 'bloquer') {
        if (!confirm('Bloquer le compte de ' + userNom + ' ?\n\nL\'utilisateur sera déconnecté immédiatement.')) {
            return;
        }
    }

    // anti double-clic
    bouton.disabled = true;
    let libelleInitial = bouton.textContent;
    bouton.textContent = '⏳ Patientez...';

    let formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action',  action);

    JDK.fetch('actions/maj_utilisateur.php', {
        method: 'POST',
        body:   formData
    })
    .then(function(data) {
        if (!data.ok) {
            throw new Error(data.message || 'Erreur inconnue');
        }
        majLigneUtilisateur(userId, data.user);
        afficherToast('✅ ' + (action === 'bloquer'
            ? userNom + ' a été bloqué (déconnexion immédiate)'
            : userNom + ' a été débloqué'), 'succes');
    })
    .catch(function(err) {
        console.error('[AJAX]', err);
        afficherToast('❌ Erreur : ' + err.message, 'erreur');
        bouton.disabled = false;
        bouton.textContent = libelleInitial;
    });
});

// MAJ visuelle de la ligne après une modif AJAX
function majLigneUtilisateur(userId, userData) {
    let ligne = document.querySelector('tr[data-user-id="' + userId + '"]');
    if (!ligne) return;

    let statut = userData.statut;
    let nouveauEstActif = (statut === 'actif');

    ligne.classList.toggle('ligne-bloquee', !nouveauEstActif);

    let cellule = ligne.querySelector('.cellule-statut');
    if (cellule) {
        cellule.innerHTML = nouveauEstActif
            ? '<span class="badge-statut badge-actif">✅ Actif</span>'
            : '<span class="badge-statut badge-bloque">🔒 Bloqué</span>';
    }

    let bouton = ligne.querySelector('.btn-ajax-statut');
    if (bouton) {
        bouton.disabled = false;
        if (nouveauEstActif) {
            bouton.dataset.action = 'bloquer';
            bouton.textContent = '🔒 Bloquer';
            bouton.classList.remove('btn-success');
            bouton.classList.add('btn-danger');
        } else {
            bouton.dataset.action = 'debloquer';
            bouton.textContent = '🔓 Débloquer';
            bouton.classList.remove('btn-danger');
            bouton.classList.add('btn-success');
        }
    }
}

// Petit toast en haut à droite qui disparait après 3.5s
function afficherToast(message, type) {
    let toast = document.createElement('div');
    toast.className = 'toast toast-' + (type || 'succes');
    toast.setAttribute('role', 'alert');
    toast.textContent = message;
    document.body.appendChild(toast);

    void toast.offsetWidth;  // force un reflow pour que la transition CSS marche
    toast.classList.add('toast-visible');

    setTimeout(function() {
        toast.classList.remove('toast-visible');
        setTimeout(function() { toast.remove(); }, 300);
    }, 3500);
}
