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
    <title>Vserver Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-table tr.disabled-vm > * {
            background-color: #5d5d5d !important;
        }

        .custom-table th, .custom-table td {
            white-space: nowrap;
        }
    </style>
    <style>
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

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    </script>



</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Hosting Service</a>
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
                    <!-- Weitere Links nach Bedarf -->
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
                                <li><a class="dropdown-item" href="/logout.php">Abmelden</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Meine Vserver</h1>

    


        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>IP</th>
                    <th>RAM</th>
                    <th>CPU Kerne</th>
                    <th>Speicher</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $token = $_SESSION['user_token'] ?? 'NO_TOKEN';

                $servername = "localhost";
                $username = "main-api";
                $password = "jfo[cNXuG-*KmSjW";
                $dbname = "panel";
                $db = new mysqli($servername, $username, $password, $dbname);

                if ($db->connect_error) {
                    die("Verbindung fehlgeschlagen: " . $db->connect_error);
                }

                $sql = "SELECT ID FROM users WHERE token = '$token'";
                $result = $db->query($sql);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $userID = $row['ID'];

                    $sql = "SELECT vms.*, IFNULL(disable_reason, '') AS disable_reason FROM vms WHERE ownerID = $userID";
                    $result = $db->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $disabled = $row['disable_reason'] !== '';
                            $disabledClass = $disabled ? "disabled-vm" : "";
                            echo "<tr class='{$disabledClass}'>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['IP']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['ram']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['cpu']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['storage']) . " (MB)</td>";
                            echo "<td>";
                            echo "<button class='btn btn-success btn-sm" . ($disabled ? " disabled" : "") . "' onclick=\"performAction('start', '".$row["name"]."', '".$token."')\">Start</button> ";
                            echo "<button class='btn btn-danger btn-sm" . ($disabled ? " disabled" : "") . "' onclick=\"performAction('stop', '".$row["name"]."', '".$token."')\">Stop</button> ";
                            echo "<button class='btn btn-warning btn-sm" . ($disabled ? " disabled" : "") . "' onclick=\"performAction('restart', '".$row["name"]."', '".$token."')\">Restart</button> ";
                            echo "<button class='btn btn-danger btn-sm' onclick=\"performAction('delete', '".$row["name"]."', '".$token."')\">Löschen</button>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>Keine Vserver gefunden.</td></tr>";
                    }
                } else {
                    echo "<p>Benutzer nicht gefunden oder ungültiger Token.</p>";
                }
                $db->close();
            ?>

            </tbody>
        </table>
    </div>

<script>
    const baseUrl = 'http://127.0.0.1:8000';

    function performAction(action, vmName, token) {
        fetch(`${baseUrl}/${action}-vm/`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ vm_name: vmName, token: token })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Netzwerkantwort war nicht ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.message.includes("disabled")) {
                // Behandeln Sie es als Fehler, wenn die Nachricht "disabled" enthält
                Toast.fire({
                    icon: 'error',
                    title: data.message
                });
            } else {
                // Ansonsten zeigen Sie eine Erfolgsmeldung an
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.fire({
                icon: 'error',
                title: 'Fehler bei der Durchführung der Aktion'
            });
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>
