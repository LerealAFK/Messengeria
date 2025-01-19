<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$user_email = $_SESSION['email'];

// Récupérer les conversations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM conversations WHERE user1_email = ? OR user2_email = ?");
$stmt->execute([$user_email, $user_email]);
$conversations = $stmt->fetchAll();

// Récupérer les photos de profil associées aux utilisateurs des conversations
$user_pictures = [];
foreach ($conversations as $conv) {
    $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];
    if (!isset($user_pictures[$other_user_email])) {
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE email = ?");
        $stmt->execute([$other_user_email]);
        $user_pictures[$other_user_email] = $stmt->fetchColumn();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Conversations</title>
    <link rel="stylesheet" href="styles/convStyle.css">
</head>
<body>
    <div class="container">
        <div style="margin: 20px 0;">
            <a href="search.php">
                <button class="conversation-button">+</button>
            </a>
        </div>

        <h1>Mes Conversations</h1>
        <ul class="conversation-list">
            <?php foreach ($conversations as $conv): ?>
                <?php 
                    $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];
                    $profile_picture = $user_pictures[$other_user_email] ?? 'default.png'; // Image par défaut si pas de photo
                ?>
                <li class="conversation-item">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="profile-picture">
                    <a href="chat.php?conversation_id=<?php echo $conv['id']; ?>">
                        <?php echo htmlspecialchars($other_user_email); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
