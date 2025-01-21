<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION['email'];

if (!isset($_GET['conversation_id'])) {
    echo "Conversation non spécifiée.";
    exit();
}

$conversation_id = $_GET['conversation_id'];

// Vérifier si la conversation existe
$stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ?");
$stmt->execute([$conversation_id]);
$conversation = $stmt->fetch();

if (!$conversation) {
    echo "Conversation introuvable.";
    exit();
}

// Marquer les messages comme lus
$stmt = $pdo->prepare("
    UPDATE private_message 
    SET is_read = 1 
    WHERE conversation_id = ? 
    AND sender_email != ?
");
$stmt->execute([$conversation_id, $user_email]);

// Récupérer les messages existants
$stmt = $pdo->prepare("SELECT id, sender_email, message, created_at FROM private_message WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversation - Messengeria</title>
    <link rel="stylesheet" href="styles/chatStyle.css">
    <style>
        .messages {
            max-height: 70vh;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Conversation avec <?php echo htmlspecialchars($conversation['user1_email'] == $user_email ? $conversation['user2_email'] : $conversation['user1_email']); ?></h1>

        <div class="messages" id="messageContainer">
            <?php foreach ($messages as $msg): ?>
                <div class="message <?php echo $msg['sender_email'] === $user_email ? 'current-user' : 'other-user'; ?>">
                    <div class="message-header">
                        <p><strong><?php echo htmlspecialchars($msg['sender_email']); ?></strong> :</p>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small><?php echo htmlspecialchars($msg['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="input-bar">
            <form id="sendMessageForm" action="chat.php?conversation_id=<?php echo $conversation_id; ?>" method="POST">
                <textarea name="message" id="messageInput" placeholder="Écris ton message..." required></textarea>
                <button type="submit">➡️</button>
            </form>
        </div>
    </div>
    <div style="margin: 20px 0;">
        <a href="index.php">
            <button class="conversation-button">🏠</button>
        </a>
    </div>

    <!-- JavaScript pour le rafraîchissement automatique -->
    <script>
        let lastMessageId = <?php echo end($messages)['id'] ?? 0; ?>; // ID du dernier message récupéré
        const messageContainer = document.getElementById('messageContainer');

        // Fonction pour récupérer les nouveaux messages
        function fetchNewMessages() {
            fetch(`fetch_messages.php?conversation_id=<?php echo $conversation_id; ?>&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    data.forEach(msg => {
                        // Créer un nouvel élément HTML pour chaque message
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'message ' + (msg.sender_email === "<?php echo $user_email; ?>" ? 'current-user' : 'other-user');
                        messageDiv.innerHTML = `
                            <div class="message-header">
                                <p><strong>${msg.sender_email}</strong> :</p>
                            </div>
                            <p>${msg.message.replace(/\n/g, '<br>')}</p>
                            <small>${msg.created_at}</small>
                        `;
                        messageContainer.appendChild(messageDiv); // Ajouter au conteneur
                    });

                    // Mettre à jour le dernier message ID
                    if (data.length > 0) {
                        lastMessageId = data[data.length - 1].id;
                        // Faire défiler vers le bas
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des messages :', error));
        }

        // Rafraîchir les messages toutes les 3 secondes
        setInterval(fetchNewMessages, 3000);

        // Descendre automatiquement au dernier message lors du chargement initial
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    </script>
</body>
</html>
