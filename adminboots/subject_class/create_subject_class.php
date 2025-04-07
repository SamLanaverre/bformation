<?php
include '../../CoBDD/index.php';
include_once '../../CoBDD/sessionmanage.php';

function addSubject($pdo, $name) {
    $stmt = $pdo->prepare("INSERT INTO subject (name) VALUES (:name)");
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->execute();
}

function addClass($pdo, $name, $room) {
    $stmt = $pdo->prepare("INSERT INTO class (name, room) VALUES (:name, :room)");
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':room', $room, PDO::PARAM_STR);
    $stmt->execute();
}

// Fonction pour supprimer une entrée dans une table par ID
function deleteEntry($pdo, $table, $id_column, $id_value) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_column = :id");
    $stmt->bindParam(':id', $id_value, PDO::PARAM_INT);
    $stmt->execute();
}


// Fonction pour mettre à jour la salle d'une classe

function updateClassRoom($pdo, $class_id, $room) {
    $stmt = $pdo->prepare("UPDATE class SET room = :room WHERE idclass = :id");
    $stmt->bindParam(':room', $room, PDO::PARAM_STR);
    $stmt->bindParam(':id', $class_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Fonction pour récupérer toutes les entrées d'une table

function getAllEntries($pdo, $table, $order_column) {
    $stmt = $pdo->query("SELECT * FROM $table ORDER BY $order_column DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Gestion des matières
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_name'])) {
    $subject_name = trim($_POST['subject_name']);
    if (!empty($subject_name)) {
        addSubject($pdo, $subject_name);
    }
}

if (isset($_GET['delete_subject_id'])) {
    deleteEntry($pdo, 'subject', 'id_subject', (int)$_GET['delete_subject_id']);
}

$subjects = getAllEntries($pdo, 'subject', 'id_subject');

// Gestion des classes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_name'], $_POST['class_room'])) {
    $class_name = trim($_POST['class_name']);
    $class_room = trim($_POST['class_room']);
    if (!empty($class_name)) {
        addClass($pdo, $class_name, $class_room);
    }
}

// Mise à jour de la salle d'une classe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class_id'], $_POST['edit_room'])) {
    $edit_class_id = (int)$_POST['edit_class_id'];
    $edit_room = trim($_POST['edit_room']);
    updateClassRoom($pdo, $edit_class_id, $edit_room);
}

if (isset($_GET['delete_class_id'])) {
    deleteEntry($pdo, 'class', 'idclass', (int)$_GET['delete_class_id']);
}

$classes = getAllEntries($pdo, 'class', 'idclass');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matières et Classes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../admin.css">
</head>
<body>

<?php include '../navbaradmin/navbaradmin.php'; ?>

<main class="container mt-5 pt-5">
    <h1 class="mb-4 text-center">Gestion des Matières</h1>

    <!-- Formulaire d'ajout de matière -->
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" name="subject_name" class="form-control" placeholder="Nom de la matière" required>
            <button class="btn btn-success" type="submit">Ajouter</button>
        </div>
    </form>

    <!-- Tableau des matières -->
    <table class="table table-bordered text-center">
        <thead class="bg-purple text-white">
            <tr>
                <th>Nom de la matière</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($subjects): ?>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject['name']) ?></td>
                        <td>
                            <a href="?delete_subject_id=<?= $subject['id_subject'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette matière ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">Aucune matière disponible.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h1 class="mt-5 mb-4 text-center">Gestion des Classes</h1>

    <!-- Formulaire d'ajout de classe -->
    <form method="POST" class="mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" name="class_name" class="form-control" placeholder="Nom de la classe" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="class_room" class="form-control" placeholder="Numéro de salle" required>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success w-100" type="submit">Ajouter</button>
            </div>
        </div>
    </form>

    <!-- Tableau des classes -->
    <table class="table table-bordered text-center">
        <thead class="bg-purple text-white">
            <tr>
                <th>Nom de la classe</th>
                <th>Numéro de salle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($classes): ?>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <form method="POST">
                            <td><?= htmlspecialchars($class['name']) ?></td>
                            <td>
                                <input type="text" name="edit_room" class="form-control" value="<?= htmlspecialchars($class['room'] ?? '') ?>">
                                <input type="hidden" name="edit_class_id" value="<?= $class['idclass'] ?>">
                            </td>
                            <td>
                                <button type="submit" class="btn btn-primary btn-sm mb-1" title="Enregistrer">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                                <a href="?delete_class_id=<?= $class['idclass'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?');">
                                    <i class="bi bi-trash"></i> Supprimer
                                </a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Aucune classe disponible.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>