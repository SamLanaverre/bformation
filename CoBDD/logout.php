<?php
session_start(); // Démarre la session si elle existe

// Détruire toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session (si applicable)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header("Location: ../register/register.php");
exit();
?>