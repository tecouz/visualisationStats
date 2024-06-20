// Attacher un gestionnaire d'événements clic sur tous les liens de joueurs
document.querySelectorAll(".player-link").forEach((item) => {
  item.addEventListener("click", (event) => {
    event.preventDefault();

    // Récupérer les données nécessaires
    const fileName = item.getAttribute("data-file");
    const sheetName = item.getAttribute("data-sheet");
    const playerName = item.getAttribute("data-player");

    // Effectuer une requête AJAX pour récupérer les statistiques du joueur
    fetch("get_player_stats.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        fileName: fileName,
        sheetName: sheetName,
        playerName: playerName,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        console.log(data); // Vérifiez la réponse reçue
        if (data.success) {
          // Traitement des statistiques du joueur ici
        } else {
          console.error("Erreur :", data.message);
          // Gérer l'affichage de l'erreur côté client
        }
      })
      .catch((error) => {
        console.error(
          "Erreur lors de la récupération des statistiques :",
          error
        );
        // Gérer les erreurs réseau ou autres
      });
  });
});
