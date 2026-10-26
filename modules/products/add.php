
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Add Product";

require_once "../../includes/header.php";


// =====================================================
// LOAD CATEGORIES
// =====================================================

$categories = [];

try {

    $stmt = $pdo->query("
        SELECT id, name
        FROM categories
        ORDER BY name ASC
    ");

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = "Category Error: " . $e->getMessage();
}


// =====================================================
// LOAD BRANDS
// =====================================================

$brands = [];

try {

    $stmt = $pdo->query("
        SELECT id, name
        FROM brands
        WHERE status = 1
        ORDER BY name ASC
    ");

    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $brands = [];
}


$error = $error ?? "";


// =====================================================
// FORM SUBMIT
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_id = (int)($_POST["category_id"] ?? 0);

    $brand_id = !empty($_POST["brand_id"])
        ? (int)$_POST["brand_id"]
        : null;

    $name = trim($_POST["name"] ?? "");

    $product_code = trim($_POST["product_code"] ?? "");

    $barcode = trim($_POST["barcode"] ?? "");

    $description = trim($_POST["description"] ?? "");

    $status = $_POST["status"] ?? "active";


    // =================================================
    // VALIDATION
    // =================================================

    if ($category_id <= 0) {

        $error = "Please select a category.";

    } elseif ($name === "") {

        $error = "Product name is required.";

    } elseif ($product_code === "") {

        $error = "Product code is required.";

    } elseif (!in_array($status, ["active", "inactive"], true)) {

        $error = "Invalid status.";

    } else {

        try {

            // =============================================
            // CHECK CATEGORY
            // =============================================

            $checkCategory = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE id = ?
                LIMIT 1
            ");

            $checkCategory->execute([$category_id]);

            if (!$checkCategory->fetch()) {

                $error = "Selected category does not exist.";

            }


            // =============================================
            // CHECK PRODUCT CODE
            // =============================================

            if ($error === "") {

                $checkProduct = $pdo->prepare("
                    SELECT id
                    FROM products
                    WHERE product_code = ?
                    LIMIT 1
                ");

                $checkProduct->execute([$product_code]);

                if ($checkProduct->fetch()) {

                    $error = "Product code already exists.";

                }
            }


            // =============================================
            // CHECK BARCODE
            // =============================================

            if ($error === "" && $barcode !== "") {

                $checkBarcode = $pdo->prepare("
                    SELECT id
                    FROM products
                    WHERE barcode = ?
                    LIMIT 1
                ");

                $checkBarcode->execute([$barcode]);

                if ($checkBarcode->fetch()) {

                    $error = "Barcode already exists.";

                }
            }


            // =============================================
            // INSERT PRODUCT
            // =============================================

            if ($error === "") {

                $insert = $pdo->prepare("
                    INSERT INTO products
                    (
                        category_id,
                        brand_id,
                        name,
                        product_code,
                        barcode,
                        description,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $insert->execute([
                    $category_id,
                    $brand_id,
                    $name,
                    $product_code,
                    $barcode !== "" ? $barcode : null,
                    $description,
                    $status === "active" ? 1 : 0
                ]);


                $newProductId = (int) $pdo->lastInsertId();
                header("Location: variant_add.php?product_id=" . $newProductId);
                exit;
            }

        } catch (PDOException $e) {

            $error = "Database Error: " . $e->getMessage();
        }
    }
}

?>


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="mb-1">
            Add Product
        </h4>

        <p class="text-muted mb-0">
            Create a new paper product
        </p>

    </div>


    <a
        href="index.php"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-arrow-left"></i>

        Back

    </a>

</div>


<!-- =====================================================
     ERROR
===================================================== -->

<?php if ($error !== ""): ?>

    <div class="alert alert-danger">

        <i class="bi bi-exclamation-triangle"></i>

        <?= htmlspecialchars($error) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     PRODUCT FORM
===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="row g-3">


                <!-- CATEGORY -->

                <div class="col-md-6">

                    <label class="form-label">

                        Category

                        <span class="text-danger">*</span>

                    </label>


                    <select
                        name="category_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Select Category --
                        </option>


                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int)$category['id'] ?>"
                                <?= (
                                    isset($_POST['category_id']) &&
                                    (int)$_POST['category_id'] ===
                                    (int)$category['id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($category['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <?php if (empty($categories)): ?>

                        <small class="text-danger">
                            No categories found in database.
                        </small>

                    <?php else: ?>

                        <small class="text-success">
                            <?= count($categories) ?> categories available
                        </small>

                    <?php endif; ?>

                </div>


                <!-- BRAND -->

                <div class="col-md-6">

                    <label class="form-label">
                        Brand
                    </label>


                    <select
                        name="brand_id"
                        class="form-select"
                    >

                        <option value="">
                            -- Select Brand --
                        </option>


                        <?php foreach ($brands as $brand): ?>

                            <option
                                value="<?= (int)$brand['id'] ?>"
                                <?= (
                                    isset($_POST['brand_id']) &&
                                    (int)$_POST['brand_id'] ===
                                    (int)$brand['id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($brand['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- PRODUCT NAME -->

                <div class="col-md-6">

                    <label class="form-label">

                        Product Name

                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="e.g. Art Paper"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- PRODUCT CODE -->

                <div class="col-md-6">

                    <label class="form-label">

                        Product Code

                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        name="product_code"
                        class="form-control"
                        placeholder="e.g. ART-001"
                        value="<?= htmlspecialchars($_POST['product_code'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- BARCODE -->

                <div class="col-md-6">

                    <label class="form-label">
                        Barcode
                    </label>


                    <input
                        type="text"
                        name="barcode"
                        class="form-control"
                        placeholder="Optional barcode"
                        value="<?= htmlspecialchars($_POST['barcode'] ?? '') ?>"
                    >

                </div>


                <!-- STATUS -->

                <div class="col-md-6">

                    <label class="form-label">
                        Status
                    </label>


                    <select
                        name="status"
                        class="form-select"
                    >

                        <option
                            value="active"
                            <?= ($_POST['status'] ?? 'active') === 'active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>


                        <option
                            value="inactive"
                            <?= ($_POST['status'] ?? '') === 'inactive'
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- DESCRIPTION -->

                <div class="col-12">

                    <label class="form-label">
                        Description
                    </label>


                    <textarea
                        name="description"
                        rows="4"
                        class="form-control"
                        placeholder="Product description..."
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                </div>


                <!-- BUTTONS -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Save Product

                    </button>


                    <a
                        href="index.php"
                        class="btn btn-light"
                    >

                        Cancel

                    </a>

                </div>


            </div>

        </form>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>
