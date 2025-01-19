<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    include('db.php');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['email'] = $email;
        echo "<script>
            localStorage.setItem('email', '$email');
            window.location.href = 'index.php';
        </script>";
    } else {
        echo "<p>Email ou mot de passe incorrect.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="styles/loginStyle.css">
</head>
<body>
    <h1>Connexion</h1>
    <form action="login.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
</body>
</html>
