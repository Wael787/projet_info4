// js/panier.js
// Logique de la page panier. Chargé dans le <head> avec "defer".

// 1. Afficher le bloc adresse uniquement si le type est "livraison"
document.querySelectorAll('[name="type_livraison"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.getElementById('bloc-adresse').style.display =
            radio.value === 'livraison' ? 'block' : 'none';
    });
});

// 2. Afficher le bloc heure uniquement si "à une heure précise"
document.querySelectorAll('[name="heure_type"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.getElementById('bloc-heure').style.display =
            radio.value === 'programmee' ? 'block' : 'none';
    });
});

// 3. Si une alerte de paiement est présente, on fait défiler jusqu'à elle
//    pour qu'elle soit bien visible (priorité visuelle).
(function () {
    const alerte = document.querySelector('.alerte-paiement');
    if (alerte) {
        alerte.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
})();
