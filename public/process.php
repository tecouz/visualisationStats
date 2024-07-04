<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../vendor/autoload.php'; // Autoloader de Composer
require_once __DIR__ . '/../include/connect.php'; // Fichier de connexion à la base de données
require_once __DIR__ . '/../include/protect.php'; // Fichier de protection (probablement pour la sécurité)

// Utiliser la classe IOFactory de PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

// Initialiser les tableaux pour stocker les statistiques, les noms de joueurs et les noms de feuilles
$playerStats = [];
$filePlayerNames = [];
$fileSheetNames = [];

// Vérifier si un formulaire a été soumis avec des fichiers Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_files'])) {
    $files = $_FILES['excel_files']; // Récupérer les fichiers envoyés

    // Boucler sur chaque fichier envoyé
    foreach ($files['tmp_name'] as $index => $tempFilePath) {
        $fileName = $files['name'][$index]; // Récupérer le nom du fichier
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION); // Récupérer l'extension du fichier

        // Vérifier si le fichier est un fichier Excel
        if ($fileType === 'xls' || $fileType === 'xlsx') {
            $spreadsheet = IOFactory::load($tempFilePath); // Charger le fichier Excel dans PhpSpreadsheet
            $sheetNames = $spreadsheet->getSheetNames(); // Récupérer les noms des feuilles de calcul
            $fileSheetNames[$fileName] = []; // Initialiser un tableau pour stocker les noms de feuilles de ce fichier

            // Boucler sur chaque feuille de calcul
            foreach ($sheetNames as $sheetName) {
                $worksheet = $spreadsheet->getSheetByName($sheetName); // Récupérer la feuille de calcul
                $highestRow = $worksheet->getHighestRow(); // Récupérer le numéro de la dernière ligne utilisée

                // Trouver la colonne "Nom Joueur"
                $playerNameColumn = null;
                foreach ($worksheet->getRowIterator(1)->current()->getCellIterator() as $cell) {
                    $cellValue = $cell->getValue();
                    if ($cellValue !== null && trim(strtolower($cellValue)) === 'nom joueur') {
                        $playerNameColumn = $cell->getColumn(); // Stocker la colonne "Nom Joueur"
                        break;
                    }
                }

                // Si la colonne "Nom Joueur" a été trouvée
                if ($playerNameColumn !== null) {
                    $hasData = false;
                    // Vérifier si la feuille contient des données
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $playerName = $worksheet->getCell($playerNameColumn . $row)->getValue();
                        if ($playerName !== null) {
                            $playerName = trim($playerName);
                            $hasData = true;
                            break;
                        }
                    }

                    // Si la feuille contient des données, ajouter son nom au tableau
                    if ($hasData) {
                        $fileSheetNames[$fileName][] = $sheetName;
                    }

                    // Boucler sur chaque ligne de la feuille de calcul
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $playerName = $worksheet->getCell($playerNameColumn . $row)->getValue();
                        if ($playerName !== null) {
                            $playerName = trim($playerName);
                        }

                        $playerData = [];
                        // Boucler sur chaque cellule de la ligne
                        foreach ($worksheet->getRowIterator(1)->current()->getCellIterator() as $cell) {
                            $columnName = $cell->getValue();
                            if ($columnName !== null) {
                                $columnName = trim($columnName);
                            }
                            $columnLetter = $cell->getColumn();
                            // Ignorer les colonnes A, B et C (probablement des colonnes de métadonnées)
                            if ($columnLetter !== $playerNameColumn && $columnLetter !== 'A' && $columnLetter !== 'B' && $columnLetter !== 'C') {
                                $playerData[$columnName] = $worksheet->getCell($columnLetter . $row)->getValue(); // Stocker les données du joueur
                            }
                        }

                        // Si un nom de joueur a été trouvé
                        if (!empty($playerName)) {
                            // Initialiser les tableaux pour ce fichier et ce joueur si nécessaire
                            if (!isset($playerStats[$fileName])) {
                                $playerStats[$fileName] = [];
                                $filePlayerNames[$fileName] = [];
                            }

                            if (!isset($playerStats[$fileName][$playerName])) {
                                $playerStats[$fileName][$playerName] = [
                                    'data' => [],
                                ];
                                $filePlayerNames[$fileName][] = $playerName;
                            }

                            // Stocker les données du joueur pour cette feuille de calcul
                            $playerStats[$fileName][$playerName]['data'][$sheetName] = $playerData;
                        }
                    }
                }
            }
        }
    }
}

// Récupérer les noms des statistiques de manière unique
$columnNames = [];
foreach ($playerStats as $fileStats) {
    foreach ($fileStats as $playerStat) {
        foreach ($playerStat['data'] as $sheetData) {
            $columnNames = array_merge($columnNames, array_keys($sheetData));
        }
    }
}
$columnNames = array_unique($columnNames);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Statistiques des joueurs</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/process.css">
</head>

<body>
    <h1>Sélectionnez les joueurs, les feuilles et les statistiques</h1>

    <?php foreach ($filePlayerNames as $fileName => $playerNames): ?>
    <div class="file-section">
        <h3><?= $fileName ?></h3>
        <div class="checkbox-container">
            <span>Joueurs :</span>
            <?php foreach ($playerNames as $playerName): ?>
            <span>
                <input type="checkbox" name="player[]" value="<?= $playerName ?>" class="player-checkbox"
                    data-file="<?= $fileName ?>">
                <label><?= $playerName ?></label>
            </span>
            <?php endforeach; ?>
        </div>
        <div class="checkbox-container">
            <span>Feuilles :</span>
            <?php foreach ($fileSheetNames[$fileName] as $sheetName): ?>
            <span>
                <input type="checkbox" name="sheets[]" value="<?= $sheetName ?>" class="sheet-checkbox"
                    data-file="<?= $fileName ?>">
                <label><?= $sheetName ?></label>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div>
        <label>Statistiques :</label>
        <div class="checkbox-container">
            <?php foreach ($columnNames as $columnName): ?>
            <span>
                <input type="checkbox" name="stats[]" value="<?= $columnName ?>" class="stat-checkbox">
                <label><?= $columnName ?></label>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <label>Type de graphique :</label>
        <select id="chart-type">
            <option value="bar">Barre</option>
            <option value="line">Ligne</option>
            <option value="pie">Camembert</option>
            <option value="doughnut">Anneau</option>
            <option value="radar">Radar</option>
            <option value="polarArea">Aire polaire</option>
        </select>
    </div>

    <button id="display-stats-btn">Afficher les statistiques</button>
    <div id="stat-chart-container">
        <canvas id="stat-chart"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    var playerStatsData = <?php echo json_encode($playerStats); ?>;
    var filePlayerNamesData = <?php echo json_encode($filePlayerNames); ?>;
    var fileSheetNamesData = <?php echo json_encode($fileSheetNames); ?>;
    </script>
    <script src="js/stats.js"></script>
</body>

</html>