<?php

$pageTitle = "Edit GSM";

require_once "../../includes/header.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get GSM
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM gsm
    WHERE id = ?
");

$stmt->execute([$id]);

$gsm = $stmt->fetch();


if (!$gsm) {

    header("Location: index.php");
    exit;

}


$error = "";


/*
|--------------------------------------------------------------------------
| Update GSM
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $value = trim($_POST["value"] ?? "");
    $unit = trim($_POST["unit"] ?? "GSM");
    $status = $_POST["status"] ?? "active";


    if ($value === "") {

        $error = "GSM value is required.";

    } elseif (!is_numeric($value)) {

        $error = "GSM value must be a number.";

    } elseif ((float)$value <= 0) {

        $error = "GSM value must be greater than zero.";

    } elseif ($unit === "") {

        $error = "Unit is required.";

    } elseif (!in_array($status, ["active", "inactive"], true)) {

        $error = "Invalid status.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check Duplicate
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM gsm
                WHERE value = ?
                AND id != ?
                LIMIT 1
            ");

            $check->execute([
                $value,
                $id
            ]);


            if ($check->fetch()) {

                $error = "Another GSM with this value already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Update
                |--------------------------------------------------------------------------
                */

                $update = $pdo->prepare("
                    UPDATE gsm
                    SET
                        value = ?,
                        unit = ?,
                        status = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $value,
                    $unit,
                    $status,
                    $id
                ]);


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
            Edit GSM
        </h4>

        <p class="text-muted mb-0">
            Update GSM information
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

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    GSM Value
                </label>

                <input
                    type="number"
                    name="value"
                    class="form-control"
                    min="0.01"
                    step="0.01"
                    value="<?= htmlspecialchars(
                        $_POST['value'] ?? $gsm['value']
                    ) ?>"
                    required
                >

            </div>


            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Unit
                </label>

                <input
                    type="text"
                    name="unit"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $_POST['unit'] ?? ($gsm['unit'] ?? 'GSM')
                    ) ?>"
                    required
                >

            </div>


            <div class="mb-4">

                <label class="form-label fw-semibold">
                    Status
                </label>

                <?php
                $currentStatus =
                    $_POST['status']
                    ?? $gsm['status'];
                ?>

                <select name="status" class="form-select">

                    <option
                        value="active"
                        <?= $currentStatus === 'active'
                            ? 'selected'
                            : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= $currentStatus === 'inactive'
                            ? 'selected'
                            : '' ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-save"></i>
                    Update GSM

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