<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    exit(); // L'utilisateur n'est pas connecté
}

$user_email = $_SESSION['email'];

// Récupérer les messages non lus
$stmt = $pdo->prepare("
    SELECT id, sender_email, message, created_at 
    FROM private_message 
    WHERE is_read = 0 
    AND sender_email != ? 
    ORDER BY created_at DESC
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

// Retourner les messages au format JSON
echo json_encode($messages);
?>
