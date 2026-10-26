<?php

$pageTitle = "Edit Category";

require_once "../../includes/header.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$category = $stmt->fetch();

if (!$category) {
    die("Category not found.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name === '') {

        $error = "Category name is required.";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE categories
                SET
                    name = :name,
                    description = :description,
                    status = :status
                WHERE id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':status' => $status,
                ':id' => $id
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            $error = "Unable to update category.";

        }

    }

}

?>

<div class="card border-0 shadow-sm">

    <div class="card-body p-4">

        <h4 class="mb-4">
            Edit Category
        </h4>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Category Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?= htmlspecialchars($category['name']) ?>"
                    required
                >

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                ><?= htmlspecialchars($category['description'] ?? '') ?></textarea>

            </div>

            <div class="form-check mb-4">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="status"
                    id="status"
                    <?= $category['status'] ? 'checked' : '' ?>
                >

                <label
                    class="form-check-label"
                    for="status"
                >
                    Active
                </label>

            </div>

            <button
                type="submit"
                class="btn btn-dark"
            >
                Update Category
            </button>

            <a
                href="index.php"
                class="btn btn-light"
            >
                Cancel
            </a>

        </form>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>