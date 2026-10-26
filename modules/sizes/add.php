<?php

$pageTitle = "Add Size";

require_once "../../includes/header.php";

$error = "";

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
            | Check Duplicate Size
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM sizes
                WHERE name = ?
                LIMIT 1
            ");

            $check->execute([$name]);


            if ($check->fetch()) {

                $error = "This size already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Insert
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO sizes
                    (
                        name,
                        width,
                        height,
                        unit,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $width !== "" ? $width : null,
                    $height !== "" ? $height : null,
                    $unit,
                    $status
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
            Add Size
        </h4>

        <p class="text-muted mb-0">
            Add a new paper/product size
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
                    placeholder="Example: A4"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
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
                        placeholder="Example: 210"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars($_POST['width'] ?? '') ?>"
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
                        placeholder="Example: 297"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars($_POST['height'] ?? '') ?>"
                    >

                </div>

            </div>


            <!-- Unit -->

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Measurement Unit
                </label>

                <select name="unit" class="form-select">

                    <option
                        value="mm"
                        <?= ($_POST['unit'] ?? 'mm') === 'mm'
                            ? 'selected'
                            : '' ?>
                    >
                        Millimeter (mm)
                    </option>

                    <option
                        value="cm"
                        <?= ($_POST['unit'] ?? '') === 'cm'
                            ? 'selected'
                            : '' ?>
                    >
                        Centimeter (cm)
                    </option>

                    <option
                        value="inch"
                        <?= ($_POST['unit'] ?? '') === 'inch'
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

                <select name="status" class="form-select">

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
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

                    <i class="bi bi-check-lg"></i>
                    Save Size

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