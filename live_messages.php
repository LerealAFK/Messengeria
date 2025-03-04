<?php
session_start();
include('db.php');

if (!isset($_GET['groupe_id'], $_GET['last_message_id'])) {
    echo json_encode(["error" => "Paramètres manquants"]);
    exit();
}

$groupe_id = $_GET['groupe_id'];
$last_message_id = (int) $_GET['last_message_id'];

$stmt = $pdo->prepare("SELECT gm.message, u.pronouns, gm.created_at, gm.id 
                       FROM groupe_messages gm 
                       JOIN users u ON gm.user_id = u.id 
                       WHERE gm.groupe_id = ? AND gm.id > ? 
                       ORDER BY gm.created_at ASC");
$stmt->execute([$groupe_id, $last_message_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
