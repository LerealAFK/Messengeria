<?php
session_start();
include('db.php');

if (!isset($_SESSION['email']) || !isset($_POST['recipient_email'])) {
    header('Location: index.php');
    exit();
}

$user_email = $_SESSION['email'];
$recipient_email = $_POST['recipient_email'];

// Vérifier que l'utilisateur ne tente pas de discuter avec lui-même
if ($user_email == $recipient_email) {
    header('Location: index.php');
    exit();
}

// Vérifier si une conversation existe déjà
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE (user1_email = ? AND user2_email = ?) OR (user1_email = ? AND user2_email = ?)");
$stmt->execute([$user_email, $recipient_email, $recipient_email, $user_email]);
$conversation = $stmt->fetch();

if (!$conversation) {
    // Créer une nouvelle conversation
    $stmt = $pdo->prepare("INSERT INTO conversations (user1_email, user2_email) VALUES (?, ?)");
    $stmt->execute([$user_email, $recipient_email]);
    $conversation_id = $pdo->lastInsertId();
} else {
    $conversation_id = $conversation['id'];
}

header('Location: chat.php?conversation_id=' . $conversation_id);
exit();
?>
