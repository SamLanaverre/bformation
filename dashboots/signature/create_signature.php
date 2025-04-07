<?php
include '../../CoBDD/index.php';
include_once '../../CoBDD/sessionmanage.php';

// Vérification manuelle de la session ici
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../register/register.php");
    exit();
}

// Vérifier que l'utilisateur est un professeur
if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

// Récupérer les IDs depuis l'URL
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$user_id = $_SESSION['user_id'];

// Vérifier si le cours existe et appartient au professeur connecté
$stmt = $pdo->prepare("
    SELECT 
        s.idschedule, 
        s.schedule_date, 
        s.date_hour_start, 
        s.date_hour_end, 
        sub.name AS subject_name, 
        c.name AS class_name,
        c.room AS class_room
    FROM 
        schedule s
    JOIN 
        subject sub ON s.subject_id = sub.id_subject
    JOIN 
        class c ON s.class_id = c.idclass
    WHERE 
        s.idschedule = :schedule_id AND 
        s.teacher_id = :user_id AND
        s.class_id = :class_id
");
$stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
$stmt->execute();
$course = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si des signatures existent déjà pour ce cours
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM signature WHERE schedule_id = :schedule_id
");
$stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
$stmt->execute();
$signatures_exist = $stmt->fetchColumn() > 0;

// Si le cours n'existe pas ou appartient à un autre professeur, rediriger
if (!$course) {
    header("Location: dashboots/dashboard/dashboard.php?error=invalid_course");
    exit();
}

// Si des signatures existent déjà, rediriger
if ($signatures_exist) {
    header("Location: dashboots/dashboard/dashboard.php?error=signatures_exist");
    exit();
}

// Vérifier si c'est l'heure du cours
$current_datetime = date('Y-m-d H:i:s');
$course_start = $course['schedule_date'] . ' ' . date('H:i:s', strtotime($course['date_hour_start']));
$course_end = $course['schedule_date'] . ' ' . date('H:i:s', strtotime($course['date_hour_end']));

if ($current_datetime < $course_start || $current_datetime > $course_end) {
    header("Location: dashboots/dashboard/dashboard.php?error=not_course_time");
    exit();
}

// Récupérer tous les élèves de la classe
$stmt = $pdo->prepare("
    SELECT idUser, first_name, surname, email 
    FROM user 
    WHERE class_id = :class_id AND role = 'élève'
    ORDER BY surname, first_name
");
$stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les élèves marqués comme présents
    $present_students = isset($_POST['present']) ? $_POST['present'] : [];
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // Pour chaque élève présent, créer une signature
        foreach ($students as $student) {
            // Vérifier si l'élève est marqué comme présent
            $is_present = in_array($student['idUser'], $present_students);
            
            // Créer la signature (présent mais non signé)
            if ($is_present) {
                $stmt = $pdo->prepare("
                    INSERT INTO signature (user_id, schedule_id, signed) 
                    VALUES (:user_id, :schedule_id, 0)
                ");
                $stmt->bindParam(':user_id', $student['idUser'], PDO::PARAM_INT);
                $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        // Valider la transaction
        $pdo->commit();
        
        // Rediriger avec un message de succès
        header("Location: ../dashboard/dashboard.php?signatures_created=success");
        exit();
        
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $pdo->rollBack();
        die("Erreur lors de la création des signatures : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lancer les signatures</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Lancer les signatures pour le cours</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h3><?= htmlspecialchars($course['subject_name']) ?></h3>
                            <p class="mb-1">
                                <i class="bi bi-calendar-event"></i> 
                                <strong>Date:</strong> <?= date('d/m/Y', strtotime($course['schedule_date'])) ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-clock"></i> 
                                <strong>Horaires:</strong> 
                                <?= date('H:i', strtotime($course['date_hour_start'])) ?> - 
                                <?= date('H:i', strtotime($course['date_hour_end'])) ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-people"></i> 
                                <strong>Classe:</strong> <?= htmlspecialchars($course['class_name']) ?>
                            </p>
                            <p class="mb-3">
                                <i class="bi bi-geo-alt"></i> 
                                <strong>Salle:</strong> <?= htmlspecialchars($course['class_room']) ?>
                            </p>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <h4>Marquer les élèves présents</h4>
                                <p class="text-muted small">Seuls les élèves cochés comme présents pourront signer ce cours.</p>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                        <label class="form-check-label" for="select-all">
                                            <strong>Sélectionner tous les élèves</strong>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="list-group">
                                    <?php if (empty($students)): ?>
                                        <div class="alert alert-info">
                                            Aucun élève n'est assigné à cette classe.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($students as $student): ?>
                                            <div class="list-group-item">
                                                <div class="form-check">
                                                    <input class="form-check-input student-checkbox" type="checkbox" name="present[]" 
                                                           value="<?= $student['idUser'] ?>" id="student-<?= $student['idUser'] ?>">
                                                    <label class="form-check-label" for="student-<?= $student['idUser'] ?>">
                                                        <?= htmlspecialchars($student['surname'] . ' ' . $student['first_name']) ?>
                                                        <span class="text-muted small">(<?= htmlspecialchars($student['email']) ?>)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="dashboots/dashboard/dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Annuler
                                </a>
                                <?php if (!empty($students)): ?>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i> Créer les signatures
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Fonction pour sélectionner/désélectionner tous les élèves
        document.getElementById('select-all').addEventListener('change', function() {
            const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>