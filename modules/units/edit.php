<?php

$pageTitle = "Edit Unit";

require_once "../../includes/header.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get Unit
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM units
    WHERE id = ?
");

$stmt->execute([$id]);

$unit = $stmt->fetch();


if (!$unit) {

    header("Location: index.php");
    exit;

}


$error = "";


/*
|--------------------------------------------------------------------------
| Update Unit
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $shortName = trim($_POST["short_name"] ?? "");
    $status = $_POST["status"] ?? "active";


    if ($name === "") {

        $error = "Unit name is required.";

    } elseif ($shortName === "") {

        $error = "Short name is required.";

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
                FROM units
                WHERE name = ?
                AND id != ?
                LIMIT 1
            ");

            $check->execute([
                $name,
                $id
            ]);


            if ($check->fetch()) {

                $error = "Another unit with this name already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Update
                |--------------------------------------------------------------------------
                */

                $update = $pdo->prepare("
                    UPDATE units
                    SET
                        name = ?,
                        short_name = ?,
                        status = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $name,
                    $shortName,
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
            Edit Unit
        </h4>

        <p class="text-muted mb-0">
            Update unit information
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


            <!-- Unit Name -->

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Unit Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $_POST['name'] ?? $unit['name']
                    ) ?>"
                    required
                >

            </div>


            <!-- Short Name -->

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Short Name
                </label>

                <input
                    type="text"
                    name="short_name"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $_POST['short_name'] ?? $unit['short_name']
                    ) ?>"
                    required
                >

            </div>


            <!-- Status -->

            <div class="mb-4">

                <label class="form-label fw-semibold">
                    Status
                </label>

                <?php

                $currentStatus =
                    $_POST['status']
                    ?? $unit['status'];

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
                    Update Unit

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