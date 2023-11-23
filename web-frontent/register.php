<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card my-5">
                    <div class="card-body">
                        <h3 class="card-title text-center">Registrieren</h3>
                        <form method="post" action="register.php">
                            <div class="mb-3">
                                <label for="Benutzername" class="form-label">Benutzername:</label>
                                <input type="text" class="form-control" id="Benutzername" name="Benutzername" required>
                            </div>
                            <div class="mb-3">
                                <label for="Password" class="form-label">Passwort:</label>
                                <input type="password" class="form-control" id="Password" name="Password" required>
                            </div>
                            <div class="mb-3">
                                <label for="Email" class="form-label">E-Mail:</label>
                                <input type="email" class="form-control" id="Email" name="Email" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Registrieren</button>
                            </div>
                        </form>
                        <?php
                            session_start();
                            // Verbindung zur Datenbank
                            $servername = "localhost";
                            $username = "main-api";
                            $password = "jfo[cNXuG-*KmSjW";
                            $dbname = "panel";
                            
                            $db = new mysqli($servername, $username, $password, $dbname);
                            
                            if ($db->connect_error) {
                                die("Verbindung fehlgeschlagen: " . $db->connect_error);
                            }
                            
                            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                                $benutzername = $db->real_escape_string($_POST['Benutzername']);
                                $password = password_hash($_POST['Password'], PASSWORD_DEFAULT);
                                $email = $db->real_escape_string($_POST['Email']);
                            
                                // Zufälliger Token mit 20 Bytes, umgewandelt in einen 40-Zeichen hexadezimalen String
                                $token = bin2hex(random_bytes(20));
                            
                                // Verschlüsselung des Tokens mit SHA-256
                                $encryptedToken = hash('sha256', $token);
                                $_SESSION['user_token'] = $encryptedToken;
                            
                                $sql = "INSERT INTO users (Benutzername, Password, Email, token) VALUES ('$benutzername', '$password', '$email', '$encryptedToken')";
                            
                                if ($db->query($sql) === TRUE) {
                                    echo "Neuer Benutzer erfolgreich registriert.";
                                } else {
                                    echo "Fehler: " . $sql . "<br>" . $db->error;
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
