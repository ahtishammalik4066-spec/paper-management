<?php

$pageTitle = "Colors";

require_once "../../includes/header.php";

$stmt = $pdo->query("SELECT * FROM colors ORDER BY id DESC");
$colors = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">Colors</h4>
        <p class="text-muted mb-0">Manage paper/product colors</p>
    </div>

    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i>
        Add Color
    </a>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover">

                <thead class="table-light">

                    <tr>
                        <th>#</th>
                        <th>Color</th>
                        <th>Color Code</th>
                        <th>Preview</th>
                        <th>Status</th>
                        <th width="150">Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($colors): ?>

                    <?php foreach ($colors as $color): ?>

                        <tr>

                            <td>
                                <?= $color['id'] ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($color['name']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($color['color_code'] ?? '') ?>
                            </td>

                            <td>

                                <?php if (!empty($color['color_code'])): ?>

                                    <span
                                        style="
                                            display:inline-block;
                                            width:35px;
                                            height:35px;
                                            border-radius:6px;
                                            border:1px solid #ddd;
                                            background:<?= htmlspecialchars($color['color_code']) ?>;
                                        "
                                    ></span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        No color
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($color['status'] === 'active'): ?>

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
                                    href="edit.php?id=<?= $color['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <a
                                    href="delete.php?id=<?= $color['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to delete this color?')"
                                >
                                    <i class="bi bi-trash"></i>
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" class="text-center text-muted py-4">
                            No colors found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php require_once "../../includes/footer.php"; ?>