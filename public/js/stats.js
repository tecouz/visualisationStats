// Données des statistiques des joueurs, des noms de joueurs par fichier et des noms de feuilles par fichier
var playerStats = playerStatsData;
var filePlayerNames = filePlayerNamesData;
var fileSheetNames = fileSheetNamesData;

// Déclarer une variable globale pour stocker l'instance du graphique
let chart;

// Sélectionner les cases à cocher des joueurs, des feuilles et des statistiques, ainsi que l'élément de sélection du type de graphique
const playerCheckboxes = document.querySelectorAll(".player-checkbox");
const sheetCheckboxes = document.querySelectorAll(".sheet-checkbox");
const statCheckboxes = document.querySelectorAll(".stat-checkbox");
const chartTypeElement = document.getElementById("chart-type");

// Fonction pour récupérer les statistiques des joueurs sélectionnés
function getPlayerStats() {
  const stats = []; // Tableau pour stocker les statistiques des joueurs

  // Récupérer les joueurs sélectionnés à partir des cases à cocher cochées
  const selectedPlayers = Array.from(
    document.querySelectorAll(".player-checkbox:checked")
  ).map((checkbox) => {
    const fileName = checkbox.dataset.file;
    const playerName = checkbox.value;
    return { fileName, playerName };
  });

  // Récupérer les feuilles sélectionnées à partir des cases à cocher cochées
  const selectedSheets = Array.from(
    document.querySelectorAll(".sheet-checkbox:checked")
  ).map((checkbox) => {
    const fileName = checkbox.dataset.file;
    const sheetName = checkbox.value;
    return { fileName, sheetName };
  });

  // Récupérer les statistiques sélectionnées à partir des cases à cocher cochées
  const selectedStats = Array.from(
    document.querySelectorAll(".stat-checkbox:checked")
  ).map((checkbox) => checkbox.value);

  // Boucler sur chaque joueur sélectionné
  for (const { fileName, playerName } of selectedPlayers) {
    const playerSheets = selectedSheets.filter(
      (sheet) => sheet.fileName === fileName
    ); // Filtrer les feuilles pour ce joueur

    // Si aucune feuille n'est sélectionnée pour ce joueur, sélectionner toutes les feuilles disponibles
    if (playerSheets.length === 0) {
      const availableSheets = fileSheetNames[fileName];
      playerSheets.push(
        ...availableSheets.map((sheetName) => ({ fileName, sheetName }))
      );
    }

    // Boucler sur chaque statistique sélectionnée
    for (const stat of selectedStats) {
      const statValues = []; // Tableau pour stocker les valeurs de la statistique

      // Boucler sur chaque feuille sélectionnée pour ce joueur
      for (const { sheetName } of playerSheets) {
        // Vérifier si la statistique existe pour cette feuille et ce joueur
        if (
          playerStats.hasOwnProperty(fileName) &&
          playerStats[fileName].hasOwnProperty(playerName) &&
          playerStats[fileName][playerName]["data"].hasOwnProperty(sheetName) &&
          playerStats[fileName][playerName]["data"][sheetName].hasOwnProperty(
            stat
          )
        ) {
          const value =
            playerStats[fileName][playerName]["data"][sheetName][stat];
          statValues.push(value); // Ajouter la valeur de la statistique au tableau
        }
      }

      // Si des valeurs ont été trouvées pour cette statistique
      if (statValues.length > 0) {
        const average =
          statValues.reduce((a, b) => a + b, 0) / statValues.length; // Calculer la moyenne des valeurs
        stats.push({
          name: playerName,
          value: average,
          stat: stat,
          file: fileName,
        }); // Ajouter la statistique moyenne au tableau
      }
    }
  }

  return stats; // Retourner le tableau des statistiques des joueurs
}

// Fonction pour afficher le graphique
function displayStats() {
  const chartType = chartTypeElement.value; // Récupérer le type de graphique sélectionné
  const playerStats = getPlayerStats(); // Récupérer les statistiques des joueurs

  // Détruire l'instance précédente du graphique, si elle existe
  if (chart) {
    chart.destroy();
  }

  // Générer des couleurs aléatoires pour les joueurs
  const playerColors = {};
  const generateColor = () => {
    const r = Math.floor(Math.random() * 256);
    const g = Math.floor(Math.random() * 256);
    const b = Math.floor(Math.random() * 256);
    return `rgba(${r}, ${g}, ${b}, 0.5)`;
  };

  // Créer le graphique avec Chart.js
  const ctx = document.getElementById("stat-chart").getContext("2d");
  chart = new Chart(ctx, {
    type: chartType, // Type de graphique sélectionné
    data: {
      labels: Array.from(new Set(playerStats.map((stat) => stat.stat))), // Étiquettes = statistiques uniques
      datasets: Array.from(
        new Set(playerStats.map((player) => player.name))
      ).map((playerName) => {
        const dataset = {
          label: playerName, // Nom du joueur
          data: playerStats
            .filter((player) => player.name === playerName)
            .map((player) => player.value), // Valeurs des statistiques pour ce joueur
          backgroundColor: playerColors[playerName] || generateColor(), // Couleur de fond pour ce joueur
          borderColor: playerColors[playerName] || generateColor(), // Couleur de bordure pour ce joueur
          borderWidth: 1,
        };

        // Générer une couleur pour chaque joueur
        if (!playerColors[playerName]) {
          playerColors[playerName] = dataset.backgroundColor;
        }

        return dataset;
      }),
    },
    options: {
      scales: {
        y: {
          beginAtZero: true, // L'axe des ordonnées commence à zéro
        },
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.dataset.label || ""; // Nom du joueur
              const value = context.formattedValue; // Valeur de la statistique
              const stat = playerStats[context.dataIndex].stat; // Nom de la statistique
              const sheetName = playerStats[context.dataIndex].sheet; // Nom de la feuille
              const fileName = playerStats[context.dataIndex].file; // Nom du fichier
              return `${label}: ${value} (${stat} - ${sheetName} - ${fileName})`; // Texte de l'infobulle
            },
          },
        },
      },
    },
  });
}

// Attacher l'événement onchange aux cases à cocher des joueurs
playerCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", function () {
    displayStats(); // Mettre à jour le graphique lorsqu'une case à cocher de joueur est modifiée
  });
});

// Attacher l'événement onchange aux cases à cocher des feuilles
sheetCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", function () {
    displayStats(); // Mettre à jour le graphique lorsqu'une case à cocher de feuille est modifiée
  });
});

// Attacher l'événement onchange aux cases à cocher des statistiques
statCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", function () {
    displayStats(); // Mettre à jour le graphique lorsqu'une case à cocher de statistique est modifiée
  });
});

// Attacher un gestionnaire d'événements change sur le type de graphique
chartTypeElement.addEventListener("change", displayStats); // Mettre à jour le graphique lorsque le type de graphique est modifié
