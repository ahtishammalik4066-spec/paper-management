<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "GSM / Gram";

require_once "../../includes/header.php";

$pageTitle = "GSM / Gram";

require_once "../../includes/header.php";

$stmt = $pdo->query("
    SELECT *
    FROM gsm
    ORDER BY value ASC
");

$gsmList = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">GSM / Gram</h4>
        <p class="text-muted mb-0">
            Manage paper GSM / weight values
        </p>
    </div>

    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Add GSM
    </a>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>GSM Value</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($gsmList): ?>

                    <?php foreach ($gsmList as $gsm): ?>

                        <tr>

                            <td>
                                <?= $gsm['id'] ?>
                            </td>

                            <td>

                                <strong>
                                    <?= htmlspecialchars($gsm['value']) ?>
                                </strong>

                            </td>

                            <td>
                                <?= htmlspecialchars($gsm['unit'] ?? 'GSM') ?>
                            </td>

                            <td>

                                <?php if ($gsm['status'] === 'active'): ?>

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
                                    href="edit.php?id=<?= $gsm['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <a
                                    href="delete.php?id=<?= $gsm['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to delete this GSM?')"
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
                            No GSM values found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>