<?php

$pageTitle = "Edit Color";

require_once "../../includes/header.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


$stmt = $pdo->prepare("
    SELECT *
    FROM colors
    WHERE id = ?
");

$stmt->execute([$id]);

$color = $stmt->fetch();

if (!$color) {
    header("Location: index.php");
    exit;
}


$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $colorCode = trim($_POST["color_code"] ?? "");
    $status = $_POST["status"] ?? "active";


    if ($name === "") {

        $error = "Color name is required.";

    } elseif (!in_array($status, ["active", "inactive"], true)) {

        $error = "Invalid status.";

    } else {

        try {

            $check = $pdo->prepare("
                SELECT id
                FROM colors
                WHERE name = ?
                AND id != ?
                LIMIT 1
            ");

            $check->execute([
                $name,
                $id
            ]);

            if ($check->fetch()) {

                $error = "Another color with this name already exists.";

            } else {

                $update = $pdo->prepare("
                    UPDATE colors
                    SET
                        name = ?,
                        color_code = ?,
                        status = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $name,
                    $colorCode,
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
            Edit Color
        </h4>

        <p class="text-muted mb-0">
            Update color information
        </p>

    </div>

    <a href="index.php" class="btn btn-secondary">

        <i class="bi bi-arrow-left"></i>
        Back

    </a>

</div>


<?php if ($error): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Color Name
                </label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        $_POST['name'] ?? $color['name']
                    ) ?>"
                    required
                >

            </div>


            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Color Code
                </label>

                <?php
                $currentColor = $_POST['color_code']
                    ?? ($color['color_code'] ?: '#ffffff');
                ?>

                <div class="input-group">

                    <input
                        type="color"
                        name="color_code"
                        class="form-control form-control-color"
                        value="<?= htmlspecialchars($currentColor) ?>"
                    >

                    <input
                        type="text"
                        id="colorCodeText"
                        class="form-control"
                        value="<?= htmlspecialchars($currentColor) ?>"
                        readonly
                    >

                </div>

            </div>


            <div class="mb-4">

                <label class="form-label fw-semibold">
                    Status
                </label>

                <?php
                $currentStatus = $_POST['status']
                    ?? $color['status'];
                ?>

                <select name="status" class="form-select">

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


            <div class="d-flex gap-2">

                <button type="submit" class="btn btn-primary">

                    <i class="bi bi-save"></i>
                    Update Color

                </button>

                <a href="index.php" class="btn btn-light border">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>


<script>

const colorInput = document.querySelector('input[name="color_code"]');
const colorText = document.getElementById('colorCodeText');

colorInput.addEventListener('input', function () {
    colorText.value = this.value;
});

</script>


<?php require_once "../../includes/footer.php"; ?>