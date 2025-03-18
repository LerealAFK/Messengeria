<?php
session_start();
include('db.php');



if (!isset($_GET['id'])) {
    echo json_encode(["error" => "ID du groupe manquant"]);
    exit();
}

$groupe_id = $_GET['id'];

// Vérification de l'appartenance au groupe
$email = $_SESSION['email'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
$user_id = $user['id'];

$stmt = $pdo->prepare("SELECT * FROM groupe_membres WHERE groupe_id = ? AND user_id = ?");
$stmt->execute([$groupe_id, $user_id]);
if ($stmt->rowCount() == 0) {
    echo json_encode(["error" => "Accès interdit"]);
    exit();
}

$stmt = $pdo->prepare("UPDATE groupe_messages SET is_read = 1 WHERE groupe_id = ? AND user_id != ?");
$stmt->execute([$groupe_id, $user_id]);


// Récupérer les messages existants
$stmt = $pdo->prepare("SELECT gm.message, u.pronouns, gm.created_at 
                       FROM groupe_messages gm 
                       JOIN users u ON gm.user_id = u.id 
                       WHERE gm.groupe_id = ? 
                       ORDER BY gm.created_at ASC");
$stmt->execute([$groupe_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) {
    echo json_encode(["error" => "Aucun message trouvé"]);
    exit();
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO groupe_messages (groupe_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$groupe_id, $user_id, $message]);
    header("Location: groupechat.php?id=" . $groupe_id);
    exit();
}

$stmt = $pdo->prepare("SELECT gr.permissions FROM groupe_roles gr 
JOIN groupe_membres gm ON gr.id = gm.role_id 
WHERE gm.user_id = ? AND gm.groupe_id = ?");
$stmt->execute([$user_id, $groupe_id]);
$role = $stmt->fetch();

$permissions = json_decode($role['permissions'], true);

if (!empty($permissions['supprimer_messages'])) {
    echo '<button onclick="supprimerMessage()">Supprimer</button>';
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat du groupe</title>
    <link rel="stylesheet" href="styles/groupeStyle.css">
</head>
<body>
    <div class="container">
        <h2>Chat du groupe</h2>
        <div class="chat-box" id="messageContainer">
            <?php foreach ($messages as $msg): ?>
                <div class="chat-message">
                    <b><?= htmlspecialchars($msg['pronouns']) ?>:</b> <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <br><small><?= htmlspecialchars($msg['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="text" name="message" placeholder="Écrire un message..." required>
            <button type="submit">Envoyer</button>
        </form>

        <!-- Bouton pour accéder à la gestion du groupe -->
        <a href="groupmanage.php?id=<?= $groupe_id ?>" class="manage-button">⚙️</a>
        <a href="groupes.php">◀️</a>
    </div>

    <script>
        let lastMessageId = <?php echo end($messages)['id'] ?? 0; ?>;
        const messageContainer = document.getElementById('messageContainer');

        function fetchNewMessages() {
            fetch(`live_messages.php?groupe_id=<?php echo $groupe_id; ?>&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                     data.forEach(msg => {
                         // Vérifier si le message existe déjà en cherchant son ID
                         if (!document.querySelector(`#msg-${msg.id}`)) {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'chat-message';
                        messageDiv.id = `msg-${msg.id}`; // ID unique pour éviter les doublons
                        messageDiv.innerHTML = `
                            <b>${msg.pronouns}:</b> ${msg.message.replace(/\n/g, '<br>')}
                            <br><small>${msg.created_at}</small>
                        `;
                        messageContainer.appendChild(messageDiv);
                    }
                });

                // Mettre à jour l'ID du dernier message
                if (data.length > 0) {
                    lastMessageId = data[data.length - 1].id;
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }
            }
        })
        .catch(error => console.error('Erreur :', error));
}

// Rafraîchir les messages toutes les 3 secondes
setInterval(fetchNewMessages, 3000);

// Descendre automatiquement au dernier message au chargement
messageContainer.scrollTop = messageContainer.scrollHeight;

    </script>
</body>
</html>
