<?php
$dbhost = "localhost"; // ou votre adresse IP
$username = "root"; // nom d'utilisateur
$password = ""; // MDP
$dbname = "OVC"; // nom de la base de données

try {
    $db = new PDO("mysql:host=" . $dbhost . ";dbname=" . $dbname . ";charset=utf8", $username, $password);
} catch (Exception $e) {
    die("Erreur :" . $e->getMessage());
}
?>