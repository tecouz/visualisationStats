<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../vendor/autoload.php'; // Autoloader de Composer
require_once __DIR__ . '/../include/connect.php'; // Fichier de connexion à la base de données
require_once __DIR__ . '/../include/protect.php'; // Fichier de protection (probablement pour la sécurité)

// Utiliser la classe IOFactory de PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

// Récupérer le chemin d'accès de l'utilisateur connecté
$userId = $_SESSION['utilisateur']['id']; // Récupérer l'ID de l'utilisateur à partir de la session
$stmt = $db->prepare('SELECT chemin_acces FROM utilisateurs WHERE id = ?'); // Préparer la requête SQL
$stmt->execute([$userId]); // Exécuter la requête avec l'ID de l'utilisateur
$userPath = $stmt->fetchColumn(); // Récupérer le chemin d'accès de l'utilisateur

// Vérifier si un formulaire a été soumis avec des fichiers Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_files'])) {
    $files = $_FILES['excel_files']; // Récupérer les fichiers envoyés

    // Boucler sur chaque fichier envoyé
    foreach ($files['tmp_name'] as $index => $tempFilePath) {
        $fileName = $files['name'][$index]; // Récupérer le nom du fichier

        // Vérifier si le fichier est bien un fichier Excel
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION); // Récupérer l'extension du fichier
        if ($fileType !== 'xls' && $fileType !== 'xlsx') {
            echo "Le fichier $fileName n'est pas un fichier Excel. Ignoré.<br>"; // Afficher un message d'erreur
            continue; // Passer au fichier suivant
        }

        // Déplacer le fichier téléchargé vers le chemin d'accès de l'utilisateur
        $targetPath = $userPath . '/' . $fileName; // Construire le chemin de destination
        if (move_uploaded_file($tempFilePath, $targetPath)) {
            echo "Le fichier $fileName a été déplacé vers $userPath avec succès.<br>"; // Afficher un message de succès
        } else {
            echo "Une erreur s'est produite lors du déplacement du fichier $fileName.<br>"; // Afficher un message d'erreur
        }
    }

    // Rediriger l'utilisateur vers une page affichant le contenu du chemin d'accès
    header('Location: view_files.php?dir=' . urlencode($userPath)); // Rediriger vers la page view_files.php avec le chemin d'accès en paramètre
    exit(); // Arrêter l'exécution du script
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sélectionner des Fichiers Excel</title>
    <link rel="stylesheet" href="/public/css/style.css"> <!-- Lier le fichier CSS -->
</head>

<body>
    <h2>Sélectionner des Fichiers Excel</h2>
    <form action="process.php" method="post" enctype="multipart/form-data">
        <!-- Formulaire pour envoyer des fichiers Excel -->
        <label for="excel_files">Sélectionner des fichiers Excel :</label><br>
        <input type="file" id="excel_files" name="excel_files[]" multiple accept=".xls,.xlsx"
            data-path="<?php echo htmlspecialchars($userPath); ?>"> <!-- Champ pour sélectionner les fichiers Excel -->
        <br><br>
        <input type="submit" value="Traiter les fichiers Excel"> <!-- Bouton pour soumettre le formulaire -->
    </form>
</body>

</html>