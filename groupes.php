<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$email = $_SESSION['email'];

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
$user_id = $user['id'];

$stmt = $pdo->prepare("SELECT g.id, g.nom FROM groupes g 
    JOIN groupe_membres gm ON g.id = gm.groupe_id WHERE gm.user_id = ?");
$stmt->execute([$user_id]);
$groupes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos groupes</title>
    <link rel="stylesheet" href="styles/groupeStyle.css">
</head>
<body>
    <div class="container">
        <h2>Vos groupes</h2>
        <ul class="group-list">
            <?php foreach ($groupes as $groupe): ?>
                <li><a href="groupechat.php?id=<?= $groupe['id'] ?>"><?= htmlspecialchars($groupe['nom']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <a href="creategroupe.php">Créer un groupe</a>
    </div>
</body>
</html>
