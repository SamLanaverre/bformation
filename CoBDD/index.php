<?php
session_start(); // Démarrer la session

// Connexion à la base de données
$host = 'localhost'; 
$user = 'root'; 
$password = ''; 
$database = 'bformation';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction de hashage sécurisé
function hashPwd($pwd) {
    return password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Fonction d'inscription
function register($pdo, $nom, $prenom, $email, $mdp) {
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Email invalide.";
        }

        // Vérifie si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            return "Cet email est déjà utilisé.";
        }

        $hashedPwd = hashPwd($mdp);
        $role = 'élève'; // Rôle par défaut

        $sql = "INSERT INTO user (first_name, surname, email, password, role) VALUES (:nom, :prenom, :email, :mdp, :role)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindValue(':prenom', $prenom, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':mdp', $hashedPwd, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        error_log("Erreur SQL : " . $e->getMessage());
        return "Une erreur est survenue.";
    }
}

// Fonction de connexion
function loginUser($pdo, $email, $mdp) {
    $sql = "SELECT * FROM user WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe
    if (!$user) {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: ../register/register.php");
        exit();
    }

    // Vérification du mot de passe
    if (!password_verify($mdp, $user['password'])) {
        $_SESSION['error'] = "Mot de passe incorrect.";
        header("Location: ../register/register.php");
        exit();
    }

    // Sécurisation de la session
    session_regenerate_id(true); 

    // Stocker les infos utilisateur en session
    $_SESSION['user_id'] = $user['idUser'];
    $_SESSION['user_role'] = strtolower(trim($user['role']));  
    $_SESSION['user_name'] = $user['first_name'];

    // Redirection selon le rôle
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: ../adminboots/admindashboard/admin.php");
        exit();
    } else {
        header("Location: ../dashboots/dashboard.php");
        exit();
    }
}

// Traitement inscription
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['mdp'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];

    if (!empty($nom) && !empty($prenom) && !empty($email) && !empty($mdp)) {
        $result = register($pdo, $nom, $prenom, $email, $mdp);
        if ($result === true) {
            header("Location: ../register/register.php?success=1");
            exit();
        } else {
            $_SESSION['error'] = $result;
            header("Location: ../register/register.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Tous les champs sont obligatoires !";
        header("Location: ../register/register.php");
        exit();
    }
}

// Traitement connexion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'], $_POST['mdp']) && !isset($_POST['nom'], $_POST['prenom'])) {
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];

    if (!empty($email) && !empty($mdp)) {
        loginUser($pdo, $email, $mdp);
    } else {
        $_SESSION['error'] = "Veuillez remplir tous les champs !";
        header("Location: ../register/register.php");
        exit();
    }
}
?>