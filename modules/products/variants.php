<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Product Variants";

require_once "../../includes/header.php";


/*
|--------------------------------------------------------------------------
| Get Product ID
|--------------------------------------------------------------------------
*/

$productId = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;

if ($productId <= 0) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name,
        b.name AS brand_name
    FROM products p

    LEFT JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN brands b
        ON b.id = p.brand_id

    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([$productId]);

$product = $stmt->fetch();


if (!$product) {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Variants
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pv.*,
        g.value AS gsm_value,
        g.unit AS gsm_unit,
        s.name AS size_name,
        s.width AS size_width,
        s.height AS size_height,
        s.unit AS size_unit,
        u.name AS unit_name,
        u.short_name AS unit_short_name,
        pv.purchase_rate AS purchase_price,
        COALESCE(NULLIF(pv.retail_rate, 0), NULLIF(pv.sale_rate, 0), 0) AS sale_price
    FROM product_variants pv
    LEFT JOIN gsm g ON g.id = pv.gsm_id
    LEFT JOIN sizes s ON s.id = pv.size_id
    LEFT JOIN units u ON u.id = pv.unit_id
    WHERE pv.product_id = ?
    ORDER BY pv.id DESC
");

$stmt->execute([$productId]);

$variants = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Calculate Total Stock
|--------------------------------------------------------------------------
*/

$totalStock = 0;

foreach ($variants as $variant) {
    $totalStock += (float) $variant['current_stock'];
}

?>

<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <div class="mb-2">

            <a
                href="index.php"
                class="text-decoration-none text-muted"
            >
                <i class="bi bi-arrow-left"></i>
                Back to Products
            </a>

        </div>

        <h4 class="mb-1">

            <?= htmlspecialchars(
                $product['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </h4>

        <p class="text-muted mb-0">

            Manage product variants

        </p>

    </div>


    <a
        href="variant_add.php?product_id=<?= $productId ?>"
        class="btn btn-primary"
    >

        <i class="bi bi-plus-lg"></i>

        Add Variant

    </a>

</div>


<!-- =========================================================
     PRODUCT INFORMATION
========================================================= -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <div class="row g-4">

            <!-- Product -->

            <div class="col-md-3">

                <small class="text-muted d-block">
                    Product
                </small>

                <strong>
                    <?= htmlspecialchars(
                        $product['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>


            <!-- Code -->

            <div class="col-md-2">

                <small class="text-muted d-block">
                    Product Code
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $product['product_code'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </strong>

            </div>


            <!-- Category -->

            <div class="col-md-2">

                <small class="text-muted d-block">
                    Category
                </small>

                <?= htmlspecialchars(
                    $product['category_name'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>


            <!-- Brand -->

            <div class="col-md-2">

                <small class="text-muted d-block">
                    Brand
                </small>

                <?= htmlspecialchars(
                    $product['brand_name'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>


            <!-- Stock -->

            <div class="col-md-3">

                <small class="text-muted d-block">
                    Total Stock
                </small>

                <strong class="fs-5">

                    <?= number_format($totalStock, 2) ?>

                </strong>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     VARIANTS TABLE
========================================================= -->

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h5 class="mb-1">
                    Product Variants
                </h5>

                <small class="text-muted">

                    <?= count($variants) ?>
                    variant(s)

                </small>

            </div>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th>#</th>

                        <th>GSM</th>

                        <th>Size</th>

                        <th>Dimensions</th>

                        <th>Unit</th>

                        <th>Purchase</th>

                        <th>Sale</th>

                        <th>Stock</th>

                        <th>Min Stock</th>

                        <th>SKU</th>

                        <th>Status</th>

                        <th width="130">Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($variants): ?>

                    <?php foreach ($variants as $variant): ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                <?= (int) $variant['id'] ?>

                            </td>


                            <!-- GSM -->

                            <td>

                                <?php if ($variant['gsm_value'] !== null): ?>

                                    <span class="badge bg-light text-dark border">

                                        <?= htmlspecialchars(
                                            $variant['gsm_value'] . ' ' . $variant['gsm_unit'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <!-- SIZE -->

                            <td>

                                <?= !empty($variant['size_name'])
                                    ? htmlspecialchars(
                                        $variant['size_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : '-'
                                ?>

                            </td>


                            <!-- DIMENSIONS -->

                            <td>

                                <?php

                                $width = $variant['size_width'];
                                $height = $variant['size_height'];

                                if (
                                    $width !== null &&
                                    $width !== '' &&
                                    $height !== null &&
                                    $height !== ''
                                ):

                                ?>

                                    <?= number_format(
                                        (float) $width,
                                        2
                                    ) ?>

                                    ×

                                    <?= number_format(
                                        (float) $height,
                                        2
                                    ) ?>
                                    <?= htmlspecialchars($variant['size_unit'] ?? '', ENT_QUOTES, 'UTF-8') ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <!-- UNIT -->

                            <td>

                                <?= htmlspecialchars(
                                    $variant['unit_short_name'] ?? 'pcs',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- PURCHASE PRICE -->

                            <td>

                                <?= number_format(
                                    (float) $variant['purchase_price'],
                                    2
                                ) ?>

                            </td>


                            <!-- SALE PRICE -->

                            <td>

                                <?= number_format(
                                    (float) $variant['sale_price'],
                                    2
                                ) ?>

                            </td>


                            <!-- CURRENT STOCK -->

                            <td>

                                <?php

                                $stock = (float) $variant['current_stock'];
                                $minimum = (float) $variant['minimum_stock'];

                                ?>

                                <?php if ($stock <= $minimum): ?>

                                    <span class="badge bg-danger">

                                        <?= number_format($stock, 2) ?>

                                    </span>

                                <?php else: ?>

                                    <strong>

                                        <?= number_format($stock, 2) ?>

                                    </strong>

                                <?php endif; ?>

                            </td>


                            <!-- MINIMUM STOCK -->

                            <td>

                                <?= number_format(
                                    $minimum,
                                    2
                                ) ?>

                            </td>


                            <!-- SKU -->

                            <td>

                                <?php if (!empty($variant['sku'])): ?>

                                    <span class="badge bg-light text-dark border">

                                        <?= htmlspecialchars(
                                            $variant['sku'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if ((int) $variant['status'] === 1): ?>

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="variant_add.php?product_id=<?= $productId ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Add another variant"
                                >
                                    <i class="bi bi-plus-lg"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php else: ?>

                    <!-- NO VARIANTS -->

                    <tr>

                        <td
                            colspan="12"
                            class="text-center text-muted py-5"
                        >

                            <i class="bi bi-layers fs-1 d-block mb-3"></i>

                            <h6>
                                No variants found
                            </h6>

                            <p class="mb-3">
                                This product does not have any variants yet.
                            </p>

                            <a
                                href="variant_add.php?product_id=<?= $productId ?>"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-plus-lg"></i>

                                Add First Variant

                            </a>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>