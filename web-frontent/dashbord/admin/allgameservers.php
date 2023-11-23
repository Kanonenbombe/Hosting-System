<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Gameserver Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .btn-margin-right {
            margin-right: 5px;
        }
        .table {
            margin-left: -5%;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="manageservers.php">Server Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="allvms.php">VM Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="allgameservers.php">Gameserver Management</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <table class="table table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>GameID</th>
                    <th>Besitzer-ID</th>
                    <th>IP</th>
                    <th>Server-ID</th>
                    <th>User Token</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
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

                $sql = "SELECT * 
                        FROM gameservers
                        JOIN users ON gameservers.ownerID = users.ID";
                $result = $db->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['GameID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['OwnerID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['IP']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['ServerID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['token']) . "</td>";
                        echo "<td>";
                        echo "<div class='btn-group' role='group'>";
                        echo "<button class='btn btn-sm btn-success btn-margin-right' onclick=\"performAction('start', '".$row["name"]."', '".$row["token"]."')\">Start</button>";
                        echo "<button class='btn btn-sm btn-danger btn-margin-right' onclick=\"performAction('stop', '".$row["name"]."', '".$row["token"]."')\">Stop</button>";
                        echo "<button class='btn btn-sm btn-warning btn-margin-right' onclick=\"performAction('restart', '".$row["name"]."', '".$row["token"]."')\">Restart</button>";
                        echo "<button class='btn btn-sm btn-danger btn-margin-right' onclick=\"performAction('delete', '".$row["name"]."', '".$row["token"]."')\">Löschen</button>";
                        echo "<button class='btn btn-sm btn-secondary' onclick=\"performAction('disable', '".$row["name"]."', '".$row["token"]."')\">Disablen</button>";
                        echo "</div>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='11'>Keine VMs gefunden.</td></tr>";
                }
                $db->close();
            ?>
            </tbody>
        </table>
    </div>

    <script>
        const baseUrl = 'http://127.0.0.1:8000';

        function performAction(action, vmName, token) {
            fetch(`${baseUrl}/${action}-gameserver/`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ vm_name: vmName, token: token }) 
            })
            .then(response => response.json())
            .then(data => alert(JSON.stringify(data)))
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
