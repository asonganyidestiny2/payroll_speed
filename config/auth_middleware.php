<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

function requireRole($roles = []) {
    if (!in_array($_SESSION['role'], $roles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access denied.";
        exit();
    }
}
?>
