<?php
include '../../CoBDD/index.php'; // Connexion à la BDD

/**
 * Fonction pour ajouter une entrée dans une table.
 */
function addEntry($pdo, $table, $column, $value) {
    $stmt = $pdo->prepare("INSERT INTO $table ($column) VALUES (:value)");
    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
    $stmt->execute();
}

/**
 * Fonction pour supprimer une entrée dans une table par ID.
 */
function deleteEntry($pdo, $table, $id_column, $id_value) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $id_column = :id");
    $stmt->bindParam(':id', $id_value, PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Fonction pour récupérer toutes les entrées d'une table.
 */
function getAllEntries($pdo, $table, $order_column) {
    $stmt = $pdo->query("SELECT * FROM $table ORDER BY $order_column DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Gestion des matières
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_name'])) {
    $subject_name = trim($_POST['subject_name']);
    if (!empty($subject_name)) {
        addEntry($pdo, 'subject', 'name', $subject_name);
    }
}

if (isset($_GET['delete_subject_id'])) {
    deleteEntry($pdo, 'subject', 'id_subject', (int)$_GET['delete_subject_id']);
}

$subjects = getAllEntries($pdo, 'subject', 'id_subject');

// Gestion des classes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_name'])) {
    $class_name = trim($_POST['class_name']);
    if (!empty($class_name)) {
        addEntry($pdo, 'class', 'name', $class_name);
    }
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

<?php
include '../navbaradmin/navbaradmin.php'
?>

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
                <th>ID</th>
                <th>Nom de la matière</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($subjects): ?>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject['id_subject']) ?></td>
                        <td><?= htmlspecialchars($subject['name']) ?></td>
                        <td>
                            <a href="?delete_subject_id=<?= $subject['id_subject'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette matière ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Aucune matière disponible.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h1 class="mt-5 mb-4 text-center">Gestion des Classes</h1>

    <!-- Formulaire d'ajout de classe -->
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" name="class_name" class="form-control" placeholder="Nom de la classe" required>
            <button class="btn btn-success" type="submit">Ajouter</button>
        </div>
    </form>

    <!-- Tableau des classes -->
    <table class="table table-bordered text-center">
        <thead class="bg-purple text-white">
            <tr>
                <th>ID</th>
                <th>Nom de la classe</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($classes): ?>
                <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= htmlspecialchars($class['idclass']) ?></td>
                        <td><?= htmlspecialchars($class['name']) ?></td>
                        <td>
                            <a href="?delete_class_id=<?= $class['idclass'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?');">Supprimer</a>
                        </td>
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
