<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Variables pour les messages d'erreur ou de succès
$error = "";
$success = "";

// Changer l'email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_email'])) {
    $new_email = $_POST['new_email'];
    // Vérifier si l'email est valide
    if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE email = ?");
        $stmt->execute([$new_email, $_SESSION['email']]);
        $_SESSION['email'] = $new_email; // Mettre à jour la session
        $success = "Email mis à jour avec succès.";
    } else {
        $error = "Veuillez entrer un email valide.";
    }
}

// Changer le mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    // Vérifier que le mot de passe est assez long
    if (strlen($new_password) >= 6) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $_SESSION['email']]);
        $success = "Mot de passe mis à jour avec succès.";
    } else {
        $error = "Le mot de passe doit comporter au moins 6 caractères.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Messengeria</title>
    <link rel="stylesheet" href="styles/settingStyle.css">
</head>
<body>
    <div class="container">
        <div class="top-bar">
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">Déconnexion</button>
            </form>
        </div>
        <h1>Paramètres de votre compte</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Formulaire pour changer l'email -->
        <form action="settings.php" method="POST">
            <label for="new_email">Changer l'email :</label>
            <input type="email" id="new_email" name="new_email" placeholder="Entrez votre nouvel email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
            <button type="submit">Mettre à jour l'email</button>
        </form>

        <!-- Formulaire pour changer le mot de passe -->
        <form action="settings.php" method="POST">
            <label for="new_password">Changer le mot de passe :</label>
            <input type="password" id="new_password" name="new_password" placeholder="Entrez votre nouveau mot de passe" required>
            <button type="submit">Mettre à jour le mot de passe</button>
        </form>

        <!-- Vous pouvez ajouter plus d'options ici (comme télécharger une photo de profil, notifications, etc.) -->

    </div>
</body>
</html>
