<?php
class SessionManager {
    // Démarrer la session si elle n'est pas déjà active
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Vérifier la connexion et rediriger si nécessaire
    public static function checkLogin() {
        self::start();
        
        // Récupérer le nom du script actuel
        $current_script = basename($_SERVER['PHP_SELF']);
        
        // Vérifier si l'utilisateur est connecté et qu'on n'est pas déjà sur la page de connexion
        if (!isset($_SESSION['user_id']) && $current_script !== 'register.php') {
            // Redirige vers la page de connexion
            header("Location: ../register/register.php");
            exit();
        }
    }
}
