<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $pronouns = $_POST['pronouns']; // Récupérer les pronoms

    include('db.php');

    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, pronouns) VALUES (?, ?, ?)");
        $stmt->execute([$email, $password, $pronouns]);
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="styles/loginStyle.css">
</head>
<body>
    <h1>Inscription</h1>
    <form action="register.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <input type="text" name="pronouns" placeholder="Username" required>
        <button type="submit">S'inscrire</button>
    </form>
    <p>Déjà inscrit ? <a href="login.php">Connectez-vous ici</a>.</p>
</body>
</html>
