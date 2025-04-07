<?php
include '../../CoBDD/index.php';
include_once '../../CoBDD/sessionmanage.php';

// Récupérer les informations de base de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = isset($_SESSION['user_role']) ? strtolower(trim($_SESSION['user_role'])) : '';
$user_name = $_SESSION['user_name'] ?? '';

// Vérifier si une demande de lancer des signatures a été faite
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Si les IDs sont présents et que l'utilisateur est un professeur, traiter la demande de signatures
if ($schedule_id > 0 && $class_id > 0 && $user_role === 'teacher') {
    // Rediriger vers la page de création de signatures
    header("Location: ../signature/create_signature.php?schedule_id=$schedule_id&class_id=$class_id");
    exit();
}

// Récupérer les messages d'erreur/succès de l'URL pour les afficher
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$signed = $_GET['signed'] ?? '';
$signatures_created = $_GET['signatures_created'] ?? '';

// Contenu pour les élèves
if ($user_role === 'élève') {
    // Récupérer la classe de l'élève
    $stmt = $pdo->prepare("SELECT class_id FROM user WHERE idUser = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_id = $result['class_id'] ?? null;

    if ($class_id) {
        // Récupérer les 5 prochains cours pour cette classe
        $stmt = $pdo->prepare("
            SELECT 
                s.idschedule, 
                s.schedule_date, 
                s.date_hour_start, 
                s.date_hour_end, 
                sub.name AS subject_name, 
                CONCAT(u.first_name, ' ', u.surname) AS teacher_name
            FROM 
                schedule s
            JOIN subject sub ON s.subject_id = sub.id_subject
            JOIN user u ON s.teacher_id = u.idUser
            WHERE 
                s.class_id = :class_id AND
                s.schedule_date >= CURDATE()
            ORDER BY 
                s.schedule_date ASC, s.date_hour_start ASC
            LIMIT 5
        ");
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        $upcoming_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les signatures en attente
        $stmt = $pdo->prepare("
            SELECT 
                sig.idsignature,
                s.schedule_date,
                sub.name AS subject_name,
                s.date_hour_start,
                s.date_hour_end
            FROM 
                signature sig
            JOIN schedule s ON sig.schedule_id = s.idschedule
            JOIN subject sub ON s.subject_id = sub.id_subject
            WHERE 
                sig.user_id = :user_id AND
                sig.signed = 0
            ORDER BY s.schedule_date ASC
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $pending_signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 
// Contenu pour les enseignants
elseif ($user_role === 'teacher') {
    // Récupérer les prochains cours pour ce professeur
    $stmt = $pdo->prepare("
        SELECT 
            s.idschedule, 
            s.class_id,
            s.schedule_date, 
            s.date_hour_start, 
            s.date_hour_end, 
            sub.name AS subject_name, 
            c.name AS class_name
        FROM 
            schedule s
        JOIN subject sub ON s.subject_id = sub.id_subject
        JOIN class c ON s.class_id = c.idclass
        WHERE 
            s.teacher_id = :user_id AND
            s.schedule_date >= CURDATE()
        ORDER BY 
            s.schedule_date ASC, s.date_hour_start ASC
        LIMIT 5
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $upcoming_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier l'état de chaque cours (signatures existantes, cours en cours)
    if (!empty($upcoming_courses)) {
        $current_datetime = date('Y-m-d H:i:s');
        
        foreach ($upcoming_courses as &$course) {
            // Vérifier si des signatures existent déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM signature WHERE schedule_id = :schedule_id");
            $stmt->bindParam(':schedule_id', $course['idschedule'], PDO::PARAM_INT);
            $stmt->execute();
            $course['has_signatures'] = $stmt->fetchColumn() > 0;
            
            // Vérifier si le cours est actuellement en cours
            $course_start = $course['schedule_date'] . ' ' . date('H:i:s', strtotime($course['date_hour_start']));
            $course_end = $course['schedule_date'] . ' ' . date('H:i:s', strtotime($course['date_hour_end']));
            $course['is_current'] = ($current_datetime >= $course_start && $current_datetime <= $course_end);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../dashboard.css">
</head>
<body>
    <?php include '../navbaruser/navbar.php'; // Inclut la barre de navigation ?>

    <div class="container mt-5 pt-5">
        <!-- Affichage des messages d'erreur -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                // Affiche un message d'erreur selon le code reçu
                switch($error) {
                    case 'invalid_course': echo "Le cours spécifié n'existe pas ou vous n'êtes pas autorisé à y accéder."; break;
                    case 'signatures_exist': echo "Des signatures existent déjà pour ce cours."; break;
                    case 'not_course_time': echo "Vous ne pouvez lancer les signatures que pendant la période du cours."; break;
                    case 'not_available': echo "Cette signature n'est pas disponible."; break;
                    case 'already_signed': echo "Vous avez déjà signé ce cours."; break;
                    default: echo "Une erreur s'est produite.";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Affichage des messages de succès -->
        <?php if (!empty($success) || !empty($signed) || !empty($signatures_created)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                if (!empty($signatures_created)) echo "Les signatures ont été créées avec succès.";
                elseif (!empty($signed)) echo "Votre présence a été confirmée avec succès.";
                else echo "Opération réussie.";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Entête du tableau de bord -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Bienvenue <?= htmlspecialchars($user_name) ?></h4>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?= $user_role === 'élève' ? 'Étudiant' : ($user_role === 'teacher' ? 'Enseignant' : 'Administrateur') ?>
                        </h6>
                    </div>
                </div>
            </div>
            
            <?php if ($user_role === 'élève'): ?>
                <!-- SECTION ÉLÈVE: Affichage des cours à venir -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Cours à venir</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($upcoming_courses) && !empty($upcoming_courses)): ?>
                                <div class="list-group">
                                    <?php foreach ($upcoming_courses as $course): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= htmlspecialchars($course['subject_name']) ?></h5>
                                                <small><?= date('d/m/Y', strtotime($course['schedule_date'])) ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <i class="bi bi-clock"></i> 
                                                <?= date('H:i', strtotime($course['date_hour_start'])) ?> - 
                                                <?= date('H:i', strtotime($course['date_hour_end'])) ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-person"></i> 
                                                <?= htmlspecialchars($course['teacher_name']) ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center">Aucun cours à venir.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION ÉLÈVE: Affichage des signatures en attente -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Signatures en attente</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($pending_signatures) && !empty($pending_signatures)): ?>
                                <div class="list-group">
                                    <?php foreach ($pending_signatures as $signature): ?>
                                        <a href="../signature/signature.php?id=<?= $signature['idsignature'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= htmlspecialchars($signature['subject_name']) ?></h5>
                                                <small><?= date('d/m/Y', strtotime($signature['schedule_date'])) ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <i class="bi bi-clock"></i> 
                                                <?= date('H:i', strtotime($signature['date_hour_start'])) ?> - 
                                                <?= date('H:i', strtotime($signature['date_hour_end'])) ?>
                                            </p>
                                            <p class="mb-0 text-success">
                                                <i class="bi bi-pencil-square"></i> Cliquez pour signer
                                            </p>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center">Aucune signature en attente.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($user_role === 'teacher'): ?>
                <!-- SECTION ENSEIGNANT: Affichage des cours et option pour lancer les signatures -->
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Vos prochains cours</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($upcoming_courses) && !empty($upcoming_courses)): ?>
                                <div class="list-group">
                                    <?php foreach ($upcoming_courses as $course): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= htmlspecialchars($course['subject_name']) ?></h5>
                                                <small><?= date('d/m/Y', strtotime($course['schedule_date'])) ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <i class="bi bi-clock"></i> 
                                                <?= date('H:i', strtotime($course['date_hour_start'])) ?> - 
                                                <?= date('H:i', strtotime($course['date_hour_end'])) ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="bi bi-people"></i> 
                                                Classe: <?= htmlspecialchars($course['class_name']) ?>
                                            </p>
                                            
                                            <?php if ($course['is_current'] && !$course['has_signatures']): ?>
                                                <!-- Affiche un bouton pour lancer les signatures si le cours est en cours et pas encore de signatures -->
                                                <a href="dashboard.php?schedule_id=<?= $course['idschedule'] ?>&class_id=<?= $course['class_id'] ?>&noloop=1" class="btn btn-success btn-sm mt-2">
                                                    <i class="bi bi-pencil-square"></i> Lancer les signatures
                                                </a>
                                            <?php elseif ($course['has_signatures']): ?>
                                                <span class="badge bg-info">Signatures déjà initiées</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center">Aucun cours à venir.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($user_role === 'admin'): ?>
                <!-- SECTION ADMIN: Message d'information -->
                <div class="col-lg-12">
                    <div class="alert alert-info">
                        <h5>Administration</h5>
                        <p>Vous êtes connecté en tant qu'administrateur. Utilisez les liens dans la barre de navigation pour gérer le système.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Message pour les utilisateurs avec un rôle non reconnu -->
                <div class="col-lg-12">
                    <div class="alert alert-warning">
                        <h5>Rôle non reconnu</h5>
                        <p>Votre rôle n'a pas été correctement défini dans le système. Veuillez contacter un administrateur.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>