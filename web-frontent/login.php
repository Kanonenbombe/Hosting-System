<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card my-5">
                    <div class="card-body">
                        <h3 class="card-title text-center">Anmelden</h3>
                        <form method="post" action="login.php">
                            <div class="mb-3">
                                <label for="Benutzername" class="form-label">Benutzername:</label>
                                <input type="text" class="form-control" id="Benutzername" name="Benutzername" required>
                            </div>
                            <div class="mb-3">
                                <label for="Password" class="form-label">Passwort:</label>
                                <input type="password" class="form-control" id="Password" name="Password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Anmelden</button>
                            </div>
                        </form>
                        <?php
                            // Verbindung zur Datenbank

                            $servername = "localhost";
                            $username = "main-api";
                            $password = "jfo[cNXuG-*KmSjW";
                            $dbname = "panel";

                            $db = new mysqli($servername, $username, $password, $dbname);

                            if ($db->connect_error) {
                                die("Verbindung fehlgeschlagen: " . $db->connect_error);
                            }

                            session_start();

                            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                                $benutzername = $db->real_escape_string($_POST['Benutzername']);
                                $password = $_POST['Password'];

                                $sql = "SELECT * FROM users WHERE Benutzername = '$benutzername'";
                                $result = $db->query($sql);

                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    if (password_verify($password, $row['Password'])) {
                                        $_SESSION['loggedin'] = true;
                                        $_SESSION['userid'] = $row['ID'];
                                        $_SESSION['user_token'] = $row['token'];
                                        $_SESSION['username'] = $benutzername;
                                        header("Location: index.php");
                                    } else {
                                        echo "Falsches Passwort!";
                                    }
                                } else {
                                    echo "Benutzername existiert nicht!";
                                }
                            }
                            $db->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



