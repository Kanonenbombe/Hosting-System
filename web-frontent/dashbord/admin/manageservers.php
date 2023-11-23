<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Server Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chart-container {
            width: 100%;
            height: 400px;
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
                        <a class="nav-link active" href="manageservers.php">Server Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " aria-current="page" href="allvms.php">VM Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="allgameservers.php">Gameserver Management</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>API IP</th>
                    <th>RAM</th>
                    <th>CPU Kerne</th>
                    <th>Speicher</th>
                    <th>Ping</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Datenbankverbindung
                $servername = "localhost";
                $username = "main-api";
                $password = "jfo[cNXuG-*KmSjW";
                $dbname = "panel";
                $db = new mysqli($servername, $username, $password, $dbname);

                if ($db->connect_error) {
                    die("Verbindung fehlgeschlagen: " . $db->connect_error);
                }

                // Server abfragen
                $sql = "SELECT `ID`, `IP`, `ApiPort`, `ram`, `cpu`, `storage` FROM `servers`";
                $result = $db->query($sql);
                $servers = [];
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $servers[] = $row;
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['IP'] . ':' . $row['ApiPort']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['ram']) . " MB</td>";
                        echo "<td>" . htmlspecialchars($row['cpu']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['storage']) . " GB</td>";
                        echo "<td id='ping-" . $row['ID'] . "'>Wird gemessen...</td>";
                        echo "<td><button class='btn btn-sm btn-danger' onclick='deleteServer(" . $row['ID'] . ")'>Löschen</button></td>";
                        echo "</tr>";
                    }
                }
                $db->close();
                ?>
            </tbody>
        </table>

        <div class="chart-container">
            <canvas id="pingChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const servers = <?php echo json_encode($servers); ?>;
        const ctx = document.getElementById('pingChart').getContext('2d');
        const data = {
            labels: Array(10).fill(''),
            datasets: servers.map(server => ({
                label: server.IP + ':' + server.ApiPort,
                data: [],
                borderColor: `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`,
                tension: 0.1
            }))
        };
        const config = {
            type: 'line',
            data: data,
            options: {}
        };
        const pingChart = new Chart(ctx, config);

        servers.forEach((server, index) => {
            setInterval(async () => {
                const url = `http://${server.IP}:${server.ApiPort}/ping-server/`;
                const start = Date.now();
                try {
                    await fetch(url);
                    const ping = Date.now() - start;
                    data.datasets[index].data.push(ping);
                    if (data.datasets[index].data.length > 10) {
                        data.datasets[index].data.shift();
                    }
                    document.getElementById(`ping-${server.ID}`).textContent = ping + ' ms';
                } catch (error) {
                    data.datasets[index].data.push(null);
                    document.getElementById(`ping-${server.ID}`).textContent = 'Nicht erreichbar';
                }
                pingChart.update();
            }, 10000);
        });


        function deleteServer(serverid) {
            // Implementieren Sie hier die Logik zum Löschen eines Servers
        }
    </script>
</body>
</html>
