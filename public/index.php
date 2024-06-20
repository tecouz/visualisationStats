<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/connect.php';
require_once __DIR__ . '/../include/protect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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

        try {
            // Charger le fichier Excel avec PhpSpreadsheet
            $spreadsheet = IOFactory::load($tempFilePath);

            // Récupérer la liste des noms de toutes les feuilles
            $sheetNames = $spreadsheet->getSheetNames();

            echo "<h2>Noms des joueurs dans le fichier Excel '$fileName' :</h2>";

            // Parcourir toutes les feuilles du fichier Excel
            foreach ($sheetNames as $sheetName) {
                // Charger la feuille spécifique
                $sheet = $spreadsheet->getSheetByName($sheetName);

                // Trouver la colonne contenant les noms des joueurs
                $playerColumn = null;
                foreach ($sheet->getRowIterator() as $row) {
                    foreach ($row->getCellIterator() as $cell) {
                        // Supposons que la première ligne contient les en-têtes
                        if (stripos($cell->getValue(), 'nom_joueur') !== false) {
                            $playerColumn = $cell->getColumn();
                            break 2; // Sortir des boucles foreach
                        }
                    }
                }

                if ($playerColumn === null) {
                    echo "Aucune colonne trouvée pour les noms de joueurs dans la feuille '$sheetName'.<br>";
                    continue;
                }

                // Récupérer les noms des joueurs dans la colonne identifiée
                echo "<h3>Feuille : $sheetName</h3>";
                echo "<ul>";
                foreach ($sheet->getRowIterator() as $row) {
                    $playerName = $sheet->getCell($playerColumn . $row->getRowIndex())->getValue();
                    if (!empty($playerName)) {
                        echo "<li>$playerName</li>";
                    }
                }
                echo "</ul>";
            }

        } catch (Exception $e) {
            echo 'Erreur lors du chargement du fichier Excel ' . $fileName . ' : ' . $e->getMessage() . '<br>';
        }
    }
} else {
    echo 'Aucun fichier n\'a été téléchargé ou méthode non autorisée.';
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sélectionner des Fichiers Excel</title>
</head>

<body>
    <h2>Sélectionner des Fichiers Excel</h2>
    <form action="process.php" method="post" enctype="multipart/form-data">
        <label for="excel_files">Sélectionner des fichiers Excel :</label><br>
        <input type="file" id="excel_files" name="excel_files[]" multiple accept=".xls,.xlsx">
        <br><br>
        <input type="submit" value="Traiter les fichiers Excel">
    </form>
</body>

</html>