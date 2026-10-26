<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Products";

require_once "../../includes/header.php";


/*
|--------------------------------------------------------------------------
| Fetch Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT 
        p.*,

        c.name AS category_name,

        b.name AS brand_name,

        (
            SELECT COUNT(*)
            FROM product_variants pv
            WHERE pv.product_id = p.id
        ) AS variant_count,

        (
            SELECT COALESCE(SUM(pv.current_stock), 0)
            FROM product_variants pv
            WHERE pv.product_id = p.id
        ) AS total_stock

    FROM products p

    LEFT JOIN categories c 
        ON c.id = p.category_id

    LEFT JOIN brands b 
        ON b.id = p.brand_id

    ORDER BY p.id DESC
");

$products = $stmt->fetchAll();

?>

<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h4 class="mb-1">
            Products
        </h4>

        <p class="text-muted mb-0">
            Manage your paper products
        </p>

    </div>


    <a href="add.php" class="btn btn-primary">

        <i class="bi bi-plus-lg"></i>

        Add Product

    </a>

</div>


<!-- =========================================================
     PRODUCTS TABLE
========================================================= -->

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead class="table-light">

                    <tr>

                        <th>#</th>

                        <th>
                            Product
                        </th>

                        <th>
                            Code
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Brand
                        </th>

                        <th>
                            Variants
                        </th>

                        <th>
                            Total Stock
                        </th>

                        <th>
                            Status
                        </th>

                        <th width="190">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($products): ?>

                    <?php foreach ($products as $product): ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                <?= (int)$product['id'] ?>

                            </td>


                            <!-- PRODUCT -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $product['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>


                                <?php if (!empty($product['barcode'])): ?>

                                    <br>

                                    <small class="text-muted">

                                        Barcode:

                                        <?= htmlspecialchars(
                                            $product['barcode'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- PRODUCT CODE -->

                            <td>

                                <span class="badge bg-light text-dark border">

                                    <?= htmlspecialchars(
                                        $product['product_code'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                <?= htmlspecialchars(
                                    $product['category_name'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- BRAND -->

                            <td>

                                <?= htmlspecialchars(
                                    $product['brand_name'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- VARIANTS -->

                            <td>

                                <?php if ((int)$product['variant_count'] > 0): ?>

                                    <span class="badge bg-info">

                                        <?= (int)$product['variant_count'] ?>

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-light text-muted border">

                                        0

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- TOTAL STOCK -->

                            <td>

                                <strong>

                                    <?= number_format(
                                        (float)$product['total_stock'],
                                        2
                                    ) ?>

                                </strong>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if ((int) $product['status'] === 1): ?>

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <!-- View -->

                                <a
                                    href="view.php?id=<?= (int)$product['id'] ?>"
                                    class="btn btn-sm btn-outline-info"
                                    title="View Product"
                                >

                                    <i class="bi bi-eye"></i>

                                </a>


                                <!-- Variants -->

                                <a
                                    href="variants.php?product_id=<?= (int)$product['id'] ?>"
                                    class="btn btn-sm btn-outline-success"
                                    title="Manage Variants"
                                >

                                    <i class="bi bi-layers"></i>

                                </a>


                                <!-- Edit -->

                                <a
                                    href="edit.php?id=<?= (int)$product['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Edit Product"
                                >

                                    <i class="bi bi-pencil"></i>

                                </a>


                                <!-- Delete -->

                                <a
                                    href="delete.php?id=<?= (int)$product['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Delete Product"
                                    onclick="return confirm('Are you sure you want to delete this product?')"
                                >

                                    <i class="bi bi-trash"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                <?php else: ?>

                    <!-- NO PRODUCTS -->

                    <tr>

                        <td
                            colspan="9"
                            class="text-center text-muted py-5"
                        >

                            <i class="bi bi-box-seam fs-2 d-block mb-2"></i>


                            <div class="mb-2">

                                No products found.

                            </div>


                            <a
                                href="add.php"
                                class="btn btn-primary btn-sm"
                            >

                                <i class="bi bi-plus-lg"></i>

                                Add First Product

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