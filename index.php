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
    <meta name="apple-mobile-web-app-capable" content="yes"> <!-- Active le mode plein écran pour iOS -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"> <!-- Style de la barre d'état -->
    <meta name="apple-mobile-web-app-title" content="Navigation Rapide"> <!-- Nom de l'application sur iOS -->
    <meta name="description" content="Une application rapide et intuitive pour naviguer sur le web.">
    <meta name="theme-color" content="#0078d4"> <!-- Couleur du thème pour les navigateurs modernes -->

    <!-- Icônes pour les appareils Apple -->
    <link rel="apple-touch-icon" href="icons/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="152x152" href="IMG_2952.jpeg">
    <link rel="apple-touch-icon" sizes="180x180" href="IMG_2952.jpeg">
    <link rel="apple-touch-icon" sizes="167x167" href="IMG_2952.jpeg">

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
                <button class="search-button">🔎</button>
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
                <?php
                // Récupérer la photo de profil pour chaque utilisateur
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE email = ?");
                $stmt->execute([$msg['email']]);
                $user = $stmt->fetch();
                $profile_picture = $user['profile_picture'] ?? 'default_profile.png'; // Image par défaut si aucune photo n'existe
                ?>
                <div class="message">
                    <div class="message-header">
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="profile-picture">
                        <p><strong><?php echo htmlspecialchars($msg['email']); ?></strong> a écrit :</p>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small>Posté le <?php echo $msg['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</body>
</html>
