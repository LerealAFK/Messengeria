<?php
session_start();
session_unset();
session_destroy();
header('Location: login.php');  // Redirection vers la page de connexion
exit();
?>
