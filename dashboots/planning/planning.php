<?php
include '../../CoBDD/index.php'; // Connexion à la base de données

// Récupérer les informations de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Déterminer la date de début (aujourd'hui par défaut)
$start_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$current_date = date('Y-m-d');

// Calculer les dates pour la navigation
$prev_week = date('Y-m-d', strtotime($start_date . ' -7 days'));
$next_week = date('Y-m-d', strtotime($start_date . ' +7 days'));

// Préparer la requête SQL en fonction du rôle
if ($user_role === 'élève') {
    // Récupérer la classe de l'élève
    $stmt = $pdo->prepare("
        SELECT u.class_id, c.room AS class_room, c.name AS class_name 
        FROM user u
        LEFT JOIN class c ON u.class_id = c.idclass
        WHERE u.idUser = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur a une classe assignée
    if ($user_data && !empty($user_data['class_id'])) {
        $class_id = $user_data['class_id'];
        $class_room = $user_data['class_room'] ?? 'Salle non définie';
        $class_name = $user_data['class_name'] ?? 'Classe non définie';

        // Récupérer les cours pour cette classe
        $query = "
            SELECT 
                s.idschedule, 
                s.schedule_date, 
                s.date_hour_start, 
                s.date_hour_end, 
                sub.name AS subject_name, 
                CONCAT(u.first_name, ' ', u.surname) AS teacher_name,
                c.name AS class_name,
                c.room AS class_room
            FROM 
                schedule s
            JOIN 
                subject sub ON s.subject_id = sub.id_subject
            JOIN 
                user u ON s.teacher_id = u.idUser
            JOIN 
                class c ON s.class_id = c.idclass
            WHERE 
                s.class_id = :class_id AND
                s.schedule_date >= :start_date AND 
                s.schedule_date <= DATE_ADD(:start_date, INTERVAL 7 DAY)
            ORDER BY 
                s.schedule_date ASC, s.date_hour_start ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
    } else {
        // L'élève n'a pas de classe assignée
        $no_class_assigned = true;
        // Créer une requête vide pour éviter les erreurs
        $query = "SELECT 1 WHERE 0";
        $stmt = $pdo->prepare($query);
    }
} elseif ($user_role === 'teacher') {
    // Récupérer les cours pour ce professeur
    $query = "
        SELECT 
            s.idschedule, 
            s.schedule_date, 
            s.date_hour_start, 
            s.date_hour_end, 
            sub.name AS subject_name, 
            CONCAT(u.first_name, ' ', u.surname) AS teacher_name,
            c.name AS class_name,
            c.room AS class_room
        FROM 
            schedule s
        JOIN 
            subject sub ON s.subject_id = sub.id_subject
        JOIN 
            user u ON s.teacher_id = u.idUser
        JOIN 
            class c ON s.class_id = c.idclass
        WHERE 
            s.teacher_id = :user_id AND
            s.schedule_date >= :start_date AND 
            s.schedule_date <= DATE_ADD(:start_date, INTERVAL 7 DAY)
        ORDER BY 
            s.schedule_date ASC, s.date_hour_start ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
} else {
    // Pour les administrateurs, afficher tous les cours
    $query = "
        SELECT 
            s.idschedule, 
            s.schedule_date, 
            s.date_hour_start, 
            s.date_hour_end, 
            sub.name AS subject_name, 
            CONCAT(u.first_name, ' ', u.surname) AS teacher_name,
            c.name AS class_name,
            c.room AS class_room
        FROM 
            schedule s
        JOIN 
            subject sub ON s.subject_id = sub.id_subject
        JOIN 
            user u ON s.teacher_id = u.idUser
        JOIN 
            class c ON s.class_id = c.idclass
        WHERE 
            s.schedule_date >= :start_date AND 
            s.schedule_date <= DATE_ADD(:start_date, INTERVAL 7 DAY)
        ORDER BY 
            s.schedule_date ASC, s.date_hour_start ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
}

$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les cours par jour pour l'affichage
$schedule_by_day = [];
$start_timestamp = strtotime($start_date);

// Déterminer le premier jour de la semaine (lundi) à partir de la date de début
$current_day_of_week = date('N', $start_timestamp); // 1 (lundi) à 7 (dimanche)
$days_to_subtract = $current_day_of_week - 1;
$first_monday = date('Y-m-d', strtotime($start_date . " -$days_to_subtract days"));

// Générer uniquement les jours de lundi à vendredi (5 jours)
for ($i = 0; $i < 5; $i++) {
    $date = date('Y-m-d', strtotime($first_monday . " +$i days"));
    $day_name = date('l', strtotime($date));
    $schedule_by_day[$date] = [
        'date' => $date,
        'day_name' => $day_name,
        'courses' => []
    ];
}

// Organiser les cours dans le tableau par jour
foreach ($courses as $course) {
    $date = $course['schedule_date'];
    if (isset($schedule_by_day[$date])) {
        $schedule_by_day[$date]['courses'][] = $course;
    }
}

// Traduction des jours de la semaine en français
function translateDayToFrench($day) {
    $days = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche'
    ];
    return $days[$day] ?? $day;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planning des cours</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="planning.css">
</head>
<body>

<?php 
include '../navbaruser/navbar.php';
?>

<main class="container mt-5 pt-5">
    <h1 class="mb-4 text-center">Planning des cours</h1>
    
    <?php if (isset($no_class_assigned) && $no_class_assigned): ?>
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Vous n'avez pas encore été assigné à une classe. Veuillez contacter un administrateur.
        </div>
    <?php else: ?>
        <!-- Navigation entre les semaines -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="?date=<?= $prev_week ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Semaine précédente
            </a>
            <h2><?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($start_date . ' +6 days')) ?></h2>
            <a href="?date=<?= $next_week ?>" class="btn btn-outline-primary">
                Semaine suivante <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <!-- Bouton pour revenir à aujourd'hui -->
        <div class="text-center mb-4">
            <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-primary">Aujourd'hui</a>
        </div>

        <!-- Affichage du planning par jour -->
        <div class="schedule-container">
            <?php foreach ($schedule_by_day as $day): ?>
                <div class="day-card <?= ($day['date'] === $current_date) ? 'current-day' : '' ?>">
                    <div class="day-header">
                        <h3><?= translateDayToFrench($day['day_name']) ?></h3>
                        <span class="date"><?= date('d/m/Y', strtotime($day['date'])) ?></span>
                    </div>
                    <div class="day-content">
                        <?php if (empty($day['courses'])): ?>
                            <div class="no-course">
                                <p>Aucun cours prévu</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($day['courses'] as $course): ?>
                                <div class="course-card">
                                    <div class="course-time">
                                        <?= date('H:i', strtotime($course['date_hour_start'])) ?> - 
                                        <?= date('H:i', strtotime($course['date_hour_end'])) ?>
                                    </div>
                                    <div class="course-info">
                                        <h4><?= htmlspecialchars($course['subject_name']) ?></h4>
                                        <p>
                                            <i class="bi bi-person-fill"></i> 
                                            <?= htmlspecialchars($course['teacher_name']) ?>
                                        </p>
                                        <p>
                                            <i class="bi bi-geo-alt-fill"></i> 
                                            <?= htmlspecialchars($course['class_room'] ?? 'Salle non définie') ?>
                                        </p>
                                        <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
                                            <p>
                                                <i class="bi bi-people-fill"></i> 
                                                <?= htmlspecialchars($course['class_name']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>