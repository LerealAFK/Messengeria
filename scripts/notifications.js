document.addEventListener("DOMContentLoaded", function () {
    function fetchNotifications() {
        fetch("fetch_notifications.php")
            .then(response => response.json())
            .then(messages => {
                if (messages.length > 0) {
                    messages.forEach(msg => showNotification(msg.sender_email, msg.message));
                }
            })
            .catch(error => console.error("Erreur de notification :", error));
    }

    function showNotification(sender, message) {
        // Vérifier si une notification existe déjà
        let existingNotification = document.querySelector(".notification-banner");
        if (existingNotification) existingNotification.remove();

        // Créer la bannière
        let notification = document.createElement("div");
        notification.className = "notification-banner show";
        notification.innerHTML = `<strong>${sender} :</strong> ${message}`;

        // Ajouter au body
        document.body.appendChild(notification);

        // Supprimer après 5 secondes ou si on clique
        setTimeout(() => notification.classList.remove("show"), 5000);
        notification.addEventListener("click", () => notification.remove());
    }

    // Vérifier les messages toutes les 6 secondes
    setInterval(fetchNotifications, 6000);
});
