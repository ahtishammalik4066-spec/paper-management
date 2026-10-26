<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$pageTitle = $pageTitle ?? "Paper Management System";

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="/paper-management/assets/css/style.css"
    >

</head>

<body>

<div class="app-shell">

<?php require __DIR__ . '/sidebar.php'; ?>

<div class="flex-grow-1">

<nav class="topbar navbar navbar-light bg-white px-3 px-lg-4">

    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light sidebar-toggle d-lg-none" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="mb-0">
            <?= htmlspecialchars($pageTitle) ?>
        </h5>
    </div>

    <div>

        <span class="me-3">
            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
        </span>

        <a
            href="/paper-management/logout.php"
            class="btn btn-sm btn-outline-danger"
        >
            Logout
        </a>

    </div>

</nav>

<main class="page-content">