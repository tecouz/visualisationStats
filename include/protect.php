<?php

// Démarrer une nouvelle session ou récupérer la session existante
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['utilisateur'])) {
    // Si l'utilisateur n'est pas authentifié, le rediriger vers la page de connexion
    header('Location: ../login.php');

    exit();
}

?>