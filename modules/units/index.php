<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Units";

require_once "../../includes/header.php";

$stmt = $pdo->query("
    SELECT *
    FROM units
    ORDER BY id DESC
");

$units = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">Units</h4>
        <p class="text-muted mb-0">
            Manage product measurement units
        </p>
    </div>

    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Add Unit
    </a>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>Unit Name</th>
                        <th>Short Name</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($units): ?>

                    <?php foreach ($units as $unit): ?>

                        <tr>

                            <td>
                                <?= $unit['id'] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($unit['name']) ?>
                                </strong>
                            </td>

                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($unit['short_name'] ?? '-') ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($unit['status'] === 'active'): ?>

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
                                    href="edit.php?id=<?= $unit['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <a
                                    href="delete.php?id=<?= $unit['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to delete this unit?')"
                                >
                                    <i class="bi bi-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center text-muted py-4"
                        >
                            No units found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>