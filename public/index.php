<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/connect.php';
require_once __DIR__ . '/../include/protect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Récupérer le chemin d'accès de l'utilisateur connecté
$userId = $_SESSION['utilisateur']['id'];
$stmt = $db->prepare('SELECT chemin_acces FROM utilisateurs WHERE id = ?');
$stmt->execute([$userId]);
$userPath = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_files'])) {
    $files = $_FILES['excel_files'];

    foreach ($files['tmp_name'] as $index => $tempFilePath) {
        $fileName = $files['name'][$index];

        // Vérifier si le fichier est bien un fichier Excel
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileType !== 'xls' && $fileType !== 'xlsx') {
            echo "Le fichier $fileName n'est pas un fichier Excel. Ignoré.<br>";
            continue;
        }

        // Déplacer le fichier téléchargé vers le chemin d'accès de l'utilisateur
        $targetPath = $userPath . '/' . $fileName;
        if (move_uploaded_file($tempFilePath, $targetPath)) {
            echo "Le fichier $fileName a été déplacé vers $userPath avec succès.<br>";
        } else {
            echo "Une erreur s'est produite lors du déplacement du fichier $fileName.<br>";
        }
    }

    // Rediriger l'utilisateur vers une page affichant le contenu du chemin d'accès
    header('Location: view_files.php?dir=' . urlencode($userPath));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sélectionner des Fichiers Excel</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>

<body>
    <h2>Sélectionner des Fichiers Excel</h2>
    <form action="process.php" method="post" enctype="multipart/form-data">
        <label for="excel_files">Sélectionner des fichiers Excel :</label><br>
        <input type="file" id="excel_files" name="excel_files[]" multiple accept=".xls,.xlsx"
            data-path="<?php echo htmlspecialchars($userPath); ?>">
        <br><br>
        <input type="submit" value="Traiter les fichiers Excel">
    </form>
</body>

</html>