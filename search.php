<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$results = [];
$search = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search = trim($_POST['search']);
    $stmt = $pdo->prepare("SELECT email FROM users WHERE email LIKE ?");
    $stmt->execute(['%' . $search . '%']);
    $results = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'utilisateur</title>
    <link rel="stylesheet" href="styles/searchStyle.css">
</head>
<body>
    <div class="container">
        <h1>Rechercher un utilisateur</h1>
        <form action="search.php" method="POST">
            <input type="text" name="search" placeholder="Tapez une adresse email..." value="<?php echo htmlspecialchars($search); ?>" required>
            <button type="submit">Rechercher</button>
        </form>
        <div class="results">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php if (empty($results)): ?>
                    <p>Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($results as $user): ?>
                            <li>
                                <a href="profile.php?email=<?php echo urlencode($user['email']); ?>">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
