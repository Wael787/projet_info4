JDK.validerFormulaire('form-connexion', {
    email: {
        test: function(v) { return JDK.validateurs.email(v); },
        msg:  "Adresse email invalide (ex : prenom@exemple.com)"
    },
    password: {
        test: function(v) { return JDK.validateurs.nonVide(v) && v.length >= 6; },
        msg:  "Le mot de passe doit contenir au moins 6 caractères"
    }
});
