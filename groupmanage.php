<?php
session_start();
include('db.php');

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_email = $_SESSION['email'];

$groupe_id = $_GET['id'] ?? null;
if (!$groupe_id) {
    die("ID du groupe non spécifié.");
}

$stmt = $pdo->prepare("SELECT * FROM groupes WHERE id = ?");
$stmt->execute([$groupe_id]);
$groupe = $stmt->fetch();

if (!$groupe) {
    die("Le groupe spécifié n'existe pas.");
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$user_email]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur non trouvé.");
}

$user_id = $user['id'];

$stmt = $pdo->prepare("SELECT * FROM groupe_membres WHERE groupe_id = ? AND user_id = ? AND role = 'admin'");
$stmt->execute([$groupe_id, $user_id]);
$admin = $stmt->fetch();

if (!$admin) {
    echo "Vous n'êtes pas administrateur de ce groupe.";
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM groupe_roles WHERE groupe_id = ?");
$stmt->execute([$groupe_id]);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['ajouter_role'])) {
    $nom = $_POST['nom'];
    $couleur = $_POST['couleur'];
    $permissions = json_encode($_POST['permissions'] ?? []);

    $stmt = $pdo->prepare("INSERT INTO groupe_roles (groupe_id, nom, couleur, permissions) VALUES (?, ?, ?, ?)");
    $stmt->execute([$groupe_id, $nom, $couleur, $permissions]);
    
    echo "Rôle ajouté !";
}

$stmt = $pdo->prepare("
    SELECT gm.user_id, u.pronouns, gr.nom AS role_nom, gr.id AS role_id 
    FROM groupe_membres gm
    JOIN users u ON gm.user_id = u.id
    LEFT JOIN groupe_roles gr ON gm.role_id = gr.id
    WHERE gm.groupe_id = ?
");
$stmt->execute([$groupe_id]);
$membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['modifier_roles'])) {
    foreach ($_POST['role'] as $user_id => $role_id) {
        $stmt = $pdo->prepare("UPDATE groupe_membres SET role_id = ? WHERE user_id = ? AND groupe_id = ?");
        $stmt->execute([$role_id, $user_id, $groupe_id]);
    }
    echo "Rôles mis à jour !";
}




// Recherche d'un utilisateur par pronoms
$search_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_member'])) {
    $search_pronouns = trim($_POST['search_pronouns']);

    $stmt = $pdo->prepare("SELECT id, email, pronouns FROM users WHERE pronouns LIKE ?");
    $stmt->execute(["%$search_pronouns%"]);
    $search_result = $stmt->fetch();

    if ($search_result) {
        $stmt = $pdo->prepare("SELECT * FROM groupe_membres WHERE groupe_id = ? AND user_id = ?");
        $stmt->execute([$groupe_id, $search_result['id']]);
        $search_result['exists'] = $stmt->fetch() ? true : false;
    }
}

// Ajouter un membre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $new_member_id = $_POST['user_id'];

    $stmt = $pdo->prepare("INSERT INTO groupe_membres (groupe_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt->execute([$groupe_id, $new_member_id]);

    header("Location: groupmanage.php?id=" . $groupe_id);
    exit();
}

// Liste des membres du groupe
$stmt = $pdo->prepare("
    SELECT users.email, users.pronouns, groupe_membres.role 
    FROM groupe_membres 
    JOIN users ON groupe_membres.user_id = users.id 
    WHERE groupe_membres.groupe_id = ?
");
$stmt->execute([$groupe_id]);
$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Groupe</title>
    <link rel="stylesheet" href="styles/groupmanageStyle.css">
</head>
<body>
    <div class="container">
        <h1>Gestion du Groupe</h1>

        <!-- Recherche par pronoms -->
        <h2>Rechercher un utilisateur à ajouter</h2>
        <form action="groupmanage.php?id=<?php echo $groupe_id; ?>" method="POST">
            <label for="search_pronouns">Pronoms de l'utilisateur :</label>
            <input type="text" name="search_pronouns" id="search_pronouns" required>
            <button type="submit" name="search_member">Rechercher</button>
        </form>

        <!-- Résultat de la recherche -->
        <?php if ($search_result): ?>
            <div class="search-result">
                <p>
                    <strong><?php echo htmlspecialchars($search_result['pronouns']); ?></strong> 
                    (<?php echo htmlspecialchars($search_result['email']); ?>)
                    <?php if ($search_result['exists']): ?>
                        est déjà membre du groupe.
                    <?php else: ?>
                        <form action="groupmanage.php?id=<?php echo $groupe_id; ?>" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $search_result['id']; ?>">
                            <button type="submit" name="add_member">Ajouter au groupe</button>
                        </form>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <h3>Membres du groupe</h3>
        <form method="POST">
            <?php foreach ($membres as $membre): ?>
                <div>
                    <b><?= htmlspecialchars($membre['pronouns']) ?></b> 
                    <select name="role[<?= $membre['user_id'] ?>]">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= $membre['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
             </div>
            <?php endforeach; ?>
            <button type="submit" name="modifier_roles">Modifier les rôles</button>
        </form>

        <h3>Rôles du groupe</h3>
        <form method="POST">
            <input type="text" name="nom" placeholder="Nom du rôle" required>
            <input type="color" name="couleur" value="#FFFFFF">
    
            <h4>Permissions :</h4>
            <label><input type="checkbox" name="permissions[admin]"> Admin</label><br>
            <label><input type="checkbox" name="permissions[gerer_roles]"> Gérer les rôles</label><br>
            <label><input type="checkbox" name="permissions[gerer_membres]"> Gérer les membres</label><br>
            <label><input type="checkbox" name="permissions[supprimer_messages]"> Supprimer des messages</label><br>
            <label><input type="checkbox" name="permissions[epingler_messages]"> Épingler des messages</label><br>
            <label><input type="checkbox" name="permissions[changer_description]"> Modifier la description</label><br>
            <label><input type="checkbox" name="permissions[changer_nom]"> Modifier le nom</label><br>
            <label><input type="checkbox" name="permissions[envoyer_messages]" checked> Envoyer des messages</label><br>
            <label><input type="checkbox" name="permissions[envoyer_images]"> Envoyer des images</label><br>

            <button type="submit" name="ajouter_role">Ajouter le rôle</button>
        </form>

    </div>
</body>
</html>
