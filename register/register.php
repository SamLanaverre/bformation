<?php
include '../CoBDD/index.php'

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css" />
    <title>Inscription Étudiant</title>
</head>

<body>
<div class="form-structor">
    <div class="signup">
        <h2 class="form-title" id="signup"><span>or</span>Inscription</h2>
        <form method="POST" action="../CoBDD/index.php"> <!-- Formulaire d'inscription -->
            <div class="form-holder">
                <input type="text" class="input" name="nom" placeholder="Nom" required />
                <input type="text" class="input" name="prenom" placeholder="Prénom" required />
                <input type="email" class="input" name="email" placeholder="Email" required />
                <input type="password" class="input" name="mdp" placeholder="Mot de passe" required />
            </div>
            <button type="submit" class="submit-btn">S'inscrire</button>
        </form>
    </div>
    <div class="login slide-up">
        <div class="center">
            <h2 class="form-title" id="login"><span>or</span>Connexion</h2>
            <form method="POST" action="../CoBDD/index.php"> <!-- Formulaire de connexion -->
                <div class="form-holder">
                    <input type="email" class="input" name="email" placeholder="Email" required />
                    <input type="password" class="input" name="mdp" placeholder="Mot de passe" required />
                </div>
                <button type="submit" class="submit-btn">Se connecter</button>
            </form>
        </div>
    </div>
</div>

<footer></footer>

<script>
    console.clear();

    const loginBtn = document.getElementById('login');
    const signupBtn = document.getElementById('signup');

    loginBtn.addEventListener('click', (e) => {
        let parent = e.target.parentNode.parentNode;
        Array.from(e.target.parentNode.parentNode.classList).find((element) => {
            if(element !== "slide-up") {
                parent.classList.add('slide-up')
            } else {
                signupBtn.parentNode.classList.add('slide-up')
                parent.classList.remove('slide-up')
            }
        });
    });
 
    signupBtn.addEventListener('click', (e) => {
        let parent = e.target.parentNode;
        Array.from(e.target.parentNode.classList).find((element) => {
            if(element !== "slide-up") {
                parent.classList.add('slide-up')
            } else {
                loginBtn.parentNode.parentNode.classList.add('slide-up')
                parent.classList.remove('slide-up')
            }
        });
    });
</script>
</body>
</html>

