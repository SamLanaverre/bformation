<?php    
include '../../CoBDD/session.php';

// Récupération de l'ID et du rôle de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

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

// Récupération des prochains cours
if ($user_role === 'élève') {
    // Récupérer la classe de l'élève
    $stmt = $pdo->prepare("
        SELECT class_id 
        FROM user 
        WHERE idUser = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data && !empty($user_data['class_id'])) {
        $class_id = $user_data['class_id'];
        
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
                c.room AS class_room,
                (SELECT sig.idsignature FROM signature sig WHERE sig.user_id = :user_id AND sig.schedule_id = s.idschedule) AS signature_id,
                (SELECT sig.signed FROM signature sig WHERE sig.user_id = :user_id AND sig.schedule_id = s.idschedule) AS is_signed
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
                s.schedule_date >= CURDATE()
            ORDER BY 
                s.schedule_date ASC, s.date_hour_start ASC
            LIMIT 3
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    } else {
        // L'élève n'a pas de classe assignée
        $no_class_assigned = true;
        $query = "SELECT 1 FROM dual WHERE 0"; // Requête vide
        $stmt = $pdo->prepare($query);
    }
} elseif ($user_role === 'teacher') {
    // Récupérer les prochains cours du professeur
    $query = "
        SELECT 
            s.idschedule, 
            s.schedule_date, 
            s.date_hour_start, 
            s.date_hour_end, 
            sub.name AS subject_name, 
            c.name AS class_name,
            c.room AS class_room,
            c.idclass AS class_id,
            (SELECT COUNT(*) FROM signature sig WHERE sig.schedule_id = s.idschedule) AS signature_count
        FROM 
            schedule s
        JOIN 
            subject sub ON s.subject_id = sub.id_subject
        JOIN 
            class c ON s.class_id = c.idclass
        WHERE 
            s.teacher_id = :user_id AND
            s.schedule_date >= CURDATE()
        ORDER BY 
            s.schedule_date ASC, s.date_hour_start ASC
        LIMIT 3
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
} else {
    // Pour les autres rôles (admin), montrer les 3 prochains cours de toute l'école
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
            s.schedule_date >= CURDATE()
        ORDER BY 
            s.schedule_date ASC, s.date_hour_start ASC
        LIMIT 3
    ";
    $stmt = $pdo->prepare($query);
}

$stmt->execute();
$upcoming_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../dashboard.css">
</head>

<body>

    <header>
        <?php include '../navbaruser/navbar.php' ?>
    </header>

    
    <div class="container mt-4">
        <div class="header-info text-center">
            <h2 id="date"><?php echo date('d/m/Y'); ?></h2> 
        </div>

        <h1 class="text-center my-4">Cours à venir</h1>

        <?php
        // Afficher les messages de notification
        if (isset($_GET['signed']) && $_GET['signed'] === 'success') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Présence confirmée !</strong> Votre signature a été enregistrée avec succès.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        
        if (isset($_GET['signatures_created']) && $_GET['signatures_created'] === 'success') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Signatures créées !</strong> Les élèves peuvent maintenant confirmer leur présence.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        
        if (isset($_GET['error'])) {
            $error_message = '';
            switch ($_GET['error']) {
                case 'not_available':
                    $error_message = 'La signature n\'est pas disponible pour ce cours.';
                    break;
                case 'already_signed':
                    $error_message = 'Vous avez déjà signé ce cours.';
                    break;
                case 'not_course_time':
                    $error_message = 'Ce n\'est pas encore l\'heure du cours.';
                    break;
                case 'signatures_exist':
                    $error_message = 'Les signatures pour ce cours ont déjà été créées.';
                    break;
                default:
                    $error_message = 'Une erreur est survenue.';
            }
            
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreur !</strong> ' . $error_message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
        ?>

        <div class="row">
            <?php if ((isset($no_class_assigned) && $no_class_assigned) || empty($upcoming_courses)): ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <?php if (isset($no_class_assigned) && $no_class_assigned): ?>
                            Vous n'avez pas encore été assigné à une classe. Veuillez contacter un administrateur.
                        <?php else: ?>
                            Aucun cours à venir pour le moment.
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_courses as $course): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card p-3 shadow-sm">
                            <h2 class="card-title"><?= htmlspecialchars($course['subject_name']) ?></h2>
                            <h5 class="card-subtitle mb-2 text-muted">
                                <?= date('d/m/Y', strtotime($course['schedule_date'])) ?>
                            </h5>
                            <h5 class="card-subtitle mb-2 text-muted">
                                <i class="bi bi-clock"></i> Début: <?= date('H:i', strtotime($course['date_hour_start'])) ?>
                            </h5>
                            <h5 class="card-subtitle mb-2 text-muted">
                                <i class="bi bi-clock"></i> Fin: <?= date('H:i', strtotime($course['date_hour_end'])) ?>
                            </h5>
                            <p class="card-text">
                                <?php if ($user_role === 'élève'): ?>
                                    <strong>Professeur:</strong> <?= htmlspecialchars($course['teacher_name']) ?><br>
                                <?php endif; ?>
                                <strong>Classe:</strong> <?= htmlspecialchars($course['class_name']) ?><br>
                                <strong>Salle:</strong> <?= htmlspecialchars($course['class_room']) ?>
                            </p>
                            
                            <?php
                            // Actions différentes selon le rôle de l'utilisateur
                            if ($user_role === 'élève') {
                                // Pour les élèves : vérifier la signature
                                if ($course['signature_id'] === null) {
                                    // Aucune signature n'a été créée par le professeur
                                    echo '<div class="alert alert-secondary" role="alert">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            Signature non disponible
                                          </div>';
                                } elseif ($course['is_signed'] == 1) {
                                    // L'étudiant a déjà signé
                                    echo '<div class="alert alert-success" role="alert">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            Présence confirmée
                                          </div>';
                                } else {
                                    // Une signature est disponible mais l'étudiant n'a pas encore signé
                                    echo '<a href="../signature.php?id=' . $course['signature_id'] . '" class="btn btn-primary">
                                            <i class="bi bi-pen-fill me-2"></i>
                                            Signer ma présence
                                          </a>';
                                }
                            } elseif ($user_role === 'teacher') {
                                // Pour les professeurs : vérifier si c'est l'heure du cours
                                $current_datetime = date('Y-m-d H:i:s');
                                $course_start = $course['schedule_date'] . ' ' . date('H:i:s', strtotime($course['date_hour_start']));
                                $course_end = $course['schedule_date'] . ' ' . date('H:i:s', strtotime($course['date_hour_end']));
                                
                                // Vérifier si des signatures existent déjà
                                if ($course['signature_count'] > 0) {
                                    // Des signatures ont déjà été créées
                                    echo '<div class="alert alert-info" role="alert">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            ' . $course['signature_count'] . ' signature(s) créée(s)
                                          </div>';
                                } elseif ($current_datetime >= $course_start && $current_datetime <= $course_end) {
                                    // C'est l'heure du cours et aucune signature n'a été créée
                                    echo '<a href="../create_signatures.php?schedule_id=' . $course['idschedule'] . '&class_id=' . $course['class_id'] . '" class="btn btn-success">
                                            <i class="bi bi-clipboard-check me-2"></i>
                                            Lancer les signatures
                                          </a>';
                                } else {
                                    // Ce n'est pas encore l'heure du cours
                                    echo '<button class="btn btn-secondary" disabled>
                                            <i class="bi bi-clock me-2"></i>
                                            En attente du cours
                                          </button>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>