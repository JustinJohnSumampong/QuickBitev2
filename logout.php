<?php
require_once "config/database.php";
require_once "includes/functions.php";

if (!empty($_COOKIE['qb_remember'])) {
    $pdo->prepare("UPDATE users SET remember_token=NULL WHERE remember_token=?")->execute([$_COOKIE['qb_remember']]);
    setcookie('qb_remember', '', time() - 3600, '/');
}
$_SESSION = [];
session_destroy();
redirect('index.php');
?>
