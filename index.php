<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'utilisateur connecté (uniquement les pronoms)
$stmt = $pdo->prepare("SELECT pronouns FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$current_user = $stmt->fetch();
$pronouns = $current_user['pronouns'] ?? "Utilisateur";

// Gestion de l'envoi de messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (email, message) VALUES (?, ?)");
        $stmt->execute([$_SESSION['email'], $message]);
    }
}

// Récupérer tous les messages publics
$stmt = $pdo->query("
    SELECT m.message, m.created_at, u.pronouns, u.profile_picture 
    FROM messages m
    JOIN users u ON m.email = u.email
    ORDER BY m.created_at DESC
");
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Messengeria">
    <meta name="theme-color" content="#0078d4">
    <link rel="stylesheet" href="styles/indexStyle.css">
    <title>Accueil - Messengeria</title>
</head>
<body>
    <div class="container">
        <!-- Bouton pour accéder aux conversations -->
        <div style="margin: 20px 0;">
            <a href="conversations.php">
                <button class="conversation-button">💬</button>
            </a>
        </div>

        <!-- Message de bienvenue -->
        <h1>Bienvenue sur Messengeria, <?php echo htmlspecialchars($pronouns); ?> !</h1>

        <!-- Bouton pour rechercher des utilisateurs -->
        <div style="margin: 20px 0;">
            <a href="search.php">
                <button class="search-button">🔎</button>
            </a>
        </div>

        <!-- Formulaire d'envoi de message public -->
        <form action="index.php" method="POST">
            <textarea name="message" placeholder="Écris un message public..." required></textarea>
            <button type="submit">Envoyer le message</button>
        </form>

        <!-- Bouton pour accéder aux paramètres -->
        <a href="settings.php">
            <button class="settings-button">⚙️</button>
        </a>

        <!-- Affichage des messages publics -->
        <h2>Messages publics :</h2>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <?php 
                // Déterminer l'image de profil à afficher
                $profile_picture = $msg['profile_picture'] ?? 'default_profile.png';
                ?>
                <div class="message">
                    <div class="message-header">
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="profile-picture">
                        <p><strong><?php echo htmlspecialchars($msg['pronouns'] ?: "Anonyme"); ?></strong> a écrit :</p>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small>Posté le <?php echo htmlspecialchars($msg['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
