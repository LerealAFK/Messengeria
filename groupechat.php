<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Accès interdit"]);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "ID de groupe manquant"]);
    exit();
}

$groupe_id = (int) $_GET['id']; // Sécurisation de l'ID

// Récupérer les messages du groupe
$stmt = $pdo->prepare("SELECT gm.message, u.pronouns, gm.created_at, gm.id FROM groupe_messages gm JOIN users u ON gm.user_id = u.id WHERE gm.groupe_id = ? ORDER BY gm.created_at ASC");
$stmt->execute([$groupe_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) {
    $messages = [["message" => "Aucun message pour l'instant.", "pronouns" => "Système"]];
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

        <form method="POST" action="envoyer_message.php">
            <input type="hidden" name="groupe_id" value="<?= $groupe_id ?>">
            <input type="text" name="message" placeholder="Écrire un message..." required>
            <button type="submit">Envoyer</button>
        </form>

        <a href="groupmanage.php?id=<?= $groupe_id ?>" class="manage-button">⚙️</a>
        <a href="groupes.php">◀️</a>
    </div>

    <script>
        let lastMessageId = <?= end($messages)['id'] ?? 0; ?>;
        const messageContainer = document.getElementById('messageContainer');

        function fetchNewMessages() {
            fetch(`live_messages.php?groupe_id=<?= $groupe_id; ?>&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(msg => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'chat-message';
                            messageDiv.innerHTML = `
                                <b>${msg.pronouns}:</b> ${msg.message.replace(/\n/g, '<br>')}
                                <br><small>${msg.created_at}</small>
                            `;
                            messageContainer.appendChild(messageDiv);
                        });

                        if (data.length > 0) {
                            lastMessageId = data[data.length - 1].id;
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        }
                    }
                })
                .catch(error => console.error('Erreur :', error));
        }

        setInterval(fetchNewMessages, 3000);
    </script>
</body>
</html>
