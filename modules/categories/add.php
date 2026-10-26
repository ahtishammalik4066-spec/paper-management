<?php

$pageTitle = "Add Category";

require_once "../../includes/header.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {

        $error = "Category name is required.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO categories
                (name, description)
                VALUES
                (:name, :description)
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {
                $error = "This category already exists.";
            } else {
                $error = "Something went wrong.";
            }

        }

    }

}

?>

<div class="card border-0 shadow-sm">

    <div class="card-body p-4">

        <h4 class="mb-4">
            Add Category
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
                    placeholder="e.g. Art Paper"
                    required
                >

            </div>

            <div class="mb-4">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                ></textarea>

            </div>

            <button
                type="submit"
                class="btn btn-dark"
            >
                Save Category
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