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
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                                <li><a class="dropdown-item" href="logout.php">Abmelden</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Mein Dashboard</h1>
        <h2>Meine VServer</h2>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>IP</th>
                    <th>RAM (GB)</th>
                    <th>CPU Kerne</th>
                    <th>Speicher (MB)</th>
                    <!-- Weitere Spalten nach Bedarf -->
                </tr>
            </thead>
            <tbody>
            <?php
                $token = $_SESSION['user_token'] ?? 'NO_TOKEN';

                // Datenbankverbindung
                $servername = "localhost";
                $username = "main-api";
                $password = "jfo[cNXuG-*KmSjW";
                $dbname = "panel";

                $db = new mysqli($servername, $username, $password, $dbname);
                if ($db->connect_error) {
                    die("Verbindung fehlgeschlagen: " . $db->connect_error);
                }

                // Abrufen der UserID anhand des Tokens
                $sql = "SELECT ID FROM users WHERE token = '$token'";
                $result = $db->query($sql);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $userID = $row['ID'];

                    // Abfragen der VServer-Daten
                    $sql = "SELECT * FROM vms WHERE ownerID = $userID";
                    $result = $db->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['IP']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['ram']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['cpu']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['storage']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>Keine VServer gefunden.</td></tr>";
                    }

                    // Abfragen der Gameserver-Daten
                    echo "</tbody></table><h2>Meine Gameserver</h2><table class='table table-dark table-striped'><thead><tr><th>Name</th><th>GameID</th><th>IP</th></tr></thead><tbody>";
                    $sql = "SELECT * FROM gameservers WHERE ownerID = $userID";
                    $result = $db->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['GameID']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['IP']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Keine Gameserver gefunden.</td></tr>";
                    }
                } else {
                    echo "<p>Benutzer nicht gefunden oder ungültiger Token.</p>";
                }
                $db->close();
            ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
