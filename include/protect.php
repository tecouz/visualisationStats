<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../login.php');
    exit();
}
?>