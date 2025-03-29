<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}
$user_email = $_SESSION['email'];

// Mettre à jour le statut de l'utilisateur connecté à l'ouverture de la page
$stmt = $pdo->prepare("UPDATE users SET is_online = TRUE WHERE email = ?");
$stmt->execute([$user_email]);


// Déterminer le canal actuel
$current_channel = isset($_GET['channel']) ? $_GET['channel'] : 'general';

// Vérifier si l'utilisateur est mute
$stmt = $pdo->prepare("SELECT is_mute FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user_status = $stmt->fetch();
$is_mute = $user_status['is_mute'] ?? false;

// Initialiser un message d'erreur (vide par défaut)
$error_message = "";

// Gestion de l'envoi de messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Vérifier si l'utilisateur a déjà envoyé un message identique dans ce canal
        $stmt = $pdo->prepare("
            SELECT message, created_at 
            FROM messages 
            WHERE email = ? AND channel = ?
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['email'], $current_channel]);
        $last_message = $stmt->fetch();

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
            $stmt = $pdo->prepare("INSERT INTO messages (email, message, channel) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['email'], $message, $current_channel]);
        }
    } else {
        $error_message = "Le message ne peut pas être vide.";
    }
}

// Récupérer les informations de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT pronouns FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$current_user = $stmt->fetch();
$pronouns = $current_user['pronouns'] ?? "Utilisateur";

// Récupérer les messages pour le canal actuel
$stmt = $pdo->prepare("
    SELECT m.message, m.created_at, u.pronouns, u.profile_picture 
    FROM messages m
    JOIN users u ON m.email = u.email
    WHERE m.channel = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$current_channel]);
$messages = $stmt->fetchAll();

// Récupérer le nombre de messages non lus (chat privé)
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

// Trouver le dernier message reçu
$last_message_id = 0;
if (!empty($messages)) {
  //  $last_message_id = end($messages)['id'];
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/indexStyle.css">
    <title>Accueil - Messengeria</title>
    <link rel="stylesheet" href="styles/notifications.css">
    <script src="scripts/notifications.js" defer></script>

</head>
<body>
    <div class="container">
        <p> Nouveau site: <a href="http://192.168.8.133/" </a></p>
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
        <div class="groupes-button">
            <button class="groupes-conv-button"><a href="groupes.php">👥</a></button>
            
        </div>
        <a href="settings.php">
            <button class="settings-button">⚙️</button>
        </a>

        <!-- Message de bienvenue -->
        <h1>Bienvenue sur Messengeria, <?php echo htmlspecialchars($pronouns); ?> !</h1>

        <!-- Choix des canaux -->
        <div class="channels">
            <a href="index.php?channel=general" class="<?php echo $current_channel == 'general' ? 'active' : ''; ?>"># Général</a>
            <a href="index.php?channel=suggestion" class="<?php echo $current_channel == 'suggestion' ? 'active' : ''; ?>"># Suggestion</a>
            <a href="index.php?channel=patchnote" class="<?php echo $current_channel == 'patchnote' ? 'active' : ''; ?>"># Patchnote </a>             
        </div>

        <!-- Formulaire ou message selon l'état de l'utilisateur -->
        <?php if ($is_mute): ?>
            <div class="error-message">
                Vous êtes mute et ne pouvez pas envoyer de message.
            </div>
        <?php else: ?>
            <form action="index.php?channel=<?php echo htmlspecialchars($current_channel); ?>" method="POST">
                <textarea name="message" placeholder="Écris un message dans #<?php echo htmlspecialchars($current_channel); ?>..." required></textarea>
                <button type="submit">Envoyer le message</button>
            </form>
        <?php endif; ?>

        <!-- Affichage des messages publics -->
        <h2>Canal actuel : #<?php echo ucfirst(htmlspecialchars($current_channel)); ?></h2>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <div class="message-header">
                    <img class="profile-picture" src="<?php echo htmlspecialchars($msg['profile_picture'] ? 'uploads/' . $msg['profile_picture'] : 'default_profile.png'); ?>" alt="Photo de profil">

                        
                        <p><strong><?php echo htmlspecialchars($msg['pronouns'] ?: "Anonyme"); ?></strong> a écrit :</p>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    <small>Posté le <?php echo htmlspecialchars($msg['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Div pour le tutoriel -->
    <div id="tutorial" class="tutorial">
        <div class="tutorial-step" data-step="1">
            <h2>Bienvenue sur Messengeria !</h2>
            <p>Voici où vous pouvez accéder à vos conversations. Cliquez sur 💬 pour voir vos messages privés.</p>
            <button class="next-button">Suivant</button>
        </div>
        <div class="tutorial-step" data-step="2">
            <h2>Paramètres</h2>
            <p>Accédez aux paramètres en cliquant sur ⚙️ pour personnaliser votre expérience.</p>
            <button class="next-button">Suivant</button>
        </div>
        <div class="tutorial-step" data-step="3">
            <h2>Canaux de discussion</h2>
            <p>Utilisez les onglets pour changer de canal de discussion. Amusez-vous bien !</p>
            <button class="close-button">Terminer</button>
        </div>
        <div class="tutorial-step" data-step="4">
            <h2>Videos</h2>
            <p>Envoyez des videos avec vos amis, attention a bien envoyer un message lier.</p>
            <button class="close-button">Terminer</button>
        </div>
    </div>

    <script src="scripts/tutorial.js"></script>
    
    <script>
    let lastMessageId = 0;  // ID du dernier message reçu

    function updateMessages() {
        const conversationId = <?php echo json_encode($conversation_id); ?>;

        fetch(`update.php?conversation_id=${conversationId}&last_message_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }

                const messagesContainer = document.querySelector(".messages");

                data.forEach(msg => {
                    lastMessageId = Math.max(lastMessageId, msg.id); // Mettre à jour l'ID du dernier message

                    // Création du message
                    const messageElement = document.createElement("div");
                    messageElement.classList.add("message");
                    messageElement.innerHTML = `
                        <p><strong>${msg.sender_email}</strong>: ${msg.message}</p>
                        <small>${msg.created_at}</small>
                    `;

                    messagesContainer.appendChild(messageElement);
                });

                // Faire défiler vers le bas pour voir les nouveaux messages
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            })
            .catch(error => console.error("Erreur lors de la récupération des messages:", error));
    }

    // Rafraîchir toutes les 2 secondes
    setInterval(updateMessages, 2000);
</script>
    

</body>
</html>
</html>
