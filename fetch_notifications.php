<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    exit(); // L'utilisateur n'est pas connecté
}

$user_email = $_SESSION['email'];

// Récupérer les messages non lus avec les pronoms de l'expéditeur
$stmt = $pdo->prepare("
    SELECT pm.id, pm.conversation_id, pm.message, pm.created_at, u.pronouns 
    FROM private_message pm
    JOIN users u ON pm.sender_email = u.email
    WHERE pm.is_read = 0 
    AND pm.sender_email != ? 
    ORDER BY pm.created_at DESC
");
$stmt->execute([$user_email]);
$messages = $stmt->fetchAll();

// Marquer les messages comme lus immédiatement
if ($messages) {
    $messageIds = array_column($messages, 'id');
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $stmt = $pdo->prepare("UPDATE private_message SET is_read = 1 WHERE id IN ($placeholders)");
    $stmt->execute($messageIds);
}

// Retourner les messages avec le bon lien de conversation
$notifications = array_map(function ($msg) {
    return [
        'id' => $msg['id'],
        'pronouns' => $msg['pronouns'],
        'message' => $msg['message'],
        'created_at' => $msg['created_at'],
        'link' => "chat.php?conversation_id=" . $msg['conversation_id']
    ];
}, $messages);

echo json_encode($notifications);
?>
