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
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .checkbox-container>span {
            margin-right: 5px;
        }

        .file-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
        }
    </style>
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
    <div>
        <canvas id="stat-chart"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const playerStats = <?php echo json_encode($playerStats); ?>;
        const filePlayerNames = <?php echo json_encode($filePlayerNames); ?>;
        const fileSheetNames = <?php echo json_encode($fileSheetNames); ?>;

        let chart; // Déclarer une variable globale pour stocker l'instance du graphique
        const playerCheckboxes = document.querySelectorAll('.player-checkbox');
        const sheetCheckboxes = document.querySelectorAll('.sheet-checkbox');
        const statCheckboxes = document.querySelectorAll('.stat-checkbox');
        const chartTypeElement = document.getElementById('chart-type');

        // Fonction pour récupérer les statistiques des joueurs sélectionnés
        function getPlayerStats() {
            const stats = [];
            const selectedPlayers = Array.from(document.querySelectorAll('.player-checkbox:checked')).map(checkbox => {
                const fileName = checkbox.dataset.file;
                const playerName = checkbox.value;
                return {
                    fileName,
                    playerName
                };
            });
            const selectedSheets = Array.from(document.querySelectorAll('.sheet-checkbox:checked')).map(checkbox => {
                const fileName = checkbox.dataset.file;
                const sheetName = checkbox.value;
                return {
                    fileName,
                    sheetName
                };
            });
            const selectedStats = Array.from(document.querySelectorAll('.stat-checkbox:checked')).map(checkbox => checkbox
                .value);

            for (const {
                fileName,
                playerName
            } of selectedPlayers) {
                for (const {
                    sheetName
                } of selectedSheets.filter(sheet => sheet.fileName === fileName)) {
                    if (playerStats.hasOwnProperty(fileName) && playerStats[fileName].hasOwnProperty(playerName) &&
                        playerStats[fileName][playerName]['data'].hasOwnProperty(sheetName)) {
                        const sheetData = playerStats[fileName][playerName]['data'][sheetName];
                        for (const stat of selectedStats) {
                            if (sheetData.hasOwnProperty(stat)) {
                                stats.push({
                                    name: playerName,
                                    value: sheetData[stat],
                                    sheet: sheetName,
                                    file: fileName,
                                    stat: stat
                                });
                            }
                        }
                    }
                }
            }
            return stats;
        }

        // Fonction pour afficher le graphique
        function displayStats() {
            const chartType = chartTypeElement.value;
            const playerStats = getPlayerStats();

            // Détruire l'instance précédente du graphique, si elle existe
            if (chart) {
                chart.destroy();
            }

            // Créer le graphique avec Chart.js
            const ctx = document.getElementById('stat-chart').getContext('2d');
            chart = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: playerStats.map(player => player.name), // Afficher uniquement le nom du joueur
                    datasets: [{
                        label: 'Statistiques',
                        data: playerStats.map(player => player.value),
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.dataset.label || '';
                                    const value = context.formattedValue;
                                    const playerName = context.label;
                                    const stat = playerStats[context.dataIndex].stat;
                                    const sheetName = playerStats[context.dataIndex].sheet;
                                    const fileName = playerStats[context.dataIndex].file;
                                    return `${label}: ${value} (${playerName} - ${stat} - ${sheetName} - ${fileName})`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Attacher l'événement onchange aux cases à cocher des joueurs, des feuilles et des statistiques
        playerCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                displayStats();
            });
        });
        sheetCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                displayStats();
            });
        });
        statCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                displayStats();
            });
        });

        // Attacher un gestionnaire d'événements change sur le type de graphique
        chartTypeElement.addEventListener('change', displayStats);
    </script>
</body>

</html>