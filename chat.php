<?php
session_start();
include('db.php');

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

// Récupérer les messages de la conversation
$stmt = $pdo->prepare("SELECT sender_email, message, created_at FROM private_message WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll();

// Ajouter un message à la conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO private_message (conversation_id, sender_email, message) VALUES (?, ?, ?)");
    $stmt->execute([$conversation_id, $user_email, $message]);
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
</head>
<body>
    <div class="container">
        <h1>Conversation avec <?php echo htmlspecialchars($conversation['user1_email'] == $user_email ? $conversation['user2_email'] : $conversation['user1_email']); ?></h1>

        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <?php
                // Vérifier si le message est de l'utilisateur actuel
                $is_current_user = $msg['sender_email'] == $_SESSION['email'];

                ?>
                <div class="message <?php echo $is_current_user ? 'current-user' : 'other-user'; ?>">
                    <div class="message-header">
                        
                        <p><strong><?php echo htmlspecialchars($msg['sender_email']); ?></strong> a écrit :</p>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small>Posté le <?php echo $msg['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="input-bar">
            <form action="chat.php?conversation_id=<?php echo $conversation_id; ?>" method="POST">
                <textarea name="message" placeholder="Écris ton message..." required></textarea>
                <button type="submit">Envoyer</button>
            </form>
        </div>
    </div>
    <div style="margin: 20px 0;">
        <a href="index.php">
            <button class="conversation-button">🏠</button>
        </a>
    </div>
</body>
</html>
