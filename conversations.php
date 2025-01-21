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

// Préparer les détails des utilisateurs et les messages non lus
$user_details = [];
$conversations_with_unread = [];
foreach ($conversations as $conv) {
    $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];

    // Récupérer les détails de l'autre utilisateur
    if (!isset($user_details[$other_user_email])) {
        $stmt = $pdo->prepare("SELECT pronouns, profile_picture FROM users WHERE email = ?");
        $stmt->execute([$other_user_email]);
        $user = $stmt->fetch();
        $user_details[$other_user_email] = [
            'pronouns' => $user['pronouns'] ?? 'Utilisateur',
            'profile_picture' => $user['profile_picture'] ?? 'default_profile.png'
        ];
    }

    // Récupérer le nombre de messages non lus
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS unread_count
        FROM private_message
        WHERE conversation_id = ?
        AND sender_email != ?
        AND is_read = 0
    ");
    $stmt->execute([$conv['id'], $user_email]);
    $unread_count = $stmt->fetch()['unread_count'];

    $conv['unread_count'] = $unread_count;
    $conversations_with_unread[] = $conv;
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
        <h1>Mes Conversations</h1>

        <ul class="conversation-list">
            <?php foreach ($conversations_with_unread as $conv): ?>
                <?php 
                    $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];
                    $details = $user_details[$other_user_email];
                ?>
                <li class="conversation-item">
                    <img class="profile-picture" 
                     src="<?php echo !empty($details['profile_picture']) && file_exists('uploads/' . $details['profile_picture']) 
                         ? 'uploads/' . htmlspecialchars($details['profile_picture']) 
                         : 'uploads/default_profile.png'; ?>" 
                     alt="Photo de profil">

                    <a href="chat.php?conversation_id=<?php echo $conv['id']; ?>">
                        <?php echo htmlspecialchars($details['pronouns']); ?>
                    </a>
                    <?php if ($conv['unread_count'] > 0): ?>
                        <span class="notification">(<?php echo $conv['unread_count']; ?> non lus)</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <a href="index.php">
        <button class="conversation-button">🏠</button>
    </a>
</body>
</html>
