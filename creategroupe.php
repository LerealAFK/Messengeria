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

    // Récupérer l'ID de l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $user_id = $user['id'];

    // Insérer le groupe
    $stmt = $pdo->prepare("INSERT INTO groupes (nom, admin_id) VALUES (?, ?)");
    $stmt->execute([$nom, $user_id]);
    $groupe_id = $pdo->lastInsertId();

    // Définir les permissions pour les rôles
    $permissionsAdmin = json_encode([
        "gerer_membres" => "on",
        "epingler_messages" => "on",
        "changer_description" => "on",
        "envoyer_messages" => "on"
    ]);

    $permissionsMembre = json_encode([
        "envoyer_messages" => "on"
    ]);

    // Insérer le rôle "Admin"
    $stmt = $pdo->prepare("INSERT INTO groupe_roles (groupe_id, nom, couleur, permissions) VALUES (?, 'Admin', '#FF0000', ?)");
    $stmt->execute([$groupe_id, $permissionsAdmin]);
    $role_id_admin = $pdo->lastInsertId();

    // Envoyer un message de bienvenue automatique
    $message_bienvenue = "Bienvenue sur le groupe $nom. Vous avez bien créé le groupe, amusez-vous bien !";
    $stmt = $pdo->prepare("INSERT INTO groupe_messages (groupe_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$groupe_id, $user_id, $message_bienvenue]);


    // Insérer le rôle "Membre"
    $stmt = $pdo->prepare("INSERT INTO groupe_roles (groupe_id, nom, couleur, permissions) VALUES (?, 'Membre', '#808080', ?)");
    $stmt->execute([$groupe_id, $permissionsMembre]);
    $role_id_membre = $pdo->lastInsertId();

    // Ajouter l'utilisateur créateur avec le rôle d'Admin
    $stmt = $pdo->prepare("INSERT INTO groupe_membres (groupe_id, user_id, role_id) VALUES (?, ?, ?)");
    $stmt->execute([$groupe_id, $user_id, $role_id_admin]);

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
