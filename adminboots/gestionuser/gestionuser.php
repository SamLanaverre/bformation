<?php
include '../../CoBDD/index.php'; // Connexion à la BDD

// Ajouter un utilisateur avec mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'], $_POST['surname'], $_POST['email'], $_POST['role'], $_POST['password'])) {
    $first_name = trim($_POST['first_name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);

    if (!empty($first_name) && !empty($surname) && !empty($email) && !empty($role) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (first_name, surname, email, role, password) VALUES (:first_name, :surname, :email, :role, :password)");
        $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $stmt->bindParam(':surname', $surname, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $stmt->execute();
    }
}

// Modifier un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'], $_POST['edit_first_name'], $_POST['edit_surname'], $_POST['edit_email'], $_POST['edit_role'])) {
    $edit_id = (int)$_POST['edit_id'];
    $edit_first_name = trim($_POST['edit_first_name']);
    $edit_surname = trim($_POST['edit_surname']);
    $edit_email = trim($_POST['edit_email']);
    $edit_role = trim($_POST['edit_role']);
    $edit_class_id = empty($_POST['edit_class_id']) ? null : (int)$_POST['edit_class_id'];

    $stmt = $pdo->prepare("UPDATE user SET first_name = :first_name, surname = :surname, email = :email, role = :role, class_id = :class_id WHERE idUser = :id");
    $stmt->bindParam(':first_name', $edit_first_name, PDO::PARAM_STR);
    $stmt->bindParam(':surname', $edit_surname, PDO::PARAM_STR);
    $stmt->bindParam(':email', $edit_email, PDO::PARAM_STR);
    $stmt->bindParam(':role', $edit_role, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $edit_class_id, PDO::PARAM_INT);
    $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Supprimer un utilisateur
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM user WHERE idUser = :id");
    $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Récupérer les utilisateurs avec leurs classes
$stmt = $pdo->query("
    SELECT user.idUser, user.first_name, user.surname, user.email, user.role, user.class_id, class.name AS class_name
    FROM user
    LEFT JOIN class ON user.class_id = class.idclass
    ORDER BY user.idUser DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les classes
$stmt = $pdo->query("SELECT * FROM class ORDER BY idclass ASC");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">

</head>
<body>

<?php
include 'navbaradmin.php'
?>

    <main class="container mt-5 pt-5">
        <h1 class="mb-4 text-center">Gestion des Utilisateurs</h1>

        <!-- Formulaire d'ajout d'utilisateur -->
        <form method="POST" class="mb-4">
            <div class="row g-2">
                <div class="col-md-2">
                    <input type="text" name="surname" class="form-control" placeholder="Nom" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="first_name" class="form-control" placeholder="Prénom" required>
                </div>
                <div class="col-md-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select" required>
                        <option value="élève">Élève</option>
                        <option value="teacher">Professeur</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-success" type="submit">Ajouter</button>
            </div>
        </form>

        <!-- Tableau des utilisateurs -->
        <table class="table table-bordered text-center">
            <thead class="bg-purple text-white">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Classe</th>
                    <th>Enregistrer</th>
                    <th>Supprimer</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <form method="POST">
                                <td><input type="text" name="edit_surname" class="form-control" value="<?= htmlspecialchars($user['surname']) ?>" required></td>
                                <td><input type="text" name="edit_first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required></td>
                                <td><input type="email" name="edit_email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></td>
                                <td>
                                    <select name="edit_role" class="form-select" required>
                                        <option value="élève" <?= $user['role'] === 'élève' ? 'selected' : '' ?>>Élève</option>
                                        <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Professeur</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="edit_class_id" class="form-select">
                                        <option value="">Aucune</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= $class['idclass'] ?>" <?= $user['class_id'] == $class['idclass'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($class['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="edit_id" value="<?= $user['idUser'] ?>">
                                    <button type="submit" class="btn btn-link p-0 text-success">
                                        <i class="bi bi-check-circle-fill" style="font-size: 1.2rem;" title="Enregistrer"></i>
                                    </button>
                                </td>
                            </form>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?= $user['idUser'] ?>">
                                    <button type="submit" class="btn btn-link p-0 text-danger" onclick="return confirm('Êtes-vous sûr ?')">
                                        <i class="bi bi-trash-fill" style="font-size: 1.2rem;" title="Supprimer"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Aucun utilisateur disponible.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 