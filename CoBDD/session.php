<?php
session_start(); // Démarre la session ou la continue

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ../register/register.php");
    exit();
}
?>