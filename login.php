<?php
session_start();

require_once __DIR__ . "/include/connect.php";

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $motDePasse = $_POST['mot_de_passe'];

    // Requête pour récupérer l'utilisateur par son email
    $requete = $db->prepare('SELECT * FROM utilisateurs WHERE email = ?');
    $requete->execute([$email]);
    $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'utilisateur existe et si le mot de passe est correct
    if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
        // Stocker les informations de l'utilisateur dans la session
        $_SESSION['utilisateur'] = $utilisateur;

        // Redirection vers index.html
        header('Location: public/index.php');
        exit();
    } else {
        echo 'Email ou mot de passe incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
</head>

<body>
    <h2>Connexion</h2>
    <form action="login.php" method="post">
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>
        <br><br>
        <label for="mot_de_passe">Mot de passe :</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
        <br><br>
        <input type="submit" value="Se connecter">
    </form>
</body>

</html>