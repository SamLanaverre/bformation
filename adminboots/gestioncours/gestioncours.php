<?php
include '../../CoBDD/index.php'; // Connexion à la BDD

// Ajouter un emploi du temps
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'], $_POST['class_id'], $_POST['schedule_date'], $_POST['start_hour'], $_POST['end_hour'], $_POST['subject_id'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $class_id = (int)$_POST['class_id'];
    $schedule_date = trim($_POST['schedule_date']);
    $start_hour = trim($_POST['start_hour']);
    $end_hour = trim($_POST['end_hour']);
    $subject_id = (int)$_POST['subject_id'];

    // Vérification que les valeurs sont bien présentes
    if (!empty($teacher_id) && !empty($class_id) && !empty($schedule_date) && !empty($start_hour) && !empty($end_hour) && !empty($subject_id)) {
        // Formater les heures avec la date pour obtenir les timestamps complets
        $start_datetime = $schedule_date . ' ' . $start_hour;
        $end_datetime = $schedule_date . ' ' . $end_hour;

        // Extraire la date (avant l'heure) pour la stocker dans la colonne 'schedule_date'
        $schedule_date_only = substr($schedule_date, 0, 10); // Prendre la partie "YYYY-MM-DD" de la date complète

        // Requête pour insérer dans la table 'schedule'
        $stmt = $pdo->prepare("INSERT INTO schedule (teacher_id, class_id, schedule_date, date_hour_start, date_hour_end, subject_id) 
                               VALUES (:teacher_id, :class_id, :schedule_date, :date_hour_start, :date_hour_end, :subject_id)");
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':schedule_date', $schedule_date_only, PDO::PARAM_STR); // Insérer uniquement la date
        $stmt->bindParam(':date_hour_start', $start_datetime, PDO::PARAM_STR);
        $stmt->bindParam(':date_hour_end', $end_datetime, PDO::PARAM_STR);
        $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

// Modifier un emploi du temps
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'], $_POST['edit_teacher_id'], $_POST['edit_class_id'], $_POST['edit_schedule_date'], $_POST['edit_start_hour'], $_POST['edit_end_hour'], $_POST['edit_subject_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $edit_teacher_id = (int)$_POST['edit_teacher_id'];
    $edit_class_id = (int)$_POST['edit_class_id'];
    $edit_schedule_date = trim($_POST['edit_schedule_date']);
    $edit_start_hour = trim($_POST['edit_start_hour']);
    $edit_end_hour = trim($_POST['edit_end_hour']);
    $edit_subject_id = (int)$_POST['edit_subject_id'];

    // Formater les heures avec la date pour obtenir les timestamps complets
    $edit_start_datetime = $edit_schedule_date . ' ' . $edit_start_hour;
    $edit_end_datetime = $edit_schedule_date . ' ' . $edit_end_hour;

    $stmt = $pdo->prepare("UPDATE schedule 
                           SET teacher_id = :teacher_id, class_id = :class_id, schedule_date = :schedule_date, date_hour_start = :date_hour_start, date_hour_end = :date_hour_end, subject_id = :subject_id 
                           WHERE idschedule = :id");
    $stmt->bindParam(':teacher_id', $edit_teacher_id, PDO::PARAM_INT);
    $stmt->bindParam(':class_id', $edit_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':schedule_date', $edit_schedule_date, PDO::PARAM_STR);
    $stmt->bindParam(':date_hour_start', $edit_start_datetime, PDO::PARAM_STR);
    $stmt->bindParam(':date_hour_end', $edit_end_datetime, PDO::PARAM_STR);
    $stmt->bindParam(':subject_id', $edit_subject_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Supprimer un emploi du temps
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM schedule WHERE idschedule = :id");
    $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Récupérer les emplois du temps
$stmt = $pdo->query("SELECT schedule.idschedule, user.first_name, user.surname, class.name AS class_name, subject.name AS subject_name, schedule.schedule_date, schedule.date_hour_start, schedule.date_hour_end
                     FROM schedule
                     JOIN user ON schedule.teacher_id = user.idUser
                     JOIN class ON schedule.class_id = class.idclass
                     JOIN subject ON schedule.subject_id = subject.id_subject
                     ORDER BY schedule.idschedule DESC");

$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les professeurs
$stmt = $pdo->query("SELECT idUser, first_name, surname FROM user WHERE role = 'teacher' ORDER BY first_name ASC");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les classes
$stmt = $pdo->query("SELECT idclass, name FROM class ORDER BY name ASC");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les matières
$stmt = $pdo->query("SELECT id_subject, name FROM subject ORDER BY name ASC");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Emplois du Temps</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>

<?php include '../navbaradmin.php'; ?>

<main class="container mt-5 pt-5">
    <h1 class="mb-4 text-center">Gestion des Emplois du Temps</h1>

    <!-- Formulaire d'ajout -->
    <form method="POST" onsubmit="return validateForm()" class="mb-4">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="teacher_id" class="form-select" required>
                    <option value="">Sélectionner un professeur</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['idUser'] ?>">
                            <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['surname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="class_id" class="form-select" required>
                    <option value="">Sélectionner une classe</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['idclass'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="subject_id" class="form-select" required>
                    <option value="">Sélectionner une matière</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id_subject'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="schedule_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-2">
                <select name="start_hour" class="form-select" required>
                    <option value="">Heure de début</option>
                    <?php for ($hour = 8; $hour <= 19; $hour++): ?>
                        <option value="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":00" ?>"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":00" ?></option>
                        <option value="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":30" ?>"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":30" ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="end_hour" class="form-select" required>
                    <option value="">Heure de fin</option>
                    <?php for ($hour = 8; $hour <= 19; $hour++): ?>
                        <option value="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":00" ?>"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":00" ?></option>
                        <option value="<?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":30" ?>"><?= str_pad($hour, 2, '0', STR_PAD_LEFT) . ":30" ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Ajouter</button>
            </div>
        </div>
    </form>

    <!-- Tableau des emplois du temps -->
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Professeur</th>
            <th>Classe</th>
            <th>Matière</th>
            <th>Date</th>
            <th>Heure de début</th>
            <th>Heure de fin</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($schedules as $schedule): ?>
            <tr>
                <td><?= htmlspecialchars($schedule['first_name'] . ' ' . $schedule['surname']) ?></td>
                <td><?= htmlspecialchars($schedule['class_name']) ?></td>
                <td><?= htmlspecialchars($schedule['subject_name']) ?></td>
                <td><?= htmlspecialchars($schedule['schedule_date']) ?></td>
                <td><?= date('H:i', strtotime($schedule['date_hour_start'])) ?></td>
                <td><?= date('H:i', strtotime($schedule['date_hour_end'])) ?></td>
                <td>
                    <a href="#" class="btn btn-warning btn-sm">Modifier</a>
                    <form action="gestioncours.php" method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?= $schedule['idschedule'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

</body>
</html>
