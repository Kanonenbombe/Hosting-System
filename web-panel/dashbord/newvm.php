<?php
session_start(); // Startet oder setzt die Session fort

// Überprüft, ob der Benutzer angemeldet ist
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login.php'); // Leitet zu login.php um
    exit; // Beendet die Ausführung des Skripts
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VM Erstellung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .slider-label {
            display: block;
            margin-top: 10px;
        }
        .navbar .dropdown-menu {
            right: 0; /* Setzt das Dropdown-Menü rechtsbündig */
            left: auto; /* Verhindert, dass das Menü linksbündig ausgerichtet wird */
        }

        .navbar .dropdown-menu-end {
            right: 0; /* Setzt das Dropdown-Menü rechtsbündig */
            left: auto; /* Verhindert, dass das Menü linksbündig ausgerichtet wird */
        }
        body {
            background: url('img/background_image.png') no-repeat center center fixed;
            background-size: cover;
        }
        .navbar {
            background-color: #343a40; /* Dunkler Hintergrund für die Navbar */
        }
        .navbar-brand, .navbar-nav .nav-link {
            color: #fff; /* Weißer Text für bessere Lesbarkeit */
        }
        .navbar-brand:hover, .navbar-nav .nav-link:hover {
            color: #f8f9fa; /* Etwas heller beim Hover für Interaktionseffekt */
        }
        .card {
            margin-top: 10px;
            margin-bottom: 10px;
            background-color: rgba(255, 255, 255, 0.8); /* Leicht transparenter Hintergrund für Karten */
        }
        .card-title {
            color: #007bff; /* Blaue Titel für eine lebendige Optik */
        }

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/index.php">Hosting Service</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Startseite</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="managevm.php">VServer Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="managegameserver.php">Gameserver Management</a>
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

    <div class="container py-4">
        <h1 class="mb-4">Vserver Miten</h1>

        <form id="vmForm">
            <div class="mb-3">
                <label for="template_name" class="form-label">Template Name</label>
                <input type="text" id="template_name" name="template_name" class="form-control" placeholder="Template Name">
            </div>

            <div class="slider-container">
                <label for="ram" class="slider-label">RAM (GB): <span id="ramValue">1</span> GB</label>
                <input type="range" id="ram" name="ram" min="1" max="64" value="1" class="form-range" oninput="updateSliderValue('ram', 'ramValue')">
            </div>

            <div class="slider-container">
                <label for="cpus" class="slider-label">CPU Kerne: <span id="cpuValue">1</span></label>
                <input type="range" id="cpus" name="cpus" min="1" max="20" value="1" class="form-range" oninput="updateSliderValue('cpus', 'cpuValue')">
            </div>

            <div class="slider-container">
                <label for="storage" class="slider-label">Storage (MB): <span id="storageValue">20000</span> MB</label>
                <input type="range" id="storage" name="storage" min="20000" max="1000000" value="20000" class="form-range" oninput="updateSliderValue('storage', 'storageValue')">
            </div>

            <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($_SESSION['user_token'] ?? 'NO_TOKEN'); ?>">
            <button type="button" class="btn btn-primary" onclick="createVM()">Jetzt Bezahlen</button>
        </form>
    </div>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        function updateSliderValue(sliderId, valueId) {
            const value = document.getElementById(sliderId).value;
            document.getElementById(valueId).textContent = value;
        }

        function createVM() {
            const template_name = document.getElementById('template_name').value;
            const ram = parseInt(document.getElementById('ram').value, 10);
            const cpus = parseInt(document.getElementById('cpus').value, 10);
            const storage = parseInt(document.getElementById('storage').value, 10);
            const token = document.getElementById('token').value;

            const formData = {
                template_name: template_name,
                ram: ram,
                cpus: cpus,
                storage: storage,
                token: token
            };

            Toast.fire({
                icon: 'success',
                title: 'Ihre ' + template_name + ' Vserver mit ' + ram + ' GB RAM, ' + cpus + ' CPU Kernen und ' + storage + ' MB Speicher wird eingerichtet'
            });

            fetch('http://127.0.0.1:8000/select-server-and-create-vm/', {
                method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                Toast.fire({
                    icon: 'success',
                    title: 'VServer erfolgreich Eigerichtet'
                });
            })
            .catch((error) => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Fehler beim Einrichten des VServers'
                });
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>
