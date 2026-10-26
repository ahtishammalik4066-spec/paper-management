<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Sizes";

require_once "../../includes/header.php";

$stmt = $pdo->query("
    SELECT *
    FROM sizes
    ORDER BY id DESC
");

$sizes = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">Sizes</h4>
        <p class="text-muted mb-0">
            Manage paper and product sizes
        </p>
    </div>

    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Add Size
    </a>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>Size Name</th>
                        <th>Width</th>
                        <th>Height</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($sizes): ?>

                    <?php foreach ($sizes as $size): ?>

                        <tr>

                            <td>
                                <?= $size['id'] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($size['name']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($size['width'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($size['height'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($size['unit'] ?? '-') ?>
                            </td>

                            <td>

                                <?php if ($size['status'] === 'active'): ?>

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
                                    href="edit.php?id=<?= $size['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <a
                                    href="delete.php?id=<?= $size['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to delete this size?')"
                                >
                                    <i class="bi bi-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-muted py-4"
                        >
                            No sizes found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>