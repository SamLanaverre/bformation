<?php    
include '../../CoBDD/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css"> -->
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

        <h1 class="text-center my-4">During The Day</h1>

        <div class="row">
            <!-- Bloc pour Mathematics -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card p-3 shadow-sm">
                    <h2 class="card-title">Mathematics</h2>
                    <h4 class="card-subtitle mb-2 text-muted">Opening Hours :</h4>
                    <h4 class="card-subtitle mb-2 text-muted">Ending Hours :</h4>
                    <button id="Button" class="btn btn-primary">Sign</button>
                </div>
            </div>

            <!-- Bloc pour English -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card p-3 shadow-sm">
                    <h2 class="card-title">English</h2>
                    <h4 class="card-subtitle mb-2 text-muted">Opening Hours :</h4>
                    <h4 class="card-subtitle mb-2 text-muted">Ending hours :</h4>
                    <button id="Button" class="btn btn-primary">Sign</button>
                </div>
            </div>

            <!-- Bloc pour Gilles -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card p-3 shadow-sm">
                    <h2 class="card-title">Gilles</h2>
                    <h4 class="card-subtitle mb-2 text-muted">Opening Hours :</h4>
                    <h4 class="card-subtitle mb-2 text-muted">Ending Hours :</h4>
                    <button id="Button" class="btn btn-primary">Sign</button>
                </div>
            </div>
        </div>
    </div>

    <script src="dashboard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>



</body>
</html>
