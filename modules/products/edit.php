
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Edit Product";

require_once "../../includes/header.php";


// =====================================================
// GET PRODUCT ID
// =====================================================

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


// =====================================================
// LOAD PRODUCT
// =====================================================

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php");
    exit;
}


// =====================================================
// LOAD CATEGORIES
// =====================================================

$categories = [];

try {

    $categoryStmt = $pdo->query("
        SELECT id, name
        FROM categories
        ORDER BY name ASC
    ");

    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = "Category loading error: " . $e->getMessage();
}


// =====================================================
// LOAD BRANDS
// =====================================================

$brands = [];

try {

    $brandStmt = $pdo->query("
        SELECT id, name
        FROM brands
        WHERE status = 1
        ORDER BY name ASC
    ");

    $brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);

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

            $categoryCheck = $pdo->prepare("
                SELECT id
                FROM categories
                WHERE id = ?
                LIMIT 1
            ");

            $categoryCheck->execute([$category_id]);

            if (!$categoryCheck->fetch()) {

                $error = "Selected category does not exist.";

            }


            // =============================================
            // CHECK PRODUCT CODE
            // =============================================

            if ($error === "") {

                $check = $pdo->prepare("
                    SELECT id
                    FROM products
                    WHERE product_code = ?
                    AND id != ?
                    LIMIT 1
                ");

                $check->execute([
                    $product_code,
                    $id
                ]);

                if ($check->fetch()) {

                    $error = "Another product with this product code already exists.";

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
                    AND id != ?
                    LIMIT 1
                ");

                $checkBarcode->execute([
                    $barcode,
                    $id
                ]);

                if ($checkBarcode->fetch()) {

                    $error = "Another product with this barcode already exists.";

                }
            }


            // =============================================
            // UPDATE PRODUCT
            // =============================================

            if ($error === "") {

                $update = $pdo->prepare("
                    UPDATE products
                    SET
                        category_id = ?,
                        brand_id = ?,
                        name = ?,
                        product_code = ?,
                        barcode = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $category_id,
                    $brand_id,
                    $name,
                    $product_code,
                    $barcode !== "" ? $barcode : null,
                    $description,
                    $status === "active" ? 1 : 0,
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


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="mb-1">
            Edit Product
        </h4>

        <p class="text-muted mb-0">
            Update product information
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
     ERROR MESSAGE
===================================================== -->

<?php if ($error !== ""): ?>

    <div class="alert alert-danger">

        <i class="bi bi-exclamation-triangle"></i>

        <?= htmlspecialchars($error) ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     FORM
===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <form method="POST">

            <div class="row g-3">


                <!-- =========================================
                     CATEGORY
                ========================================== -->

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
                                <?=
                                    (
                                        (
                                            $_SERVER["REQUEST_METHOD"] === "POST"
                                                ? (int)($_POST['category_id'] ?? 0)
                                                : (int)($product['category_id'] ?? 0)
                                        )
                                        ===
                                        (int)$category['id']
                                    )
                                    ? 'selected'
                                    : ''
                                ?>
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


                <!-- =========================================
                     BRAND
                ========================================== -->

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
                                <?=
                                    (
                                        (
                                            $_SERVER["REQUEST_METHOD"] === "POST"
                                                ? (int)($_POST['brand_id'] ?? 0)
                                                : (int)($product['brand_id'] ?? 0)
                                        )
                                        ===
                                        (int)$brand['id']
                                    )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars($brand['name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- =========================================
                     PRODUCT NAME
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        Product Name

                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $_POST['name'] ?? $product['name'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <!-- =========================================
                     PRODUCT CODE
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        Product Code

                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        name="product_code"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $_POST['product_code'] ?? $product['product_code'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <!-- =========================================
                     BARCODE
                ========================================== -->

                <div class="col-md-6">

                    <label class="form-label">

                        Barcode

                    </label>


                    <input
                        type="text"
                        name="barcode"
                        class="form-control"
                        value="<?= htmlspecialchars(
                            $_POST['barcode'] ?? $product['barcode'] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- =========================================
                     STATUS
                ========================================== -->

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
                            <?=
                                (
                                    ($_POST['status'] ?? $product['status'])
                                    === 'active'
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Active
                        </option>


                        <option
                            value="inactive"
                            <?=
                                (
                                    ($_POST['status'] ?? $product['status'])
                                    === 'inactive'
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- =========================================
                     DESCRIPTION
                ========================================== -->

                <div class="col-12">

                    <label class="form-label">

                        Description

                    </label>


                    <textarea
                        name="description"
                        rows="4"
                        class="form-control"
                        placeholder="Product description..."
                    ><?= htmlspecialchars(
                        $_POST['description']
                        ?? $product['description']
                        ?? ''
                    ) ?></textarea>

                </div>


                <!-- =========================================
                     BUTTONS
                ========================================== -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Update Product

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
