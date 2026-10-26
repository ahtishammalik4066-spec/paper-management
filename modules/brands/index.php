<?php

$pageTitle = "Brands";

require_once "../../includes/header.php";

$stmt = $pdo->query("SELECT * FROM brands ORDER BY id DESC");
$brands = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">Brands</h4>
        <p class="text-muted mb-0">Manage product brands</p>
    </div>

    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Add Brand
    </a>

</div>


<div class="card shadow-sm border-0">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>Brand Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (count($brands) > 0): ?>

                    <?php foreach ($brands as $brand): ?>

                        <tr>

                            <td>
                                <?= $brand['id'] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($brand['name']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($brand['description'] ?? '') ?>
                            </td>

                            <td>

                                <?php if ($brand['status'] === 'active'): ?>

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a
                                    href="edit.php?id=<?= $brand['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <a
                                    href="delete.php?id=<?= $brand['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to delete this brand?')"
                                >
                                    <i class="bi bi-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="5" class="text-center text-muted py-4">
                            No brands found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>