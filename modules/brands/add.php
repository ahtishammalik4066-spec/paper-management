<?php

$pageTitle = "Add Brand";

require_once "../../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($name === "") {

        $error = "Brand name is required.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO brands
                (name, description, status)
                VALUES (?, ?, 'active')
            ");

            $stmt->execute([
                $name,
                $description
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {

                $error = "This brand already exists.";

            } else {

                $error = "Something went wrong. Please try again.";

            }

        }

    }

}

?>

<div class="mb-4">

    <h4>Add Brand</h4>

    <p class="text-muted">
        Add a new product brand
    </p>

</div>


<?php if ($error): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<div class="card shadow-sm border-0">

    <div class="card-body">

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Brand Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="Enter brand name"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
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
                    placeholder="Enter description"
                ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

            </div>


            <div class="mb-3">

                <label class="form-label">
                    Status
                </label>

                <select name="status" class="form-select">

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
                        Inactive
                    </option>

                </select>

            </div>


            <div class="d-flex gap-2">

                <button type="submit" class="btn btn-primary">

                    <i class="bi bi-check-lg"></i>
                    Save Brand

                </button>

                <a href="index.php" class="btn btn-secondary">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>