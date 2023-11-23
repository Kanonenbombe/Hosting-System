<?php
session_start();
session_destroy(); // Beendet die Session und meldet den Benutzer ab
header("Location: login.php"); // Leitet den Benutzer zurück zur Anmeldeseite
exit;
?>
