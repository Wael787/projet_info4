(function() {
    // 1. Compteur de caractères pour le textarea
    //    On met à jour à chaque frappe (événement 'input').
    const textarea = document.getElementById('avis_texte');
    const compteur = document.getElementById('compteur-avis');
    if (textarea && compteur) {
        textarea.addEventListener('input', function() {
            compteur.textContent = textarea.value.length;
        });
    }

    // 2. Empêcher la double soumission du formulaire
    //    Si le client clique 2 fois rapidement, on aurait 2 notations.
    //    On désactive le bouton après le premier clic valide.
    const form = document.getElementById('form-notation');
    const btn  = document.getElementById('btn-envoyer');
    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.textContent = '⏳ Envoi en cours…';
        });
    }
})();
