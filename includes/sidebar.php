<?php
$navGroups = [
    "Inventory" => [
        ["Products", "/paper-management/modules/products/index.php", "bi-box-seam"],
        ["Categories", "/paper-management/modules/categories/index.php", "bi-grid"],
        ["Brands", "/paper-management/modules/brands/index.php", "bi-tags"],
        ["Colors", "/paper-management/modules/colors/index.php", "bi-palette"],
        ["GSM / Gram", "/paper-management/modules/gsm/index.php", "bi-layers"],
        ["Sizes", "/paper-management/modules/sizes/index.php", "bi-aspect-ratio"],
        ["Units", "/paper-management/modules/units/index.php", "bi-rulers"],
        ["Opening stock", "/paper-management/modules/operations.php?module=opening-stock", "bi-box-arrow-in-down"],
        ["Stock adjustment", "/paper-management/modules/operations.php?module=stock-adjustment", "bi-sliders"],
        ["Stock movement", "/paper-management/modules/operations.php?module=stock", "bi-arrow-left-right"],
    ],
    "Purchases" => [
        ["New purchase", "/paper-management/modules/operations.php?module=purchase", "bi-bag-plus"],
        ["Purchase list", "/paper-management/modules/operations.php?module=purchases", "bi-receipt"],
        ["Purchase returns", "/paper-management/modules/operations.php?module=purchase-returns", "bi-arrow-return-left"],
    ],
    "Sales" => [
        ["New sale", "/paper-management/modules/operations.php?module=sale", "bi-cart-plus"],
        ["Sales list", "/paper-management/modules/operations.php?module=sales", "bi-receipt-cutoff"],
        ["Sales returns", "/paper-management/modules/operations.php?module=sales-returns", "bi-arrow-return-right"],
    ],
    "Parties" => [
        ["Customers", "/paper-management/modules/operations.php?module=customers", "bi-person"],
        ["Vendors", "/paper-management/modules/operations.php?module=vendors", "bi-truck"],
        ["Customer payments", "/paper-management/modules/operations.php?module=customer-payments", "bi-cash-coin"],
        ["Vendor payments", "/paper-management/modules/operations.php?module=vendor-payments", "bi-wallet2"],
    ],
    "Workers" => [
        ["Workers", "/paper-management/modules/operations.php?module=workers", "bi-people"],
        ["Commission", "/paper-management/modules/operations.php?module=commissions", "bi-percent"],
        ["Daily expenses", "/paper-management/modules/operations.php?module=worker-expenses", "bi-receipt"],
        ["Worker payments", "/paper-management/modules/operations.php?module=worker-payments", "bi-currency-exchange"],
    ],
    "Accounts" => [
        ["Chart of accounts", "/paper-management/modules/operations.php?module=accounts", "bi-diagram-3"],
        ["Cash", "/paper-management/modules/operations.php?module=cash", "bi-cash-stack"],
        ["Bank", "/paper-management/modules/operations.php?module=bank", "bi-bank"],
        ["Expenses", "/paper-management/modules/operations.php?module=expenses", "bi-credit-card"],
        ["Customer ledger", "/paper-management/modules/operations.php?module=customer-ledger", "bi-journal-text"],
        ["Vendor ledger", "/paper-management/modules/operations.php?module=vendor-ledger", "bi-journal-bookmark"],
        ["Worker ledger", "/paper-management/modules/operations.php?module=worker-ledger", "bi-journal-check"],
        ["Transactions", "/paper-management/modules/operations.php?module=transactions", "bi-arrow-left-right"],
    ],
    "Reports" => [
        ["Sales reports", "/paper-management/modules/operations.php?module=report-sales", "bi-graph-up"],
        ["Purchase reports", "/paper-management/modules/operations.php?module=report-purchases", "bi-graph-down"],
        ["Stock reports", "/paper-management/modules/operations.php?module=report-stock", "bi-boxes"],
        ["Expense reports", "/paper-management/modules/operations.php?module=report-expenses", "bi-receipt"],
        ["Profit & loss", "/paper-management/modules/operations.php?module=report-profit", "bi-pie-chart"],
        ["Customer reports", "/paper-management/modules/operations.php?module=report-customers", "bi-person-lines-fill"],
        ["Vendor reports", "/paper-management/modules/operations.php?module=report-vendors", "bi-truck"],
        ["Worker reports", "/paper-management/modules/operations.php?module=report-workers", "bi-person-workspace"],
        ["Financial reports", "/paper-management/modules/operations.php?module=report-financial", "bi-bar-chart-line"],
    ],
    "Users" => [
        ["Users", "/paper-management/modules/operations.php?module=users", "bi-person-gear"],
        ["Roles", "/paper-management/modules/operations.php?module=roles", "bi-shield"],
        ["Permissions", "/paper-management/modules/operations.php?module=permissions", "bi-key"],
    ],
    "Settings" => [
        ["Company", "/paper-management/modules/operations.php?module=company-settings", "bi-building"],
        ["Invoice", "/paper-management/modules/operations.php?module=invoice-settings", "bi-file-earmark-text"],
        ["Payment methods", "/paper-management/modules/operations.php?module=payment-methods", "bi-credit-card-2-front"],
        ["System settings", "/paper-management/modules/operations.php?module=settings", "bi-gear"],
        ["Backup", "/paper-management/modules/operations.php?module=backup", "bi-database-down"],
    ],
];
$currentUrl = $_SERVER["REQUEST_URI"] ?? "";
$currentPath = parse_url($currentUrl, PHP_URL_PATH);
$dashboardActive = $currentPath === "/paper-management/dashboard.php" || $currentPath === "/paper-management/";
?>
<div class="sidebar-backdrop"></div>
<aside class="sidebar">
    <div class="brand-block">
        <a class="brand-link" href="/paper-management/dashboard.php">
            <span class="brand-mark"><i class="bi bi-stack"></i></span>
            <span><strong>PaperFlow</strong><small>Management system</small></span>
        </a>
        <button class="sidebar-close d-lg-none" type="button" aria-label="Close navigation"><i class="bi bi-x-lg"></i></button>
    </div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        <a class="nav-item-link <?= $dashboardActive ? "active" : "" ?>" href="/paper-management/dashboard.php">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <?php foreach ($navGroups as $group => $links): ?>
            <section class="nav-section">
                <div class="nav-section-title"><?= htmlspecialchars($group, ENT_QUOTES, "UTF-8") ?></div>
                <?php foreach ($links as [$label, $href, $icon]): ?>
                    <?php
                    $targetPath = parse_url($href, PHP_URL_PATH);
                    $targetQuery = [];
                    parse_str((string) parse_url($href, PHP_URL_QUERY), $targetQuery);
                    $isActive = $targetPath === $currentPath;
                    if ($isActive && isset($targetQuery["module"])) {
                        $isActive = ($targetQuery["module"] === ($_GET["module"] ?? ""));
                    }
                    ?>
                    <a class="nav-item-link <?= $isActive ? "active" : "" ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, "UTF-8") ?>">
                        <i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, "UTF-8") ?>"></i><span><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></span>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION["user_name"] ?? "A", 0, 1)) ?></div>
        <div class="sidebar-user"><strong><?= htmlspecialchars($_SESSION["user_name"] ?? "Admin", ENT_QUOTES, "UTF-8") ?></strong><small>Signed in</small></div>
        <a class="sidebar-logout" href="/paper-management/logout.php" aria-label="Log out" title="Log out"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</aside>
