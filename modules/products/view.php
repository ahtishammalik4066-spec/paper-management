<?php

$pageTitle = "Product Details";

require_once "../../includes/header.php";


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($id <= 0) {
    header("Location: index.php");
    exit;
}


$stmt = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name,
        b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$product = $stmt->fetch();


if (!$product) {
    header("Location: index.php");
    exit;
}


// Get variants

$variantStmt = $pdo->prepare("
    SELECT
        pv.*,
        c.name AS color_name,
        g.value AS gsm_value,
        g.unit AS gsm_unit,
        s.name AS size_name,
        u.name AS unit_name,
        u.short_name AS unit_short_name
    FROM product_variants pv
    LEFT JOIN colors c ON c.id = pv.color_id
    LEFT JOIN gsm g ON g.id = pv.gsm_id
    LEFT JOIN sizes s ON s.id = pv.size_id
    LEFT JOIN units u ON u.id = pv.unit_id
    WHERE pv.product_id = ?
    ORDER BY pv.id DESC
");

$variantStmt->execute([$id]);

$variants = $variantStmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="mb-1">
            <?= htmlspecialchars($product['name']) ?>
        </h4>

        <p class="text-muted mb-0">
            Product details and variants
        </p>

    </div>


    <div>

        <a
            href="edit.php?id=<?= $product['id'] ?>"
            class="btn btn-primary"
        >
            <i class="bi bi-pencil"></i>
            Edit Product
        </a>

        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >
            Back
        </a>

    </div>

</div>


<!-- Product Information -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <h5 class="mb-4">
            Product Information
        </h5>

        <div class="row g-4">

            <div class="col-md-4">

                <small class="text-muted d-block">
                    Product Name
                </small>

                <strong>
                    <?= htmlspecialchars($product['name']) ?>
                </strong>

            </div>


            <div class="col-md-4">

                <small class="text-muted d-block">
                    Product Code
                </small>

                <strong>
                    <?= htmlspecialchars($product['product_code']) ?>
                </strong>

            </div>


            <div class="col-md-4">

                <small class="text-muted d-block">
                    Barcode
                </small>

                <strong>
                    <?= htmlspecialchars($product['barcode'] ?? '-') ?>
                </strong>

            </div>


            <div class="col-md-4">

                <small class="text-muted d-block">
                    Category
                </small>

                <strong>
                    <?= htmlspecialchars($product['category_name'] ?? '-') ?>
                </strong>

            </div>


            <div class="col-md-4">

                <small class="text-muted d-block">
                    Brand
                </small>

                <strong>
                    <?= htmlspecialchars($product['brand_name'] ?? '-') ?>
                </strong>

            </div>


            <div class="col-md-4">

                <small class="text-muted d-block">
                    Status
                </small>

                <?php if ((int) $product['status'] === 1): ?>

                    <span class="badge bg-success">
                        Active
                    </span>

                <?php else: ?>

                    <span class="badge bg-secondary">
                        Inactive
                    </span>

                <?php endif; ?>

            </div>


            <div class="col-12">

                <small class="text-muted d-block">
                    Description
                </small>

                <p class="mb-0">
                    <?= nl2br(htmlspecialchars($product['description'] ?? '-')) ?>
                </p>

            </div>

        </div>

    </div>

</div>


<!-- Product Variants -->

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h5 class="mb-1">
                    Product Variants
                </h5>

                <p class="text-muted mb-0">
                    GSM, color, size, rates and stock
                </p>

            </div>

            <span class="badge bg-primary">
                <?= count($variants) ?> Variants
            </span>

        </div>


        <div class="table-responsive">

            <table class="table table-hover">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>SKU</th>
                        <th>Color</th>
                        <th>GSM</th>
                        <th>Size</th>
                        <th>Unit</th>
                        <th>Sale Rate</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                    </tr>

                </thead>


                <tbody>

                <?php if ($variants): ?>

                    <?php foreach ($variants as $variant): ?>

                        <tr>

                            <td>
                                <?= $variant['id'] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($variant['sku']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($variant['color_name'] ?? '-') ?>
                            </td>

                            <td>

                                <?php if ($variant['gsm_value'] !== null): ?>

                                    <?= htmlspecialchars($variant['gsm_value']) ?>
                                    <?= htmlspecialchars($variant['gsm_unit'] ?? 'GSM') ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= htmlspecialchars($variant['size_name'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($variant['unit_name'] ?? '-') ?>
                            </td>

                            <td>

                                Rs.
                                <?= number_format((float) ($variant['retail_rate'] > 0 ? $variant['retail_rate'] : $variant['sale_rate']), 2) ?>

                            </td>

                            <td>

                                <strong>
                                    <?= number_format((float)$variant['current_stock'], 2) ?>
                                </strong>

                            </td>

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

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="9"
                            class="text-center text-muted py-5"
                        >

                            <i class="bi bi-box-seam fs-2 d-block mb-2"></i>

                            No variants found.

                            <br>

                            Product variants will be added in the next step.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>