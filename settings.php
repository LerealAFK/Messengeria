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

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();
$profile_picture = $user['profile_picture'] ?? null;
$pronouns = $user['pronouns'] ?? "";

// Gérer les soumissions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mise à jour des pronoms
    if (isset($_POST['pronouns'])) {
        $new_pronouns = trim($_POST['pronouns']);
        $stmt = $pdo->prepare("UPDATE users SET pronouns = ? WHERE email = ?");
        $stmt->execute([$new_pronouns, $_SESSION['email']]);
        $success = "Pronoms mis à jour avec succès.";
    }

    // Mise à jour de la photo de profil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileType = $_FILES['profile_picture']['type'];

        $maxFileSize = 2 * 1024 * 1024; // 2 Mo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if ($fileSize > $maxFileSize) {
            $error = "La taille de l'image ne doit pas dépasser 2 Mo.";
        } elseif (!in_array($fileType, $allowedTypes)) {
            $error = "Veuillez télécharger une image valide (JPEG, PNG, GIF).";
        } else {
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = time() . '_' . $_SESSION['email'] . '.' . $fileExtension;

            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE email = ?");
                $stmt->execute([$dest_path, $_SESSION['email']]);
                $success = "Photo de profil mise à jour avec succès.";
            } else {
                $error = "Une erreur est survenue lors du téléversement.";
            }
        }
    }

    // Mise à jour de l'email
    if (isset($_POST['new_email'])) {
        $new_email = trim($_POST['new_email']);
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE email = ?");
            $stmt->execute([$new_email, $_SESSION['email']]);
            $_SESSION['email'] = $new_email;
            $success = "Email mis à jour avec succès.";
        } else {
            $error = "Veuillez entrer un email valide.";
        }
    }

    // Mise à jour du mot de passe
    if (isset($_POST['new_password'])) {
        $new_password = trim($_POST['new_password']);
        if (strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $_SESSION['email']]);
            $success = "Mot de passe mis à jour avec succès.";
        } else {
            $error = "Le mot de passe doit comporter au moins 6 caractères.";
        }
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

        <!-- Photo de profil -->
        <div class="profile-picture">
            <?php if ($profile_picture): ?>
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="profile-img">
            <?php else: ?>
                <img src="default-avatar.png" alt="Photo de profil par défaut" class="profile-img">
            <?php endif; ?>
        </div>

        <h1>Paramètres de votre compte</h1>

        <!-- Messages d'erreur ou de succès -->
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Formulaire de mise à jour des pronoms -->
        <form action="settings.php" method="POST">
            <label for="pronouns">Vos pronoms :</label>
            <input type="text" id="pronouns" name="pronouns" placeholder="Exemple : il/lui, elle/elle, iel" value="<?php echo htmlspecialchars($pronouns); ?>">
            <button type="submit">Mettre à jour les pronoms</button>
        </form>

        <!-- Formulaire de mise à jour de l'email -->
        <form action="settings.php" method="POST">
            <label for="new_email">Changer l'email :</label>
            <input type="email" id="new_email" name="new_email" placeholder="Entrez votre nouvel email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
            <button type="submit">Mettre à jour l'email</button>
        </form>

        <!-- Formulaire de mise à jour du mot de passe -->
        <form action="settings.php" method="POST">
            <label for="new_password">Changer le mot de passe :</label>
            <input type="password" id="new_password" name="new_password" placeholder="Entrez votre nouveau mot de passe" required>
            <button type="submit">Mettre à jour le mot de passe</button>
        </form>

        <!-- Formulaire de mise à jour de la photo de profil -->
        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <label for="profile_picture">Télécharger une nouvelle photo de profil :</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            <button type="submit">Mettre à jour la photo de profil</button>
        </form>
    </div>
    <a href="index.php">
        <button class="conversation-button">🏠</button>
    </a> 

    <script>
        document.getElementById('profile_picture').addEventListener('change', function () {
            const maxSize = 2 * 1024 * 1024; // 2 Mo
            const file = this.files[0];

            if (file && file.size > maxSize) {
                alert("La taille de l'image ne doit pas dépasser 2 Mo.");
                this.value = ""; // Réinitialiser le champ
            }
        });
    </script>
</body>
</html>
