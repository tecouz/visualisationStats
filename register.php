<?php
require_once __DIR__ . "/include/connect.php";

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nomUtilisateur = $_POST['nom_utilisateur'];
    $email = $_POST['email'];
    $motDePasse = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT); // Hachage du mot de passe
    $cheminAcces = $_POST['chemin_acces'];

    // Préparer la requête d'insertion
    $requete = $db->prepare('INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe, chemin_acces) VALUES (?, ?, ?, ?)');
    $requete->execute([$nomUtilisateur, $email, $motDePasse, $cheminAcces]);

    // Redirection vers la page de connexion
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Créer un compte</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>

<body>
    <h2>Créer un compte</h2>
    <form action="register.php" method="post">
        <label for="nom_utilisateur">Nom d'utilisateur :</label>
        <input type="text" id="nom_utilisateur" name="nom_utilisateur" required>
        <br><br>
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>
        <br><br>
        <label for="mot_de_passe">Mot de passe :</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
        <br><br>
        <label for="chemin_acces">Chemin d'accès :</label>
        <input type="text" id="chemin_acces" name="chemin_acces" required>
        <br><br>
        <input type="submit" value="Créer le compte">
    </form>
</body>

</html>