<?php

$pageTitle = "Edit Brand";

require_once "../../includes/header.php";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Brand
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);

$brand = $stmt->fetch();

if (!$brand) {
    header("Location: index.php");
    exit;
}


$error = "";


/*
|--------------------------------------------------------------------------
| Update Brand
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status = $_POST["status"] ?? "active";


    // Validation

    if ($name === "") {

        $error = "Brand name is required.";

    } elseif (!in_array($status, ["active", "inactive"], true)) {

        $error = "Invalid status.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check duplicate brand
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM brands
                WHERE name = ?
                AND id != ?
                LIMIT 1
            ");

            $check->execute([
                $name,
                $id
            ]);

            if ($check->fetch()) {

                $error = "Another brand with this name already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Update
                |--------------------------------------------------------------------------
                */

                $update = $pdo->prepare("
                    UPDATE brands
                    SET
                        name = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $name,
                    $description,
                    $status,
                    $id
                ]);


                /*
                |--------------------------------------------------------------------------
                | Redirect
                |--------------------------------------------------------------------------
                */

                header("Location: index.php");
                exit;
            }

        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();

        }

    }

}

?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="mb-1">
            Edit Brand
        </h4>

        <p class="text-muted mb-0">
            Update brand information
        </p>

    </div>

    <a href="index.php" class="btn btn-secondary">

        <i class="bi bi-arrow-left"></i>

        Back

    </a>

</div>


<?php if ($error): ?>

    <div class="alert alert-danger">

        <i class="bi bi-exclamation-triangle"></i>

        <?= htmlspecialchars($error) ?>

    </div>

<?php endif; ?>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">


            <!-- Brand Name -->

            <div class="mb-3">

                <label class="form-label fw-semibold">

                    Brand Name

                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $_POST['name'] ?? $brand['name']
                    ) ?>"
                    placeholder="Enter brand name"
                    required
                >

            </div>


            <!-- Description -->

            <div class="mb-3">

                <label class="form-label fw-semibold">

                    Description

                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                    placeholder="Enter brand description"
                ><?= htmlspecialchars(
                    $_POST['description'] ?? ($brand['description'] ?? '')
                ) ?></textarea>

            </div>


            <!-- Status -->

            <div class="mb-4">

                <label class="form-label fw-semibold">

                    Status

                </label>

                <?php
                $currentStatus = $_POST['status'] ?? $brand['status'];
                ?>

                <select
                    name="status"
                    class="form-select"
                >

                    <option
                        value="active"
                        <?= $currentStatus === 'active' ? 'selected' : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= $currentStatus === 'inactive' ? 'selected' : '' ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <!-- Buttons -->

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-save"></i>

                    Update Brand

                </button>


                <a
                    href="index.php"
                    class="btn btn-light border"
                >

                    Cancel

                </a>

            </div>


        </form>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>