<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase = preg_replace('#/(admin|manager)$#', '', $scriptPath);
    $appBase = rtrim($appBase, '/');
    if ($appBase === '') {
        $appBase = '/';
    }
    define('BASE_URL', $appBase);
}

// ── Track last_active for online presence ───────────────────
// Only run once per minute per session to avoid DB hammering
if (isset($_SESSION['user_id'], $_SESSION['user_type'])) {
    $nowTs = time();
    if (!isset($_SESSION['last_active_update']) || ($nowTs - $_SESSION['last_active_update']) >= 60) {
        $_SESSION['last_active_update'] = $nowTs;
        // Determine which table to update
        if (!isset($GLOBALS['conn'])) {
            @include_once __DIR__ . '/../config/database.php';
        }
        if (isset($GLOBALS['conn']) || isset($conn)) {
            $db  = $GLOBALS['conn'] ?? $conn;
            $uid = (int) $_SESSION['user_id'];
            if ($_SESSION['user_type'] === 'Client') {
                $upStmt = mysqli_prepare($db, "UPDATE Client SET last_active=NOW() WHERE UserID=?");
                if ($upStmt) { mysqli_stmt_bind_param($upStmt,"i",$uid); mysqli_stmt_execute($upStmt); }
            } else {
                $upStmt = mysqli_prepare($db, "UPDATE Employee SET last_active=NOW() WHERE EmployeeID=?");
                if ($upStmt) { mysqli_stmt_bind_param($upStmt,"i",$uid); mysqli_stmt_execute($upStmt); }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Yosech Construction</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

</head>

<body>