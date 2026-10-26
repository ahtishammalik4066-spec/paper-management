<?php

require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login.php");
    exit;
}


$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM brands
            WHERE id = ?
        ");

        $stmt->execute([$id]);

    } catch (PDOException $e) {

        // Brand may already be used by products.
        // Do nothing here and return to list.

    }

}

header("Location: index.php");
exit;