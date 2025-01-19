<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['email'])) {
    echo "Utilisateur non spécifié.";
    exit();
}

$email = $_GET['email'];

// Vérifier si l'utilisateur existe
$stmt = $pdo->prepare("SELECT email, profile_picture FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "Utilisateur introuvable.";
    exit();
}

// Récupérer la photo de profil de l'utilisateur
$profile_picture = $user['profile_picture'] ?? null;

// Récupérer les messages de l'utilisateur
$stmt = $pdo->prepare("SELECT message, created_at FROM messages WHERE email = ? ORDER BY created_at DESC");
$stmt->execute([$email]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($email); ?></title>
    <link rel="stylesheet" href="styles/profileStyle.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="home-button">🏠</a>
        </div>
        <h1>Profil de : <?php echo htmlspecialchars($email); ?></h1>

        <!-- Affichage de la photo de profil -->
        <div class="profile-picture">
            <?php if ($profile_picture): ?>
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="profile-img">
            <?php else: ?>
                <img src="default-avatar.png" alt="Photo de profil par défaut" class="profile-img">
            <?php endif; ?>
        </div>

        <!-- Bouton pour démarrer une nouvelle conversation -->
        <?php if ($_SESSION['email'] != $email): ?>
            <form action="start_conversation.php" method="POST">
                <input type="hidden" name="recipient_email" value="<?php echo htmlspecialchars($email); ?>">
                <button type="submit" class="start-conversation-button">Nouvelle conversation</button>
            </form>
        <?php endif; ?>

        <h2>Derniers messages :</h2>
        <div class="messages">
            <?php if (empty($messages)): ?>
                <p>Aucun message envoyé.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message" onmouseover="showDeleteButton(this)" onmouseout="hideDeleteButton(this)">
                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <small>Posté le <?php echo $msg['created_at']; ?></small>
                        <?php if ($email === $_SESSION['email']): ?>
                            <form action="delete_message.php" method="POST" class="delete-form">
                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                <button type="submit" class="delete-button">🗑️</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function showDeleteButton(element) {
        const deleteForm = element.querySelector('.delete-form');
        if (deleteForm) {
            deleteForm.style.display = 'block';
        }
    }

    function hideDeleteButton(element) {
        const deleteForm = element.querySelector('.delete-form');
        if (deleteForm) {
            deleteForm.style.display = 'none';
        }
    }
    </script>
</body>
</html>

