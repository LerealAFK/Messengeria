<?php
header('Content-Type: application/json');
include('db.php');

// Lire les données JSON envoyées depuis le client
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;

if ($email) {
    // Vérifier si l'email existe dans la base de données
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode([
            'valid' => true,
            'email' => $user['email'], // Retourne l'email
            'name' => $user['name'] ?? null, // Optionnel : ajouter d'autres infos utiles
        ]);
    } else {
        echo json_encode(['valid' => false]);
    }
} else {
    echo json_encode(['valid' => false]);
}
