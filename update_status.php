<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    http_response_code(403); // Accès interdit si pas connecté
    exit();
}

$user_email = $_SESSION['email'];

try {
    // Mettre à jour le statut en ligne
    $stmt = $pdo->prepare("UPDATE users SET is_online = TRUE WHERE email = ?");
    $stmt->execute([$user_email]);

    http_response_code(200); // Succès
} catch (Exception $e) {
    http_response_code(500); // Erreur interne du serveur
    echo json_encode(['error' => $e->getMessage()]);
}
