<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données postées
    $postData = json_decode(file_get_contents('php://input'), true);
    $fileName = $postData['fileName'];
    $sheetName = $postData['sheetName'];
    $playerName = $postData['playerName'];

    // Chemin vers le fichier Excel
    $filePath = __DIR__ . '/../chemin/vers/votre/dossier/' . $fileName;

    // Vérifier si le fichier existe et est lisible
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Le fichier Excel $fileName n'existe pas."]);
        exit;
    }
    if (!is_readable($filePath)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Le fichier Excel $fileName n'est pas accessible pour lecture."]);
        exit;
    }

    try {
        // Charger le fichier Excel avec PhpSpreadsheet
        $spreadsheet = IOFactory::load($filePath);

        // Charger la feuille spécifique
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (!$sheet) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "La feuille $sheetName n'a pas été trouvée dans le fichier $fileName."]);
            exit;
        }

        // Rechercher la ligne du joueur en fonction du nom
        $playerRow = findPlayerRow($sheet, $playerName);

        if ($playerRow !== null) {
            // Récupérer les données de la ligne du joueur
            $playerStats = [];
            foreach ($sheet->getColumnIterator() as $column) {
                /** @var Cell $cell */
                $cell = $sheet->getCell($column->getColumn() . $playerRow);
                $playerStats[$column->getColumn()] = $cell->getValue();
            }

            // Répondre avec les statistiques du joueur au format JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'playerStats' => $playerStats]);
            exit;
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Joueur non trouvé dans la feuille spécifiée.']);
            exit;
        }
    } catch (Exception $e) {
        // En cas d'erreur, répondre avec un JSON d'erreur
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données du joueur : ' . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

/**
 * Fonction pour trouver la ligne d'un joueur dans une feuille donnée.
 * @param Worksheet $sheet Feuille Excel
 * @param string $playerName Nom du joueur à rechercher
 * @return int|null Retourne le numéro de la ligne ou null si le joueur n'est pas trouvé
 */
function findPlayerRow(Worksheet $sheet, string $playerName): ?int
{
    foreach ($sheet->getRowIterator() as $row) {
        $cell = $sheet->getCell('A' . $row->getRowIndex()); // Supposons que le nom du joueur est dans la colonne A
        if ($cell->getValue() == $playerName) {
            return $row->getRowIndex();
        }
    }
    return null;
}
?>