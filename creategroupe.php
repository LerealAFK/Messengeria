<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $email = $_SESSION['email'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $user_id = $user['id'];

    $stmt = $pdo->prepare("INSERT INTO groupe_roles (groupe_id, nom, couleur, permissions) VALUES (?, 'Membre', '#808080', ?)");
    $permissionsMembre = json_encode(["envoyer_messages" => true]);
    $stmt->execute([$groupe_id, $permissionsMembre]);

    // Récupérer l'ID du rôle "Membre" pour l’assigner aux nouveaux membres
    $role_id_membre = $pdo->lastInsertId();


    $stmt = $pdo->prepare("INSERT INTO groupes (nom, admin_id) VALUES (?, ?)");
    $stmt->execute([$nom, $user_id]);
    $groupe_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO groupe_membres (groupe_id, user_id, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$groupe_id, $user_id]);

    header("Location: groupes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un groupe</title>
    <link rel="stylesheet" href="styles/groupeStyle.css">
</head>
<body>
    <div class="container">
        <h2>Créer un groupe</h2>
        <form method="POST">
            <input type="text" name="nom" placeholder="Nom du groupe" required>
            <button type="submit">Créer</button>
        </form>
        <a href="groupes.php">Retour</a>
    </div>
</body>
</html>
