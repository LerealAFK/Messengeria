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

// Récupérer la photo de profil de l'utilisateur
$profile_picture = $user['profile_picture'] ?? null;

// Gérer les soumissions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si un fichier a été téléchargé pour la photo de profil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileType = $_FILES['profile_picture']['type'];

        // Définir la limite de taille (2 Mo)
        $maxFileSize = 2 * 1024 * 1024; // 2 Mo

        // Vérifier si le fichier dépasse la taille maximale
        if ($fileSize > $maxFileSize) {
            $error = "La taille de l'image ne doit pas dépasser 2 Mo.";
        } else {
            // Vérifier que le fichier est une image valide
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($fileType, $allowedTypes)) {
                // Générer un nom de fichier unique
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $newFileName = time() . '_' . $_SESSION['email'] . '.' . $fileExtension;

                // Définir le chemin de destination
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true); // Créer le dossier s'il n'existe pas
                }
                $dest_path = $uploadDir . $newFileName;

                // Déplacer le fichier téléchargé
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Mettre à jour la base de données avec le chemin de la photo
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE email = ?");
                    $stmt->execute([$dest_path, $_SESSION['email']]);
                    $success = "Photo de profil mise à jour avec succès.";
                } else {
                    $error = "Une erreur est survenue lors du téléversement.";
                }
            } else {
                $error = "Veuillez télécharger une image valide (JPEG, PNG, GIF).";
            }
        }
    }

    // Gestion des autres champs (email, mot de passe, etc.)
    if (isset($_POST['new_email'])) {
        $new_email = $_POST['new_email'];
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE email = ?");
            $stmt->execute([$new_email, $_SESSION['email']]);
            $_SESSION['email'] = $new_email; // Mettre à jour la session
            $success = "Email mis à jour avec succès.";
        } else {
            $error = "Veuillez entrer un email valide.";
        }
    }

    if (isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
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
        <!-- Affichage de la photo de profil -->
        <div class="profile-picture">
            <?php if ($profile_picture): ?>
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="profile-img">
            <?php else: ?>
                <img src="default-avatar.png" alt="Photo de profil par défaut" class="profile-img">
            <?php endif; ?>
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

        <!-- Formulaire pour télécharger une nouvelle photo de profil -->
        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <label for="profile_picture">Télécharger une nouvelle photo de profil :</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            <button type="submit">Mettre à jour la photo de profil</button>
        </form>
    </div>
    <script>
        document.getElementById('profile_picture').addEventListener('change', function () {
            const maxSize = 2 * 1024 * 1024; // 2 Mo
            const file = this.files[0];

            if (file && file.size > maxSize) {
                alert("La taille de l'image ne doit pas dépasser 2 Mo.");
                this.value = ""; // Réinitialiser le champ de fichier
            }
        });
    </script>

</body>
</html>
