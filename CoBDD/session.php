<?php
// Vérifier si une session est déjà active avant de la démarrer
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Démarre la session ou la continue
}

// Récupérer le nom du script actuel
$current_script = basename($_SERVER['PHP_SELF']);

// Vérifier si l'utilisateur est connecté et qu'on n'est pas déjà sur la page de connexion/inscription
if (!isset($_SESSION['user_id']) && $current_script !== 'register.php') {
    // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ../register/register.php");
    exit();
}
?>
