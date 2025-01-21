<?php
session_start();
include('db.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si l'utilisateur est un admin
$stmt = $pdo->prepare("SELECT admin FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$current_user = $stmt->fetch();

if (!$current_user || !$current_user['admin']) {
    header('Location: index.php');
    exit();
}

// Gestion de l'action de muter un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_email'])) {
    $user_email = trim($_POST['user_email']);
    $mute_action = isset($_POST['mute']) ? true : false; // Si le bouton mute est cliqué

    // Vérifier que l'utilisateur existe dans la base de données
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $user_to_update = $stmt->fetch();

    if ($user_to_update) {
        // Mettre à jour le statut `is_mute` de l'utilisateur
        $stmt = $pdo->prepare("UPDATE users SET is_mute = ? WHERE email = ?");
        $stmt->execute([$mute_action, $user_email]);
        $message = $mute_action 
            ? "L'utilisateur a été muté avec succès." 
            : "L'utilisateur a été démuté avec succès.";
    } else {
        $message = "Utilisateur introuvable.";
    }
}

// Récupérer tous les utilisateurs pour les afficher
$stmt = $pdo->query("SELECT email, pronouns, is_mute FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/modoStyle.css">
    <title>Modération - Messengeria</title>
</head>
<body>
    <div class="container">
        <h1>Page de modération</h1>

        <!-- Message de retour -->
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>Liste des utilisateurs</h2>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Pronoms</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['pronouns'] ?: 'Non spécifié'); ?></td>
                        <td>
                            <?php echo $user['is_mute'] ? 'Muté' : 'Actif'; ?>
                        </td>
                        <td>
                            <form action="modo.php" method="POST" style="display: inline;">
                                <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php if (!$user['is_mute']): ?>
                                    <button type="submit" name="mute" class="mute-button">Mute</button>
                                <?php else: ?>
                                    <button type="submit" name="unmute" class="unmute-button">Unmute</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
