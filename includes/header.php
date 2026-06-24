<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $appBase = preg_replace('#/admin$#', '', $scriptPath);
    $appBase = rtrim($appBase, '/');
    if ($appBase === '') {
        $appBase = '/';
    }
    define('BASE_URL', $appBase);
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