<?php

require_once "../../includes/auth.php";
require_once "../../config/database.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

try {

    $stmt = $pdo->prepare("
        DELETE FROM categories
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

} catch (PDOException $e) {

    // Category may be linked with products.
    // In that case it should not be deleted.

}

header("Location: index.php");
exit;