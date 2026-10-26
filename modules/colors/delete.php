<?php

require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login.php");
    exit;
}


$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;


if ($id > 0) {

    try {

        $stmt = $pdo->prepare("
            DELETE FROM colors
            WHERE id = ?
        ");

        $stmt->execute([$id]);

    } catch (PDOException $e) {

        // Color may already be used by product variants.

    }

}


header("Location: index.php");
exit;