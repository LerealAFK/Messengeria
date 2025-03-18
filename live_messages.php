<?php
session_start();
include('db.php');

header('Content-Type: application/json');

if (!isset($_GET['groupe_id'], $_GET['last_message_id'])) {
    echo json_encode(["error" => "Paramètres manquants"]);
    exit();
}

$groupe_id = (int) $_GET['groupe_id'];
$last_message_id = (int) $_GET['last_message_id'];

if ($groupe_id <= 0 || $last_message_id < 0) {
    echo json_encode(["error" => "Paramètres invalides"]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT DISTINCT gm.id, gm.message, u.pronouns, gm.created_at 
    FROM groupe_messages gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.groupe_id = ? AND gm.id > ? 
    ORDER BY gm.id ASC
    LIMIT 20
");


$stmt->execute([$groupe_id, $last_message_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debugging : voir si la requête SQL retourne quelque chose
error_log("Messages récupérés : " . json_encode($messages));

echo json_encode($messages);
