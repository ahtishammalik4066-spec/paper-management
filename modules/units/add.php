<?php

$pageTitle = "Add Unit";

require_once "../../includes/header.php";

$error = "";

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
                LIMIT 1
            ");

            $check->execute([$name]);


            if ($check->fetch()) {

                $error = "This unit already exists.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Insert
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO units
                    (
                        name,
                        short_name,
                        status
                    )
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $shortName,
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
            Add Unit
        </h4>

        <p class="text-muted mb-0">
            Add a new measurement unit
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
                    placeholder="Example: Sheet"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
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
                    placeholder="Example: pcs"
                    value="<?= htmlspecialchars($_POST['short_name'] ?? '') ?>"
                    required
                >

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
                    Save Unit

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