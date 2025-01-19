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

// Préparer les images de profil et les pronoms pour les utilisateurs
$user_details = [];
foreach ($conversations as $conv) {
    $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];

    // Récupérer les détails de l'autre utilisateur si non déjà stocké
    if (!isset($user_details[$other_user_email])) {
        $stmt = $pdo->prepare("SELECT pronouns, profile_picture FROM users WHERE email = ?");
        $stmt->execute([$other_user_email]);
        $user = $stmt->fetch();
        $user_details[$other_user_email] = [
            'pronouns' => $user['pronouns'] ?? 'Utilisateur',
            'profile_picture' => $user['profile_picture'] ?? 'default_profile.png' // Image par défaut si aucune photo
        ];
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
                    // Identifier l'autre utilisateur dans la conversation
                    $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];

                    // Récupérer les détails de l'utilisateur (pronom et photo de profil)
                    $other_user_details = $user_details[$other_user_email];
                ?>
                <li class="conversation-item">
                    <img src="<?php echo htmlspecialchars($other_user_details['profile_picture']); ?>" alt="Photo de profil" class="profile-picture">
                    <a href="chat.php?conversation_id=<?php echo $conv['id']; ?>">
                        <?php echo htmlspecialchars($other_user_details['pronouns']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <a href="index.php">
        <button class="settings-button">🏠</button>
    </a>
</body>
</html>

