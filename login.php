<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la base de données
include('db.php');


// Si l'utilisateur est déjà connecté, rediriger
if (isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($email && $password) {
        
        
            // Vérifier l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['email'] = $email
            echo "
                <script>
                    localStorage.setItem('userEmail', '" . addslashes($email) . "');
                    window.location.href = 'index.php';
                </script>
            ";
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    

    } else {
        $error = "Veuillez remplir tous les champs.";
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
        <form action="login.php" method="POST" onsubmit="saveToLocalStorage(event)">
            <input type="email" name="email" id="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
    </div>

    <script>
        function saveToLocalStorage(event) {
            // Lire l'e-mail avant l'envoi du formulaire
            const email = document.getElementById('email').value;
            localStorage.setItem('userEmail', email);
        }
    </script>
</body>
</html>
