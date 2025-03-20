<?php
session_start();
include('../db.php');

if (!isset($_SESSION['email'])) {
    header('Location: ../login.php');
    exit();
}

$user_email = $_SESSION['email'];

try {
    // Récupérer les conversations de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE user1_email = ? OR user2_email = ?");
    $stmt->execute([$user_email, $user_email]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mettre à jour le statut de l'utilisateur connecté à l'ouverture de la page
    $stmt = $pdo->prepare("UPDATE users SET is_online = TRUE WHERE email = ?");
    $stmt->execute([$user_email]);

    // Préparer les détails des utilisateurs et les messages non lus
    $user_details = [];
    $conversations_with_unread = [];
    foreach ($conversations as $conv) {
        $other_user_email = $conv['user1_email'] == $user_email ? $conv['user2_email'] : $conv['user1_email'];

        // Récupérer les détails de l'autre utilisateur
        if (!isset($user_details[$other_user_email])) {
            $stmt = $pdo->prepare("SELECT pronouns, profile_picture FROM users WHERE email = ?");
            $stmt->execute([$other_user_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user_details[$other_user_email] = [
                    'pronouns' => $user['pronouns'] ?? 'Utilisateur',
                    'profile_picture' => $user['profile_picture'] ?? 'default_profile.png',
                    'email' => $other_user_email
                ];
            }
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
        $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'] ?? 0;

        $conv['unread_count'] = $unread_count;
        $conversations_with_unread[] = $conv;
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
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
                    $details = $user_details[$other_user_email] ?? null;

                    // Récupérer le statut de l'utilisateur
                    $stmt = $pdo->prepare("SELECT is_online FROM users WHERE email = ?");
                    $stmt->execute([$other_user_email]);
                    $is_online = $stmt->fetch(PDO::FETCH_ASSOC)['is_online'] ?? 0;
                ?>
                <?php if ($details): ?>
                    <li class="conversation-item">
                        <div class="user-info" data-email="<?php echo htmlspecialchars($other_user_email); ?>">
                        <img class="profile-picture" src="<?php echo htmlspecialchars($msg['profile_picture'] ? 'uploads/' . $msg['profile_picture'] : 'default-profile.png'); ?>" alt="Photo de profil">
                            <div class="status-indicator <?php echo $is_online ? 'online' : 'offline'; ?>"></div>

                            <a href="chat.php?conversation_id=<?php echo $conv['id']; ?>">
                                <?php echo htmlspecialchars($details['pronouns']); ?>
                            </a>
                        </div>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="notification">(<?php echo $conv['unread_count']; ?> non lus)</span>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <a href="index.php">
        <button class="conversation-button">🏠</button>
    </a>
    <a href="search.php">
        <button class="search-button">+</button>
    </a>
    <script>
         // Met à jour le statut en ligne toutes les 2 minutes
         setInterval(() => {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ update: true })
            })
            .then(response => {
                if (!response.ok) {
                    console.error('Erreur lors de la mise à jour du statut');
                }
            })
            .catch(error => console.error('Erreur :', error));
        }, 120000); // 2 minutes en millisecondes

        // Passe au statut offline lorsque l'utilisateur quitte la page
        window.addEventListener('beforeunload', () => {
            navigator.sendBeacon('set_offline.php'); // Utilisation de sendBeacon pour requête rapide
        });
    </script>
</body>
</html>
