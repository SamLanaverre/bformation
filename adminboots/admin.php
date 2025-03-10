<?php    
include '../CoBDD/index.php';
?>  

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<?php
include 'navbaradmin.php'
?>

    <!-- Le contenu du tableau de bord -->
    <main class="container mt-5 pt-5">
        <section class="statistics row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card text-center bg-purple text-white">
                    <div class="card-body">
                        <h2 class="card-title">Professeurs</h2>
                        <p class="card-text">50 inscrits</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card text-center bg-purple text-white">
                    <div class="card-body">
                        <h2 class="card-title">Étudiants</h2>
                        <p class="card-text">200 inscrits</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card text-center bg-purple text-white">
                    <div class="card-body">
                        <h2 class="card-title">Visiteurs</h2>
                        <p class="card-text">30 inscrits</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="actions text-center">
            <h2>Résumé des activités récentes</h2>
            <p>Activités récentes : Connexions d'utilisateurs, Ajout d'un étudiant, etc.</p>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
