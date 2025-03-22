<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    exit(); // L'utilisateur n'est pas connecté
}

$user_email = $_SESSION['email'];

// Récupérer les messages non lus uniquement des conversations où l'utilisateur est impliqué
$stmt = $pdo->prepare("
    SELECT pm.id, pm.conversation_id, pm.message, pm.created_at, u.pronouns 
    FROM private_message pm
    JOIN users u ON pm.sender_email = u.email
    JOIN private_conversation pc ON pm.conversation_id = pc.id
    WHERE pm.is_read = 0 
    AND pm.sender_email != ? 
    AND (pc.user1_email = ? OR pc.user2_email = ?)
    ORDER BY pm.created_at DESC
");
$stmt->execute([$user_email, $user_email, $user_email]);
$messages = $stmt->fetchAll();

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
