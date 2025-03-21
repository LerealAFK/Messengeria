document.addEventListener("DOMContentLoaded", function () {
    const notificationSound = new Audio("../sounds/notif.mp3"); // Correctif du chemin

    function fetchNotifications() {
        fetch("../fetch_notifications.php") // S'assurer que le script de récupération fonctionne
            .then(response => response.json())
            .then(messages => {
                if (messages.length > 0) {
                    messages.forEach(msg => showNotification(msg.pronouns, msg.message, msg.link));
                }
            })
            .catch(error => console.error("Erreur de notification :", error));
    }

    function showNotification(pronouns, message, link) {
        // Vérifier si une notification existe déjà
        let existingNotification = document.querySelector(".notification-banner");
        if (existingNotification) existingNotification.remove();

        // Créer la bannière
        let notification = document.createElement("div");
        notification.className = "notification-banner show";
        notification.innerHTML = `<strong>${pronouns} :</strong> ${message}`;

        // Jouer le son
        // Jouer le son si activé
       
        notificationSound.play().catch(err => console.error("Son non joué :", err));
        
        

        // Ajouter au body
        document.body.appendChild(notification);

        // Redirection au clic
        notification.addEventListener("click", () => {
            window.location.href = link;
        });

        // Supprimer après 5 secondes
        setTimeout(() => notification.classList.remove("show"), 5000);
    }

    // Vérifier les messages toutes les 6 secondes
    setInterval(fetchNotifications, 6000);
});
