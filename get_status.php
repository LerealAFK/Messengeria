<?php
// Inclusion de la connexion à la base de données
include('db.php');

// Récupérer tous les utilisateurs avec leur statut actuel
$stmt = $pdo->query("SELECT email, is_online FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Définir le type de contenu pour la réponse
header('Content-Type: application/json');

// Retourner les données sous forme de JSON
echo json_encode($users);
