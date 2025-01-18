<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $email = $_SESSION['email'];
    $message = $_POST['message'];

    // Insertion du message dans la base de données
    $stmt = $pdo->prepare("INSERT INTO messages (email, message) VALUES (?, ?)");
    $stmt->execute([$email, $message]);
}

// Récupérer tous les messages
$stmt = $pdo->query("SELECT email, message, created_at FROM messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Messengeria</title>
    <link rel="stylesheet" href="styles/indexStyle.css">
</head>
<body>
    <div class="container">
        <!-- index.php -->
        <div style="margin: 20px 0;">
            <a href="conversations.php">
                <button class="conversation-button">💬</button>
            </a>
        </div>

        <h1>Bienvenue sur Messengeria, <?php echo htmlspecialchars($_SESSION['email']); ?> !</h1>
        <!-- Bouton pour rechercher des utilisateurs -->
        <div style="margin: 20px 0;">
            <a href="search.php">
                <button class="search-button">Rechercher un utilisateur</button>
            </a>
        </div>
        <!-- Formulaire d'envoi de message -->
        <form action="index.php" method="POST">
            <textarea name="message" placeholder="Écris un message public..." required></textarea>
            <button type="submit">Envoyer le message</button>
        </form>
        <a href="settings.php">
            <button class="settings-button">⚙️</button>
        </a>

        <!-- Affichage des messages publics -->
        <h2>Messages publics :</h2>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <p><strong><?php echo htmlspecialchars($msg['email']); ?></strong> a écrit :</p>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small>Posté le <?php echo $msg['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
