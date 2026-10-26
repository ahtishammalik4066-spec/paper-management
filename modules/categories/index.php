<?php

$pageTitle = "Categories";

require_once "../../includes/header.php";

$stmt = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY id DESC
");

$categories = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h4 class="mb-1">Categories</h4>
        <p class="text-muted mb-0">
            Manage product categories
        </p>
    </div>

    <a
        href="add.php"
        class="btn btn-dark"
    >
        <i class="bi bi-plus-lg"></i>
        Add Category
    </a>

</div>

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (!$categories): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="text-center text-muted py-5"
                        >
                            No categories found.
                        </td>

                    </tr>

                <?php endif; ?>

                <?php foreach ($categories as $category): ?>

                    <tr>

                        <td>
                            <?= $category['id'] ?>
                        </td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars($category['name']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $category['description'] ?? ''
                            ) ?>
                        </td>

                        <td>

                            <?php if ($category['status']): ?>

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
                            <?= date(
                                'd M Y',
                                strtotime($category['created_at'])
                            ) ?>
                        </td>

                        <td class="text-end">

                            <a
                                href="edit.php?id=<?= $category['id'] ?>"
                                class="btn btn-sm btn-outline-primary"
                            >
                                Edit
                            </a>

                            <a
                                href="delete.php?id=<?= $category['id'] ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete this category?')"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>