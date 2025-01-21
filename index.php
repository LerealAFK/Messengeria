<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si l'utilisateur est mute
$stmt = $pdo->prepare("SELECT is_mute FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user_status = $stmt->fetch();

if ($user_status && $user_status['is_mute']) {
    $error_message = "Vous êtes mute et ne pouvez pas envoyer de message.";
} else {
    // L'ancien code de vérification et d'envoi des messages ici
    if (!empty($message)) {
        $stmt = $pdo->prepare("
            SELECT message, created_at 
            FROM messages 
            WHERE email = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['email']]);
        $last_message = $stmt->fetch();

        // Vérifier si le message est identique ou trop proche dans le temps
        $can_send = true;

        if ($last_message) {
            $last_message_time = strtotime($last_message['created_at']);
            if ($last_message['message'] === $message) {
                $can_send = false;
                $error_message = "Vous ne pouvez pas envoyer deux fois le même message.";
            }
        }

        // Insérer le message si toutes les conditions sont remplies
        if ($can_send) {
            $stmt = $pdo->prepare("INSERT INTO messages (email, message) VALUES (?, ?)");
            $stmt->execute([$_SESSION['email'], $message]);
        }
    } else {
        $error_message = "Le message ne peut pas être vide.";
    }
}


// Récupérer les informations de l'utilisateur connecté (uniquement les pronoms)
$stmt = $pdo->prepare("SELECT pronouns FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$current_user = $stmt->fetch();
$pronouns = $current_user['pronouns'] ?? "Utilisateur";

// Récupérer le nombre de messages non lus
$stmt = $pdo->prepare("
    SELECT COUNT(*) as unread_count 
    FROM private_message pm
    JOIN conversations c ON c.id = pm.conversation_id
    WHERE (c.user1_email = :email OR c.user2_email = :email)
    AND pm.sender_email != :email
    AND pm.is_read = 0
");
$stmt->execute(['email' => $_SESSION['email']]);
$unread_count = $stmt->fetch()['unread_count'];

// Initialiser un message d'erreur (vide par défaut)
$error_message = "";

// Gestion de l'envoi de messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Vérifier si l'utilisateur a déjà envoyé un message identique
        $stmt = $pdo->prepare("
            SELECT message, created_at 
            FROM messages 
            WHERE email = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['email']]);
        $last_message = $stmt->fetch();

        // Vérifier si le message est identique ou trop proche dans le temps
        $can_send = true;

        if ($last_message) {
            $last_message_time = strtotime($last_message['created_at']); // Convertir le timestamp en format UNIX
            if ($last_message['message'] === $message) {
                $can_send = false;
                $error_message = "Vous ne pouvez pas envoyer deux fois le même message.";
            } 
        }

        // Insérer le message si toutes les conditions sont remplies
        if ($can_send) {
            $stmt = $pdo->prepare("INSERT INTO messages (email, message) VALUES (?, ?)");
            $stmt->execute([$_SESSION['email'], $message]);
        }
    } else {
        $error_message = "Le message ne peut pas être vide.";
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
    <link rel="stylesheet" href="styles/indexStyle.css">
    <title>Accueil - Messengeria</title>
</head>
<body>
    <div class="container">
        <!-- Bouton pour accéder aux conversations -->
        <div style="margin: 20px 0;">
            <a href="conversations.php">
                <button class="conversation-button">
                    💬 <?php if ($unread_count > 0): ?>
                        <span class="notification"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </button>
            </a>
        </div>
        <a href="settings.php">
            <button class="settings-button">
                ⚙️ 
            </button>
        </a>

        <!-- Message de bienvenue -->
        <h1>Bienvenue sur Messengeria, <?php echo htmlspecialchars($pronouns); ?> !</h1>

        <!-- Afficher un message d'erreur s'il y en a -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Formulaire ou message selon l'état de l'utilisateur -->
        <?php if ($user_status['is_mute']): ?>
            <div class="error-message">
                Vous êtes mute et ne pouvez pas envoyer de message.
            </div>
        <?php else: ?>
            <form action="index.php" method="POST">
                <textarea name="message" placeholder="Écris un message public..." required></textarea>
                <button type="submit">Envoyer le message</button>
            </form>
        <?php endif; ?>


        <!-- Affichage des messages publics -->
        <h2>Messages publics :</h2>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <div class="message-header">
                        <img class="profile-picture" src="<?php echo htmlspecialchars($msg['profile_picture'] ? 'uploads/' . $msg['profile_picture'] : 'default-profile.png'); ?>"
                             alt="Photo de profil" class="profile-picture">
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
