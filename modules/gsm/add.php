<?php

$pageTitle = "Add GSM";

require_once "../../includes/header.php";

$error = "";

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
            | Check duplicate GSM
            |--------------------------------------------------------------------------
            */

            $check = $pdo->prepare("
                SELECT id
                FROM gsm
                WHERE value = ?
                LIMIT 1
            ");

            $check->execute([
                $value
            ]);


            if ($check->fetch()) {

                $error = "This GSM value already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Insert
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO gsm
                    (value, unit, status)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $value,
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
            Add GSM
        </h4>

        <p class="text-muted mb-0">
            Add a new GSM / gram value
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
                    placeholder="Example: 120"
                    min="0.01"
                    step="0.01"
                    value="<?= htmlspecialchars($_POST['value'] ?? '') ?>"
                    required
                >

                <small class="text-muted">
                    Enter paper weight, e.g. 80, 100, 120, 150 GSM.
                </small>

            </div>


            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Unit
                </label>

                <input
                    type="text"
                    name="unit"
                    class="form-control"
                    value="<?= htmlspecialchars($_POST['unit'] ?? 'GSM') ?>"
                    placeholder="GSM"
                    required
                >

            </div>


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


            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-check-lg"></i>
                    Save GSM

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