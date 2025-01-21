<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérifier si l'identifiant de la conversation est fourni
if (!isset($_GET['conversation_id'])) {
    echo json_encode(['error' => 'Conversation non spécifiée']);
    exit();
}

$conversation_id = $_GET['conversation_id'];

// Vérifier s'il y a un identifiant de dernier message (facultatif)
$last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;

// Récupérer les messages récents
if ($last_message_id > 0) {
    // Si un dernier message est défini, récupérer uniquement les nouveaux messages
    $stmt = $pdo->prepare("
        SELECT id, sender_email, message, created_at 
        FROM private_message 
        WHERE conversation_id = ? 
        AND id > ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversation_id, $last_message_id]);
} else {
    // Sinon, récupérer tous les messages
    $stmt = $pdo->prepare("
        SELECT id, sender_email, message, created_at 
        FROM private_message 
        WHERE conversation_id = ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$conversation_id]);
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retourner les messages au format JSON
header('Content-Type: application/json');
echo json_encode($messages);
