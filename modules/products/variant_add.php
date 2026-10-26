<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";

$pageTitle = "Add Product Variant";
$productId = filter_input(INPUT_GET, "product_id", FILTER_VALIDATE_INT) ?: 0;
$productQuery = $pdo->prepare("
    SELECT p.*, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE p.id = ?
    LIMIT 1
");
$productQuery->execute([$productId]);
$product = $productQuery->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

$attributes = [
    "colors" => $pdo->query("SELECT id, name FROM colors WHERE status = 1 ORDER BY name")->fetchAll(),
    "gsm" => $pdo->query("SELECT id, value, unit FROM gsm WHERE status = 1 ORDER BY value")->fetchAll(),
    "sizes" => $pdo->query("SELECT id, name FROM sizes WHERE status = 1 ORDER BY name")->fetchAll(),
    "units" => $pdo->query("SELECT id, name, short_name FROM units WHERE status = 1 ORDER BY name")->fetchAll(),
];

$error = "";
$values = [
    "color_id" => "",
    "gsm_id" => "",
    "size_id" => "",
    "unit_id" => "",
    "sku" => "",
    "purchase_rate" => "",
    "sale_rate" => "",
    "wholesale_rate" => "",
    "retail_rate" => "",
    "minimum_sale_rate" => "",
    "minimum_stock" => "0",
    "opening_stock" => "0",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($values as $name => $default) {
        $values[$name] = trim((string) ($_POST[$name] ?? $default));
    }

    $numericFields = [
        "purchase_rate" => "Purchase rate",
        "sale_rate" => "Sale rate",
        "wholesale_rate" => "Wholesale rate",
        "retail_rate" => "Retail rate",
        "minimum_sale_rate" => "Minimum sale rate",
        "minimum_stock" => "Minimum stock",
        "opening_stock" => "Opening stock",
    ];
    $numeric = [];

    foreach ($numericFields as $name => $label) {
        $raw = $values[$name] === "" ? "0" : $values[$name];
        if (!is_numeric($raw) || (float) $raw < 0) {
            $error = $label . " must be a valid non-negative number.";
            break;
        }
        $numeric[$name] = (float) $raw;
    }

    if ($error === "" && $numeric["retail_rate"] <= 0) {
        $error = "Retail rate must be greater than zero so sales can use the saved product price.";
    }

    if ($error === "" && $numeric["minimum_sale_rate"] > 0) {
        $configuredSaleRate = $numeric["retail_rate"] > 0 ? $numeric["retail_rate"] : $numeric["sale_rate"];
        if ($configuredSaleRate < $numeric["minimum_sale_rate"]) {
            $error = "Retail rate cannot be lower than the minimum sale rate.";
        }
    }

    $attributeIds = [];
    foreach (["color_id" => "colors", "gsm_id" => "gsm", "size_id" => "sizes", "unit_id" => "units"] as $name => $source) {
        $raw = $values[$name];
        if ($raw === "") {
            if ($name === "unit_id") {
                $error = "Choose a unit.";
                break;
            }
            $attributeIds[$name] = null;
            continue;
        }
        $id = filter_var($raw, FILTER_VALIDATE_INT);
        $attribute = null;
        foreach ($attributes[$source] as $option) {
            if ((int) $option["id"] === $id) {
                $attribute = $option;
                break;
            }
        }
        if (!$attribute) {
            $error = "Choose a valid " . rtrim($source, "s") . ".";
            break;
        }
        $attributeIds[$name] = $id;
    }

    $sku = $values["sku"] !== "" ? $values["sku"] : null;
    if ($error === "" && $sku !== null) {
        $skuQuery = $pdo->prepare("SELECT id FROM product_variants WHERE sku = ? LIMIT 1");
        $skuQuery->execute([$sku]);
        if ($skuQuery->fetchColumn()) {
            $error = "This SKU already exists.";
        }
    }

    if ($error === "") {
        try {
            $pdo->beginTransaction();
            $insert = $pdo->prepare("
                INSERT INTO product_variants (
                    product_id, color_id, gsm_id, size_id, unit_id, sku,
                    purchase_rate, sale_rate, wholesale_rate, retail_rate,
                    minimum_sale_rate, minimum_stock, opening_stock, current_stock, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $openingStock = $numeric["opening_stock"];
            $insert->execute([
                $productId,
                $attributeIds["color_id"],
                $attributeIds["gsm_id"],
                $attributeIds["size_id"],
                $attributeIds["unit_id"],
                $sku,
                $numeric["purchase_rate"],
                $numeric["sale_rate"] > 0 ? $numeric["sale_rate"] : $numeric["retail_rate"],
                $numeric["wholesale_rate"],
                $numeric["retail_rate"],
                $numeric["minimum_sale_rate"],
                $numeric["minimum_stock"],
                $openingStock,
                $openingStock,
            ]);
            $variantId = (int) $pdo->lastInsertId();

            if ($openingStock > 0) {
                $movement = $pdo->prepare("
                    INSERT INTO stock_movements (
                        product_variant_id, movement_type, quantity_in, quantity_out,
                        balance_stock, rate, created_by, notes
                    ) VALUES (?, 'opening', ?, 0, ?, ?, ?, 'Opening stock entered while creating the variant')
                ");
                $movement->execute([
                    $variantId,
                    $openingStock,
                    $openingStock,
                    $numeric["purchase_rate"],
                    (int) ($_SESSION["user_id"] ?? 0),
                ]);
            }

            $pdo->commit();
            header("Location: variants.php?product_id=" . $productId);
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && $exception->getCode() !== "23000") {
                error_log($exception->getMessage());
            }
            $error = $exception instanceof PDOException && $exception->getCode() === "23000"
                ? "A variant with this SKU already exists."
                : "Unable to save this product variant. Please check the values and try again.";
        }
    }
}

require_once "../../includes/header.php";
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="variants.php?product_id=<?= (int) $productId ?>" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Back to variants
        </a>
        <h1 class="h4 mt-2 mb-1">Add product variant</h1>
        <p class="text-secondary mb-0">Set a saved selling price so it can be filled automatically when this item is sold.</p>
    </div>
</div>

<?php if ($error !== ""): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-4">
        <div><small class="text-secondary d-block">Product</small><strong><?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?></strong></div>
        <div><small class="text-secondary d-block">Product code</small><strong><?= htmlspecialchars($product["product_code"] ?? "-", ENT_QUOTES, "UTF-8") ?></strong></div>
        <div><small class="text-secondary d-block">Category</small><strong><?= htmlspecialchars($product["category_name"] ?? "-", ENT_QUOTES, "UTF-8") ?></strong></div>
    </div>
</div>

<form method="post">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Variant details</h2></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="color_id">Color</label>
                    <select class="form-select" id="color_id" name="color_id">
                        <option value="">No color</option>
                        <?php foreach ($attributes["colors"] as $option): ?>
                            <option value="<?= (int) $option["id"] ?>" <?= $values["color_id"] == $option["id"] ? "selected" : "" ?>><?= htmlspecialchars($option["name"], ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="gsm_id">GSM / Gram</label>
                    <select class="form-select" id="gsm_id" name="gsm_id">
                        <option value="">Not specified</option>
                        <?php foreach ($attributes["gsm"] as $option): ?>
                            <option value="<?= (int) $option["id"] ?>" <?= $values["gsm_id"] == $option["id"] ? "selected" : "" ?>><?= htmlspecialchars($option["value"] . " " . $option["unit"], ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="size_id">Size</label>
                    <select class="form-select" id="size_id" name="size_id">
                        <option value="">Not specified</option>
                        <?php foreach ($attributes["sizes"] as $option): ?>
                            <option value="<?= (int) $option["id"] ?>" <?= $values["size_id"] == $option["id"] ? "selected" : "" ?>><?= htmlspecialchars($option["name"], ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="unit_id">Stock unit <span class="text-danger">*</span></label>
                    <select class="form-select" id="unit_id" name="unit_id" required>
                        <option value="">Choose unit</option>
                        <?php foreach ($attributes["units"] as $option): ?>
                            <option value="<?= (int) $option["id"] ?>" <?= $values["unit_id"] == $option["id"] ? "selected" : "" ?>><?= htmlspecialchars($option["name"] . " (" . $option["short_name"] . ")", ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="sku">SKU</label>
                    <input class="form-control" id="sku" name="sku" value="<?= htmlspecialchars($values["sku"], ENT_QUOTES, "UTF-8") ?>" placeholder="Unique stock keeping unit">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Pricing per unit</h2></div>
        <div class="card-body">
            <div class="alert alert-info py-2 small">Sales will automatically use the saved retail or wholesale rate. You can choose the rate type when creating a sale.</div>
            <div class="row g-3">
                <?php
                $rateFields = [
                    ["purchase_rate", "Purchase / cost rate", false],
                    ["sale_rate", "Default sale rate", false],
                    ["wholesale_rate", "Wholesale rate", false],
                    ["retail_rate", "Retail rate", true],
                    ["minimum_sale_rate", "Minimum allowed sale rate", false],
                ];
                foreach ($rateFields as [$name, $label, $required]):
                ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label" for="<?= $name ?>"><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?><?= $required ? ' <span class="text-danger">*</span>' : "" ?></label>
                        <div class="input-group"><span class="input-group-text">Rs.</span><input class="form-control" type="number" min="0" step="0.01" id="<?= $name ?>" name="<?= $name ?>" value="<?= htmlspecialchars($values[$name], ENT_QUOTES, "UTF-8") ?>" <?= $required ? "required" : "" ?>></div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="form-label" for="minimum_stock">Low-stock alert quantity</label>
                    <input class="form-control" type="number" min="0" step="0.001" id="minimum_stock" name="minimum_stock" value="<?= htmlspecialchars($values["minimum_stock"], ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="form-label" for="opening_stock">Opening stock</label>
                    <input class="form-control" type="number" min="0" step="0.001" id="opening_stock" name="opening_stock" value="<?= htmlspecialchars($values["opening_stock"], ENT_QUOTES, "UTF-8") ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="variants.php?product_id=<?= (int) $productId ?>" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save variant</button>
    </div>
</form>

<?php require_once "../../includes/footer.php"; ?>
