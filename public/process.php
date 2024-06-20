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

            // Récupérer la liste des noms de feuilles
            $sheetNames = $spreadsheet->getSheetNames();

            // Afficher les noms des joueurs pour chaque feuille
            echo "<h2>Noms des joueurs dans le fichier Excel $fileName :</h2>";
            foreach ($sheetNames as $sheetName) {
                echo "<h3>Feuille : $sheetName</h3>";
                echo "<ul>";

                // Charger la feuille spécifique
                $sheet = $spreadsheet->getSheetByName($sheetName);

                // Rechercher la colonne contenant les noms de joueurs
                $playerColumn = findPlayerColumn($sheet);

                // Si aucune colonne trouvée pour les noms de joueurs
                if ($playerColumn === null) {
                    echo "Aucune colonne trouvée pour les noms de joueurs dans la feuille $sheetName.<br>";
                    echo "</ul><br>";
                    continue;
                }

                // Afficher les noms des joueurs dans cette feuille avec des liens
                foreach ($sheet->getRowIterator() as $row) {
                    if ($sheet->cellExists($playerColumn . $row->getRowIndex())) {
                        $playerName = $sheet->getCell($playerColumn . $row->getRowIndex())->getValue();
                        if (!empty($playerName)) {
                            echo "<li><a href='#' class='player-link' data-file='$fileName' data-sheet='$sheetName' data-player='$playerName'>$playerName</a></li>";
                        }
                    }
                }

                echo "</ul><br>";
            }
        } catch (Exception $e) {
            echo 'Erreur lors du chargement du fichier Excel ' . $fileName . ' : ' . $e->getMessage() . '<br>';
        }
    }
} else {
    echo 'Aucun fichier Excel n\'a été téléchargé ou méthode non autorisée.';
}

/**
 * Fonction pour trouver la colonne contenant les noms de joueurs dans une feuille donnée.
 * @param PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Feuille Excel
 * @return string|null Retourne la lettre de la colonne ou null si aucune colonne trouvée
 */
function findPlayerColumn($sheet)
{
    $playerColumn = null;

    // Rechercher la colonne contenant les noms de joueurs
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            // Supposons que la première ligne contient les en-têtes
            $cellValue = $cell->getValue();
            // Recherche plus large pour les en-têtes de colonne
            if (is_string($cellValue) && (stripos($cellValue, 'nom') !== false || stripos($cellValue, 'joueur') !== false)) {
                $playerColumn = $cell->getColumn();
                return $playerColumn;
            }
        }
    }

    return $playerColumn;
}
?>

<script src="stats.js"></script>