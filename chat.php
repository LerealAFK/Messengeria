<?php
session_start();
include('db.php');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION['email'];

// Mettre à jour le statut de l'utilisateur connecté à l'ouverture de la page
$stmt = $pdo->prepare("UPDATE users SET is_online = TRUE WHERE email = ?");
$stmt->execute([$user_email]);

// Vérification des paramètres GET
if (!isset($_GET['conversation_id'])) {
    echo "Conversation non spécifiée.";
    exit();
}

$conversation_id = (int) $_GET['conversation_id'];

// Vérification que la conversation existe
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

// Récupération des messages existants
$stmt = $pdo->prepare("SELECT id, sender_email, message, created_at FROM private_message WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();

// Envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO private_message (conversation_id, sender_email, message) VALUES (?, ?, ?)");
        $stmt->execute([$conversation_id, $user_email, $message]);
    }
    header("Location: chat.php?conversation_id=" . $conversation_id); // Rediriger après l'ajout du message
    exit();
}
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
        .message {
            margin-bottom: 15px;
        }
        .current-user {
            background-color: #d4edda;
            padding: 10px;
            border-radius: 8px;
            text-align: right;
        }
        .other-user {
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Conversation avec <?php echo htmlspecialchars($conversation['user1_email'] == $user_email ? $conversation['user2_email'] : $conversation['user1_email']); ?></h1>

        <div class="messages" id="messageContainer">
            <?php foreach ($messages as $msg): ?>
                <div class="message <?php echo $msg['sender_email'] === $user_email ? 'current-user' : 'other-user'; ?>">
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small><?php echo htmlspecialchars($msg['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="input-bar">
            <form id="sendMessageForm" action="chat.php?conversation_id=<?php echo $conversation_id; ?>" method="POST">
                <textarea name="message" id="messageInput" placeholder="Écris ton message..." required></textarea>
                <button type="submit">-></button>
            </form>
        </div>
    </div>
    <div style="margin: 20px 0;">
        <a href="index.php">
            <button class="conversation-button">🏠</button>
        </a>
    </div>

    <script>
        let lastMessageId = <?php echo end($messages)['id'] ?? 0; ?>; // ID du dernier message
        const messageContainer = document.getElementById('messageContainer');

        // Fonction pour récupérer les nouveaux messages
        function fetchNewMessages() {
            fetch(`fetch_messages.php?conversation_id=<?php echo $conversation_id; ?>&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'message ' + (msg.sender_email === "<?php echo $user_email; ?>" ? 'current-user' : 'other-user');
                            messageDiv.innerHTML = `
                                <p><strong>${msg.sender_email} :</strong></p>
                                <p>${msg.message.replace(/\n/g, '<br>')}</p>
                                <small>${msg.created_at}</small>
                            `;
                            messageContainer.appendChild(messageDiv);
                        });

                        // Mettre à jour le dernier ID de message
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
