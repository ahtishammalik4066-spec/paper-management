<?php

$pageTitle = "Edit Size";

require_once "../../includes/header.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get Size
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM sizes
    WHERE id = ?
");

$stmt->execute([$id]);

$size = $stmt->fetch();


if (!$size) {

    header("Location: index.php");
    exit;

}


$error = "";


/*
|--------------------------------------------------------------------------
| Update Size
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $width = trim($_POST["width"] ?? "");
    $height = trim($_POST["height"] ?? "");
    $unit = trim($_POST["unit"] ?? "mm");
    $status = $_POST["status"] ?? "active";


    if ($name === "") {

        $error = "Size name is required.";

    } elseif ($width !== "" && !is_numeric($width)) {

        $error = "Width must be a number.";

    } elseif ($height !== "" && !is_numeric($height)) {

        $error = "Height must be a number.";

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
                FROM sizes
                WHERE name = ?
                AND id != ?
                LIMIT 1
            ");

            $check->execute([
                $name,
                $id
            ]);


            if ($check->fetch()) {

                $error = "Another size with this name already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Update
                |--------------------------------------------------------------------------
                */

                $update = $pdo->prepare("
                    UPDATE sizes
                    SET
                        name = ?,
                        width = ?,
                        height = ?,
                        unit = ?,
                        status = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $name,
                    $width !== "" ? $width : null,
                    $height !== "" ? $height : null,
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
            Edit Size
        </h4>

        <p class="text-muted mb-0">
            Update size information
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


            <!-- Size Name -->

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Size Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $_POST['name'] ?? $size['name']
                    ) ?>"
                    required
                >

            </div>


            <div class="row">

                <!-- Width -->

                <div class="col-md-6 mb-3">

                    <label class="form-label fw-semibold">
                        Width
                    </label>

                    <input
                        type="number"
                        name="width"
                        class="form-control"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars(
                            $_POST['width'] ?? ($size['width'] ?? '')
                        ) ?>"
                    >

                </div>


                <!-- Height -->

                <div class="col-md-6 mb-3">

                    <label class="form-label fw-semibold">
                        Height
                    </label>

                    <input
                        type="number"
                        name="height"
                        class="form-control"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars(
                            $_POST['height'] ?? ($size['height'] ?? '')
                        ) ?>"
                    >

                </div>

            </div>


            <!-- Unit -->

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Measurement Unit
                </label>

                <?php
                $currentUnit =
                    $_POST['unit']
                    ?? ($size['unit'] ?? 'mm');
                ?>

                <select name="unit" class="form-select">

                    <option
                        value="mm"
                        <?= $currentUnit === 'mm'
                            ? 'selected'
                            : '' ?>
                    >
                        Millimeter (mm)
                    </option>

                    <option
                        value="cm"
                        <?= $currentUnit === 'cm'
                            ? 'selected'
                            : '' ?>
                    >
                        Centimeter (cm)
                    </option>

                    <option
                        value="inch"
                        <?= $currentUnit === 'inch'
                            ? 'selected'
                            : '' ?>
                    >
                        Inch
                    </option>

                </select>

            </div>


            <!-- Status -->

            <div class="mb-4">

                <label class="form-label fw-semibold">
                    Status
                </label>

                <?php
                $currentStatus =
                    $_POST['status']
                    ?? $size['status'];
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


            <!-- Buttons -->

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-save"></i>
                    Update Size

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