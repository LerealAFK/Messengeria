<?php
session_start();
include('db.php');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION['email'];

// Mettre à jour le statut de l'utilisateur connecté
$stmt = $pdo->prepare("UPDATE users SET is_online = TRUE WHERE email = ?");
$stmt->execute([$user_email]);

// Vérification des paramètres GET
if (!isset($_GET['conversation_id'])) {
    echo "Conversation non spécifiée.";
    exit();
}

$conversation_id = (int)$_GET['conversation_id'];

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
$stmt = $pdo->prepare("SELECT id, sender_email, message, video_path, created_at, is_read 
                       FROM private_message 
                       WHERE conversation_id = ? 
                       ORDER BY created_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']) ?: null;
    $videoPath = null;

    // Création des dossiers si nécessaire
    if (!is_dir('uploads/videos')) {
        mkdir('uploads/videos', 0777, true);
    }

    // Gestion de l'upload de la vidéo
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoTmp = $_FILES['video']['tmp_name'];
        $videoExt = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $allowedVideoExt = ['mp4', 'mov', 'avi'];

        if (in_array($videoExt, $allowedVideoExt)) {
            $videoName = uniqid() . "." . $videoExt;
            $videoPath = 'uploads/videos/' . $videoName;
            if (!move_uploaded_file($videoTmp, $videoPath)) {
                echo "Erreur lors de l'upload de la vidéo.";
                exit();
            }
        } else {
            echo "Extension de vidéo non autorisée.";
            exit();
        }
    }

    // Insérer le message avec vidéo
    $stmt = $pdo->prepare("
        INSERT INTO private_message (conversation_id, sender_email, message, video_path) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$conversation_id, $user_email, $message, $videoPath]);

    header("Location: chat.php?conversation_id=" . $conversation_id);
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
    <link rel="stylesheet" href="styles/notifications.css">
    <script src="scripts/notifications.js" defer></script>
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
            
            <?php if (!empty($msg['message'])): ?>
                <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($msg['video_path'])): ?>
                <video width="320" height="240" controls>
                    <source src="<?php echo htmlspecialchars($msg['video_path']); ?>" type="video/mp4">
                    Votre navigateur ne supporte pas la lecture des vidéos.
                </video>
            <?php endif; ?>

            <small><?php echo htmlspecialchars($msg['created_at']); ?></small>

            <!-- Affichage du statut du message -->
            <?php if ($msg['is_read'] == 1): ?>
                <small style="color: green;">Message vu</small>
            <?php else: ?>
                <small style="color: red;">Message non vu</small>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
</div>
    
        <form id="sendMessageForm" action="chat.php?conversation_id=<?php echo $conversation_id; ?>" method="POST" enctype="multipart/form-data">
            <textarea name="message" id="messageInput" placeholder="Écris ton message..."></textarea>

            <button type="submit">Envoyer</button>
        </form>

    </div>
    <div style="margin: 20px 0;">
        <a href="conversations.php">
            <button class="conversation-button">🏠</button>
        </a>
    </div>
    <script>
        const sendMessageForm = document.getElementById('sendMessageForm');
        const messageInput = document.getElementById('messageInput');

    // Écouter l'événement 'keypress' sur le champ de texte
        messageInput.addEventListener('keypress', function(event) {    
        // Vérifier si la touche pressée est 'Entrée' (keyCode 13)
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault(); // Empêcher l'ajout de nouvelle ligne
            sendMessageForm.submit(); // Soumettre le formulair
        }
        });
    </script>


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
