var playerStats = playerStatsData;
var filePlayerNames = filePlayerNamesData;
var fileSheetNames = fileSheetNamesData;

let chart; // Déclarer une variable globale pour stocker l'instance du graphique

const playerCheckboxes = document.querySelectorAll(".player-checkbox");
const sheetCheckboxes = document.querySelectorAll(".sheet-checkbox");
const statCheckboxes = document.querySelectorAll(".stat-checkbox");
const chartTypeElement = document.getElementById("chart-type");

// Fonction pour récupérer les statistiques des joueurs sélectionnés
function getPlayerStats() {
  const stats = [];
  const selectedPlayers = Array.from(
    document.querySelectorAll(".player-checkbox:checked")
  ).map((checkbox) => {
    const fileName = checkbox.dataset.file;
    const playerName = checkbox.value;
    return {
      fileName,
      playerName,
    };
  });
  const selectedSheets = Array.from(
    document.querySelectorAll(".sheet-checkbox:checked")
  ).map((checkbox) => {
    const fileName = checkbox.dataset.file;
    const sheetName = checkbox.value;
    return {
      fileName,
      sheetName,
    };
  });
  const selectedStats = Array.from(
    document.querySelectorAll(".stat-checkbox:checked")
  ).map((checkbox) => checkbox.value);

  for (const { fileName, playerName } of selectedPlayers) {
    const playerSheets = selectedSheets.filter(
      (sheet) => sheet.fileName === fileName
    );

    for (const stat of selectedStats) {
      const statValues = [];

      for (const { sheetName } of playerSheets) {
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
          statValues.push(value);
        }
      }

      if (statValues.length > 0) {
        const average =
          statValues.reduce((a, b) => a + b, 0) / statValues.length;
        stats.push({
          name: playerName,
          value: average,
          stat: stat,
          file: fileName,
        });
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
    type: chartType,
    data: {
      labels: Array.from(new Set(playerStats.map((stat) => stat.stat))), // Étiquettes = statistiques
      datasets: Array.from(
        new Set(playerStats.map((player) => player.name))
      ).map((playerName) => {
        const dataset = {
          label: playerName,
          data: playerStats
            .filter((player) => player.name === playerName)
            .map((player) => player.value),
          backgroundColor: playerColors[playerName] || generateColor(),
          borderColor: playerColors[playerName] || generateColor(),
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
          beginAtZero: true,
        },
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.dataset.label || "";
              const value = context.formattedValue;
              const stat = playerStats[context.dataIndex].stat;
              const sheetName = playerStats[context.dataIndex].sheet;
              const fileName = playerStats[context.dataIndex].file;
              return `${label}: ${value} (${stat} - ${sheetName} - ${fileName})`;
            },
          },
        },
      },
    },
  });
}

// Attacher l'événement onchange aux cases à cocher des joueurs, des feuilles et des statistiques
playerCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", function () {
    displayStats();
  });
});

sheetCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", function () {
    displayStats();
  });
});

statCheckboxes.forEach((checkbox) => {
  checkbox.addEventListener("change", function () {
    displayStats();
  });
});

// Attacher un gestionnaire d'événements change sur le type de graphique
chartTypeElement.addEventListener("change", displayStats);
