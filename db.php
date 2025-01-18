<?php
$host = 'mysql-messdata.alwaysdata.net'; // Adresse du serveur MySQL
$db = 'messdata_message'; // Nom de la base de données
$user = 'messdata'; // Utilisateur MySQL
$pass = 'nessim2012'; // Mot de passe MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
