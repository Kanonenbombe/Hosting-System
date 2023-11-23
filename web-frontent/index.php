<?php
session_start(); // Startet oder setzt die Session fort

// Überprüft, ob der Benutzer angemeldet ist
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); // Leitet zu login.php um
    exit; // Beendet die Ausführung des Skripts
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Startseite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
        body {
            background: url('img/background_image.png') no-repeat center center fixed;
            background-size: cover;
        }
        .card {
            margin-top: 10px; /* Abstand oben */
            margin-bottom: 10px; /* Abstand unten */
        }

        /* Scrollbar-Design */
        ::-webkit-scrollbar {
            width: 0px; /* Breite der Scrollbar */
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1; /* Hintergrundfarbe der Scrollbar-Bahn */
        }

        ::-webkit-scrollbar-thumb {
            background: #888; /* Farbe der Scrollbar selbst */
            border-radius: 6px; /* Abrunden der Ecken */
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555; /* Farbe der Scrollbar beim Hover */
        }
        .navbar .dropdown-menu {
            right: 0; /* Setzt das Dropdown-Menü rechtsbündig */
            left: auto; /* Verhindert, dass das Menü linksbündig ausgerichtet wird */
        }

        .navbar .dropdown-menu-end {
            right: 0; /* Setzt das Dropdown-Menü rechtsbündig */
            left: auto; /* Verhindert, dass das Menü linksbündig ausgerichtet wird */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="">Hosting Service</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashbord">dashbord</a>
                    </li>

                </ul>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#">Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Abmelden</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Beispiel für eine Karte -->
            <div class="col-md-4">
                <div class="card">
                    <img src="img/vm_image.jpg" class="card-img-top" alt="VM">
                    <div class="card-body">
                        <h5 class="card-title">Vserver</h5>
                        <p class="card-text">Miten Sie schnell und einfach eine neue Vserver. Der server ist in unter 60 sekunden einsatzbereit</p>
                        <a href="dashbord/newvm.php" class="btn btn-primary">Jetzt Mieten</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <img src="img/root_server_image.jpg" class="card-img-top" alt="Root Server">
                    <div class="card-body">
                        <h5 class="card-title">Root Server</h5>
                        <p class="card-text">Hochleistungs-Root-Server für maximale Kontrolle und Flexibilität.</p>
                        <a href="dashbord/newrootserver.php" class="btn btn-primary">Jetzt Mieten</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <img src="img/gameserver_image.jpg" class="card-img-top" alt="Gameserver">
                    <div class="card-body">
                        <h5 class="card-title">Gameserver</h5>
                        <p class="card-text">Zuverlässige und schnelle Gameserver für ein optimales Spielerlebnis.</p>
                        <a href="dashbord/newgameserver.php" class="btn btn-primary">Jetzt Mieten</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <img src="img/discord_bot_image.jpg" class="card-img-top" alt="Discord Bot">
                    <div class="card-body">
                        <h5 class="card-title">Discord Bot</h5>
                        <p class="card-text">Fertige Discord Bots von uns oder von ihnen für Ihre Community oder Ihr Business. Hosten</p>
                        <a href="dashbord/newdiscordbot.php" class="btn btn-primary">Jetzt Mieten</a>
                    </div>
                </div>
            </div>

            



        </div>
    </div>

    <footer class="footer bg-dark text-light mt-4">
        <div class="container text-center py-3">
            <p><a href="datenschutz.html" class="text-light">Datenschutz</a> | <a href="impressum.html" class="text-light">Impressum</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
