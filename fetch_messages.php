<?php
session_start();
include('../db.php');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'Utilisateur non connecté.']);
    exit();
}

$user_email = $_SESSION['email'];

// Vérification des paramètres GET
if (!isset($_GET['conversation_id']) || !isset($_GET['last_message_id'])) {
    echo json_encode(['error' => 'Paramètres invalides.']);
    exit();
}

$conversation_id = (int) $_GET['conversation_id'];
$last_message_id = (int) $_GET['last_message_id'];

// Récupération des nouveaux messages
$stmt = $pdo->prepare("
    SELECT id, sender_email, message, created_at 
    FROM private_message 
    WHERE conversation_id = ? AND id > ?
    ORDER BY created_at ASC
");
$stmt->execute([$conversation_id, $last_message_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Renvoi des messages sous forme de JSON
echo json_encode($messages);
exit();
