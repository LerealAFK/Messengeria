<?php
session_start();
include('db.php');

if (!isset($_SESSION['email']) || !isset($_POST['message']) || !isset($_POST['conversation_id'])) {
    header('Location: index.php');
    exit();
}

$user_email = $_SESSION['email'];
$message = $_POST['message'];
$conversation_id = $_POST['conversation_id'];

// Insérer le message dans la base de données
$stmt = $pdo->prepare("INSERT INTO private_messages (conversation_id, sender_email, message) VALUES (?, ?, ?)");
$stmt->execute([$conversation_id, $user_email, $message]);

header('Location: chat.php?conversation_id=' . $conversation_id);
exit();
?>
