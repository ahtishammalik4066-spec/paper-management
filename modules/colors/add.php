<?php

$pageTitle = "Add Color";

require_once "../../includes/header.php";

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
                LIMIT 1
            ");

            $check->execute([$name]);

            if ($check->fetch()) {

                $error = "This color already exists.";

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO colors
                    (name, color_code, status)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $colorCode,
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
            Add Color
        </h4>

        <p class="text-muted mb-0">
            Add a new paper/product color
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
                    placeholder="Example: White"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    required
                >

            </div>


            <div class="mb-3">

                <label class="form-label fw-semibold">
                    Color Code
                </label>

                <div class="input-group">

                    <input
                        type="color"
                        name="color_code"
                        class="form-control form-control-color"
                        value="<?= htmlspecialchars($_POST['color_code'] ?? '#ffffff') ?>"
                    >

                    <input
                        type="text"
                        id="colorCodeText"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['color_code'] ?? '#ffffff') ?>"
                        readonly
                    >

                </div>

                <small class="text-muted">
                    Select the color used for this paper.
                </small>

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

                <button type="submit" class="btn btn-primary">

                    <i class="bi bi-check-lg"></i>
                    Save Color

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