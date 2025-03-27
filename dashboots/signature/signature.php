<?php
include '../CoBDD/session.php';

// Récupération de l'ID de la signature depuis l'URL
$signature_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

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

// Vérifier si la signature existe et appartient à l'utilisateur connecté
$stmt = $pdo->prepare("
    SELECT 
        sig.idsignature, 
        sig.signed,
        s.idschedule,
        s.schedule_date, 
        s.date_hour_start, 
        s.date_hour_end, 
        sub.name AS subject_name, 
        CONCAT(u.first_name, ' ', u.surname) AS teacher_name,
        c.name AS class_name,
        c.room AS class_room
    FROM 
        signature sig
    JOIN 
        schedule s ON sig.schedule_id = s.idschedule
    JOIN 
        subject sub ON s.subject_id = sub.id_subject
    JOIN 
        user u ON s.teacher_id = u.idUser
    JOIN 
        class c ON s.class_id = c.idclass
    WHERE 
        sig.idsignature = :signature_id AND 
        sig.user_id = :user_id
");
$stmt->bindParam(':signature_id', $signature_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$signature = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de signature
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_presence'])) {
    if ($signature && $signature['signed'] == 0) {
        // Mettre à jour la signature
        $update = $pdo->prepare("
            UPDATE signature 
            SET signed = 1 
            WHERE idsignature = :signature_id
        ");
        $update->bindParam(':signature_id', $signature_id, PDO::PARAM_INT);
        
        if ($update->execute()) {
            // Redirection avec message de succès
            header("Location: dashboots/dashboard/dashboard.php?signed=success");
            exit();
        }
    } else {
        // Redirection avec erreur
        header("Location: dashboots/dashboard/dashboard.php?error=not_available");
        exit();
    }
}

// Si la signature n'existe pas ou est déjà signée, rediriger
if (!$signature) {
    header("Location: dashboots/dashboard/dashboard.php?error=not_available");
    exit();
} elseif ($signature['signed'] == 1) {
    header("Location: dashboots/dashboard/dashboard.php?error=already_signed");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de présence</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboots/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Confirmer votre présence</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h3><?= htmlspecialchars($signature['subject_name']) ?></h3>
                            <p class="mb-1">
                                <i class="bi bi-calendar-event"></i> 
                                <strong>Date:</strong> <?= date('d/m/Y', strtotime($signature['schedule_date'])) ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-clock"></i> 
                                <strong>Horaires:</strong> 
                                <?= date('H:i', strtotime($signature['date_hour_start'])) ?> - 
                                <?= date('H:i', strtotime($signature['date_hour_end'])) ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-person"></i> 
                                <strong>Professeur:</strong> <?= htmlspecialchars($signature['teacher_name']) ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-people"></i> 
                                <strong>Classe:</strong> <?= htmlspecialchars($signature['class_name']) ?>
                            </p>
                            <p class="mb-3">
                                <i class="bi bi-geo-alt"></i> 
                                <strong>Salle:</strong> <?= htmlspecialchars($signature['class_room']) ?>
                            </p>
                        </div>

                        <form method="POST" action="">
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="confirm_presence" name="confirm_presence" value="1" required>
                                <label class="form-check-label" for="confirm_presence">
                                    Je confirme être présent à ce cours
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboots/dashboard/dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Confirmer ma présence
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>