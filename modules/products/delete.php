<?php

require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($id <= 0) {
    header("Location: index.php");
    exit;
}


try {

    // Check product exists

    $check = $pdo->prepare("
        SELECT id
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([$id]);


    if (!$check->fetch()) {
        header("Location: index.php");
        exit;
    }


    // Delete product

    $delete = $pdo->prepare("
        DELETE FROM products
        WHERE id = ?
    ");

    $delete->execute([$id]);


    header("Location: index.php");
    exit;


} catch (PDOException $e) {

    echo "<h3>Cannot delete this product.</h3>";

    echo "<p>This product may already have variants or related records.</p>";

    echo '<a href="index.php">Back to Products</a>';
}