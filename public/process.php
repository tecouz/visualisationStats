<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../include/connect.php';
require_once __DIR__ . '/../include/protect.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$playerStats = [];
$filePlayerNames = [];
$fileSheetNames = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_files'])) {
    $files = $_FILES['excel_files'];

    foreach ($files['tmp_name'] as $index => $tempFilePath) {
        $fileName = $files['name'][$index];
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($fileType === 'xls' || $fileType === 'xlsx') {
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheetNames = $spreadsheet->getSheetNames();
            $fileSheetNames[$fileName] = [];

            foreach ($sheetNames as $sheetName) {
                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $highestRow = $worksheet->getHighestRow();

                // Trouver la colonne "Nom Joueur"
                $playerNameColumn = null;
                foreach ($worksheet->getRowIterator(1)->current()->getCellIterator() as $cell) {
                    $cellValue = $cell->getValue();
                    if ($cellValue !== null && trim(strtolower($cellValue)) === 'nom joueur') {
                        $playerNameColumn = $cell->getColumn();
                        break;
                    }
                }

                if ($playerNameColumn !== null) {
                    $hasData = false;
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $playerName = $worksheet->getCell($playerNameColumn . $row)->getValue();
                        if ($playerName !== null) {
                            $playerName = trim($playerName);
                            $hasData = true;
                            break;
                        }
                    }

                    if ($hasData) {
                        $fileSheetNames[$fileName][] = $sheetName;
                    }

                    for ($row = 2; $row <= $highestRow; $row++) {
                        $playerName = $worksheet->getCell($playerNameColumn . $row)->getValue();
                        if ($playerName !== null) {
                            $playerName = trim($playerName);
                        }

                        $playerData = [];
                        foreach ($worksheet->getRowIterator(1)->current()->getCellIterator() as $cell) {
                            $columnName = $cell->getValue();
                            if ($columnName !== null) {
                                $columnName = trim($columnName);
                            }
                            $columnLetter = $cell->getColumn();
                            if ($columnLetter !== $playerNameColumn && $columnLetter !== 'A' && $columnLetter !== 'B' && $columnLetter !== 'C') {
                                $playerData[$columnName] = $worksheet->getCell($columnLetter . $row)->getValue();
                            }
                        }

                        if (!empty($playerName)) {
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