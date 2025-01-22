<?php
session_start();

// Inclure la base de données
include('db.php');

// Si l'utilisateur est déjà connecté via une session, le rediriger vers index.php
if (isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

// Traiter la soumission du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Vérifier si l'utilisateur existe dans la base de données
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Connexion réussie : démarrer une session et rediriger
        $_SESSION['email'] = $email;
        header('Location: index.php');
        exit();
    } else {
        // Message d'erreur pour email/mot de passe incorrect
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="styles/loginStyle.css">
</head>
<body>
    <div class="login-container">
        <h1>Connexion</h1>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
    </div>
</body>
</html>
