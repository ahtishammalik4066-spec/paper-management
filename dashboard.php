<?php
require_once "includes/auth.php";
require_once "config/database.php";

$pageTitle = "Dashboard";
$today = date("Y-m-d");
$monthStart = date("Y-m-01");

$counts = [
    "products" => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 1")->fetchColumn(),
    "customers" => (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE status = 1")->fetchColumn(),
    "vendors" => (int) $pdo->query("SELECT COUNT(*) FROM vendors WHERE status = 1")->fetchColumn(),
    "workers" => (int) $pdo->query("SELECT COUNT(*) FROM workers WHERE status = 1")->fetchColumn(),
    "stock" => (float) $pdo->query("SELECT COALESCE(SUM(current_stock), 0) FROM product_variants WHERE status = 1")->fetchColumn(),
    "low_stock" => (int) $pdo->query("
        SELECT COUNT(*)
        FROM product_variants
        WHERE status = 1 AND minimum_stock > 0 AND current_stock <= minimum_stock
    ")->fetchColumn(),
];

$salesTodayQuery = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE sale_date = ?");
$salesTodayQuery->execute([$today]);
$todaySales = (float) $salesTodayQuery->fetchColumn();

$purchaseTodayQuery = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM purchases WHERE purchase_date = ?");
$purchaseTodayQuery->execute([$today]);
$todayPurchases = (float) $purchaseTodayQuery->fetchColumn();

$salesMonthQuery = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE sale_date >= ? AND sale_date <= ?");
$salesMonthQuery->execute([$monthStart, $today]);
$monthSales = (float) $salesMonthQuery->fetchColumn();

$purchaseMonthQuery = $pdo->prepare("SELECT COALESCE(SUM(grand_total), 0) FROM purchases WHERE purchase_date >= ? AND purchase_date <= ?");
$purchaseMonthQuery->execute([$monthStart, $today]);
$monthPurchases = (float) $purchaseMonthQuery->fetchColumn();

$monthDifference = $monthSales - $monthPurchases;
$monthComparisonLabel = $monthDifference < 0
    ? "Loss this month"
    : ($monthDifference > 0 ? "Sales over purchases" : "Break-even");
$monthComparisonClass = $monthDifference < 0
    ? "monthly-variance--loss"
    : ($monthDifference > 0 ? "monthly-variance--surplus" : "monthly-variance--even");

$chartLabels = [];
$chartSales = [];
$chartPurchases = [];
$chartLosses = [];
$salesByDate = [];
$purchasesByDate = [];
$trendStart = date("Y-m-d", strtotime("-13 days"));

$trendQuery = $pdo->prepare("
    SELECT sale_date AS day, SUM(grand_total) AS amount
    FROM sales
    WHERE sale_date >= ? AND sale_date <= ?
    GROUP BY sale_date
");
$trendQuery->execute([$trendStart, $today]);
foreach ($trendQuery->fetchAll() as $row) {
    $salesByDate[$row["day"]] = (float) $row["amount"];
}

$trendQuery = $pdo->prepare("
    SELECT purchase_date AS day, SUM(grand_total) AS amount
    FROM purchases
    WHERE purchase_date >= ? AND purchase_date <= ?
    GROUP BY purchase_date
");
$trendQuery->execute([$trendStart, $today]);
foreach ($trendQuery->fetchAll() as $row) {
    $purchasesByDate[$row["day"]] = (float) $row["amount"];
}

for ($offset = 13; $offset >= 0; $offset--) {
    $day = date("Y-m-d", strtotime("-" . $offset . " days"));
    $chartLabels[] = date("M j", strtotime($day));
    $daySales = $salesByDate[$day] ?? 0;
    $dayPurchases = $purchasesByDate[$day] ?? 0;
    $chartSales[] = $daySales;
    $chartPurchases[] = $dayPurchases;
    $chartLosses[] = $dayPurchases > $daySales ? $dayPurchases - $daySales : null;
}

$lowStock = $pdo->query("
    SELECT p.name AS product_name, pv.sku, pv.current_stock, pv.minimum_stock, u.short_name
    FROM product_variants pv
    INNER JOIN products p ON p.id = pv.product_id
    LEFT JOIN units u ON u.id = pv.unit_id
    WHERE pv.status = 1 AND pv.minimum_stock > 0 AND pv.current_stock <= pv.minimum_stock
    ORDER BY pv.current_stock ASC
    LIMIT 6
")->fetchAll();

$recentSales = $pdo->query("
    SELECT s.invoice_no, s.sale_date, s.grand_total, c.name AS customer_name, w.name AS worker_name
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    LEFT JOIN workers w ON w.id = s.worker_id
    ORDER BY s.id DESC
    LIMIT 6
")->fetchAll();

$workerTodayQuery = $pdo->prepare("
    SELECT w.id, w.name,
           COALESCE(s.sales_count, 0) AS sales_count,
           COALESCE(s.sales_total, 0) AS sales_total,
           COALESCE(c.commission_total, 0) AS commission_total
    FROM workers w
    LEFT JOIN (
        SELECT worker_id, COUNT(*) AS sales_count, SUM(grand_total) AS sales_total
        FROM sales
        WHERE sale_date = ? AND worker_id IS NOT NULL
        GROUP BY worker_id
    ) s ON s.worker_id = w.id
    LEFT JOIN (
        SELECT s.worker_id, SUM(wc.commission_amount) AS commission_total
        FROM sales s
        INNER JOIN worker_commissions wc ON wc.sale_id = s.id AND wc.worker_id = s.worker_id
        WHERE s.sale_date = ?
        GROUP BY s.worker_id
    ) c ON c.worker_id = w.id
    WHERE w.status = 1
    GROUP BY w.id, w.name
    ORDER BY s.sales_total DESC, w.name
");
$workerTodayQuery->execute([$today, $today]);
$workerToday = $workerTodayQuery->fetchAll();
$workerChartNames = array_column($workerToday, "name");
$workerChartSales = array_map("floatval", array_column($workerToday, "sales_total"));
$workerChartHeight = max(180, count($workerToday) * 42);
?>
<?php require "includes/header.php"; ?>

<section class="dashboard-welcome mb-4">
    <div>
        <span class="eyebrow">BUSINESS OVERVIEW</span>
        <h1 class="h3 mb-1">Good <?= date("H") < 12 ? "morning" : (date("H") < 18 ? "afternoon" : "evening") ?>, <?= htmlspecialchars($_SESSION["user_name"] ?? "Admin", ENT_QUOTES, "UTF-8") ?></h1>
        <p class="text-secondary mb-0">Here is what's happening with your business today.</p>
    </div>
    <div class="welcome-date"><i class="bi bi-calendar3 me-2"></i><?= date("l, d M Y") ?></div>
</section>

<section class="row g-3 mb-4 dashboard-metrics">
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--violet">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-currency-exchange"></i></span><span class="metric-period">TODAY</span></div>
            <div class="metric-label">Today's sales</div>
            <div class="metric-value">Rs. <?= number_format($todaySales, 2) ?></div>
            <div class="metric-note">This month <strong>Rs. <?= number_format($monthSales, 2) ?></strong></div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--blue">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-bag-check"></i></span><span class="metric-period">TODAY</span></div>
            <div class="metric-label">Today's purchases</div>
            <div class="metric-value">Rs. <?= number_format($todayPurchases, 2) ?></div>
            <div class="metric-note">This month <strong>Rs. <?= number_format($monthPurchases, 2) ?></strong></div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--green">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-box-seam"></i></span><span class="metric-period">ACTIVE</span></div>
            <div class="metric-label">Products</div>
            <div class="metric-value"><?= number_format($counts["products"]) ?></div>
            <div class="metric-note">Active products in catalog</div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--teal">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-boxes"></i></span><span class="metric-period">ON HAND</span></div>
            <div class="metric-label">Current stock</div>
            <div class="metric-value"><?= number_format($counts["stock"], 3) ?></div>
            <div class="metric-note">Total units across variants</div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--orange">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-person"></i></span><span class="metric-period">ACTIVE</span></div>
            <div class="metric-label">Customers</div>
            <div class="metric-value"><?= number_format($counts["customers"]) ?></div>
            <div class="metric-note">Registered customers</div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--cyan">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-truck"></i></span><span class="metric-period">ACTIVE</span></div>
            <div class="metric-label">Vendors</div>
            <div class="metric-value"><?= number_format($counts["vendors"]) ?></div>
            <div class="metric-note">Registered suppliers</div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--rose">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-person-workspace"></i></span><span class="metric-period">ACTIVE</span></div>
            <div class="metric-label">Workers</div>
            <div class="metric-value"><?= number_format($counts["workers"]) ?></div>
            <div class="metric-note">Active workers</div>
        </article>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="metric-card metric-card--amber">
            <div class="metric-top"><span class="metric-icon"><i class="bi bi-exclamation-triangle"></i></span><span class="metric-period">REORDER</span></div>
            <div class="metric-label">Low stock items</div>
            <div class="metric-value"><?= number_format($counts["low_stock"]) ?></div>
            <div class="metric-note">At or below minimum stock</div>
        </article>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div><h2 class="panel-title">Daily sales & purchases</h2><p class="panel-subtitle">Day-by-day activity · last 14 days</p></div>
                <span class="panel-icon"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <div class="chart-wrap"><canvas id="tradeChart" aria-label="Sales and purchases chart"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="panel-card monthly-chart-card">
            <div class="panel-heading mb-2">
                <div><h2 class="panel-title">Monthly comparison</h2><p class="panel-subtitle">Sales vs purchases · this month</p></div>
                <span class="panel-icon panel-icon--teal"><i class="bi bi-pie-chart"></i></span>
            </div>
            <div class="monthly-chart-wrap"><canvas id="monthlyTradeChart" aria-label="This month's sales compared with purchases"></canvas></div>
            <div class="monthly-chart-legend">
                <div class="monthly-legend-item">
                    <span class="legend-label"><i class="legend-dot legend-dot--sales"></i>Sales</span>
                    <strong>Rs. <?= number_format($monthSales, 2) ?></strong>
                </div>
                <div class="monthly-legend-item">
                    <span class="legend-label"><i class="legend-dot legend-dot--purchases"></i>Purchases</span>
                    <strong>Rs. <?= number_format($monthPurchases, 2) ?></strong>
                </div>
                <div class="monthly-legend-item monthly-variance <?= $monthComparisonClass ?>">
                    <span><?= htmlspecialchars($monthComparisonLabel, ENT_QUOTES, "UTF-8") ?></span>
                    <strong>Rs. <?= number_format(abs($monthDifference), 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="quick-actions-section mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div><h2 class="panel-title mb-1">Quick actions</h2><p class="panel-subtitle">Common tasks at a glance</p></div>
    </div>
    <div class="row g-2">
        <div class="col-6 col-lg-3"><a class="quick-action-tile" href="/paper-management/modules/products/add.php"><span class="quick-icon"><i class="bi bi-plus-lg"></i></span><span><strong>Add product</strong><small>Create a product and variants</small></span><i class="bi bi-chevron-right ms-auto"></i></a></div>
        <div class="col-6 col-lg-3"><a class="quick-action-tile" href="/paper-management/modules/operations.php?module=purchase"><span class="quick-icon"><i class="bi bi-bag-plus"></i></span><span><strong>Record purchase</strong><small>Receive stock from a vendor</small></span><i class="bi bi-chevron-right ms-auto"></i></a></div>
        <div class="col-6 col-lg-3"><a class="quick-action-tile" href="/paper-management/modules/operations.php?module=sale"><span class="quick-icon"><i class="bi bi-cart-plus"></i></span><span><strong>Record sale</strong><small>Sell products and update stock</small></span><i class="bi bi-chevron-right ms-auto"></i></a></div>
        <div class="col-6 col-lg-3"><a class="quick-action-tile" href="/paper-management/modules/operations.php?module=stock-adjustment"><span class="quick-icon"><i class="bi bi-sliders"></i></span><span><strong>Adjust stock</strong><small>Record a stock correction</small></span><i class="bi bi-chevron-right ms-auto"></i></a></div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div><h2 class="panel-title">Recent sales</h2><p class="panel-subtitle">Latest invoices recorded</p></div>
                <a class="panel-link" href="/paper-management/modules/operations.php?module=sales">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Worker</th><th>Date</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php if (!$recentSales): ?>
                        <tr><td colspan="5" class="empty-state">No sales have been recorded yet.</td></tr>
                    <?php else: foreach ($recentSales as $sale): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($sale["invoice_no"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($sale["customer_name"] ?? "Walk-in customer", ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($sale["worker_name"] ?? "Unassigned", ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= date("d M Y", strtotime($sale["sale_date"])) ?></td>
                            <td class="text-end fw-semibold">Rs. <?= number_format((float) $sale["grand_total"], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="panel-card h-100">
            <div class="panel-heading">
                <div><h2 class="panel-title">Low stock alerts</h2><p class="panel-subtitle">Items at or below minimum stock</p></div>
                <a class="panel-link" href="/paper-management/modules/operations.php?module=stock">Inventory <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="low-stock-list">
                <?php if (!$lowStock): ?>
                    <div class="empty-state">No low-stock items. Your inventory is looking good.</div>
                <?php else: foreach ($lowStock as $item): ?>
                    <div class="low-stock-item">
                        <span class="stock-alert-icon"><i class="bi bi-exclamation"></i></span>
                        <span class="stock-item-name"><strong><?= htmlspecialchars($item["product_name"], ENT_QUOTES, "UTF-8") ?></strong><small><?= htmlspecialchars($item["sku"] ?: "No SKU", ENT_QUOTES, "UTF-8") ?></small></span>
                        <span class="stock-amount"><?= number_format((float) $item["current_stock"], 3) ?><small><?= htmlspecialchars($item["short_name"] ?? "units", ENT_QUOTES, "UTF-8") ?> left</small></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="row g-3 mt-1">
    <div class="col-12">
        <div class="panel-card">
            <div class="panel-heading">
                <div><h2 class="panel-title">Today's worker sales</h2><p class="panel-subtitle">Today's sales by worker · bar length and amount show the total</p></div>
                <a class="panel-link" href="/paper-management/modules/operations.php?module=report-workers">Worker report <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php if ($workerToday): ?>
                <div class="worker-sales-chart-wrap" style="height: <?= $workerChartHeight ?>px">
                    <canvas id="workerSalesChart" aria-label="Today's sales totals by worker"></canvas>
                </div>
            <?php else: ?>
                <div class="chart-empty"><i class="bi bi-bar-chart-line"></i><strong>No active workers</strong><span>Add or activate a worker to see today's sales chart.</span></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table dashboard-table mb-0">
                    <thead><tr><th>Worker</th><th class="text-end">Sales</th><th class="text-end">Daily sales total</th><th class="text-end">Commission earned</th></tr></thead>
                    <tbody>
                    <?php if ($workerToday): foreach ($workerToday as $worker): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($worker["name"], ENT_QUOTES, "UTF-8") ?></td>
                            <td class="text-end"><?= number_format((int) $worker["sales_count"]) ?></td>
                            <td class="text-end fw-semibold">Rs. <?= number_format((float) $worker["sales_total"], 2) ?></td>
                            <td class="text-end fw-semibold">Rs. <?= number_format((float) $worker["commission_total"], 2) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="empty-state">No active workers to show.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("tradeChart");
    if (typeof Chart === "undefined") return;
    const workerSalesCanvas = document.getElementById("workerSalesChart");
    if (workerSalesCanvas) {
        const workerNames = <?= json_encode($workerChartNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const workerSales = <?= json_encode($workerChartSales) ?>;
        new Chart(workerSalesCanvas, {
            type: "bar",
            data: {
                labels: workerNames,
                datasets: [{
                    label: "Today's sales",
                    data: workerSales,
                    backgroundColor: "rgba(102, 88, 211, .82)",
                    borderColor: "#6658d3",
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 26
                }]
            },
            options: {
                indexAxis: "y",
                responsive: true,
                maintainAspectRatio: false,
                layout: {padding: {right: 110}},
                plugins: {
                    legend: {display: false},
                    tooltip: {callbacks: {label: context => `Sales: Rs. ${Number(context.raw).toLocaleString(undefined, {minimumFractionDigits: 2})}`}}
                },
                scales: {
                    x: {beginAtZero: true, grace: "15%", grid: {color: "#f0f1f5"}, ticks: {callback: value => "Rs. " + Number(value).toLocaleString()}},
                    y: {grid: {display: false}}
                }
            },
            plugins: [{
                id: "workerSalesValues",
                afterDatasetsDraw(chart) {
                    const {ctx} = chart;
                    const meta = chart.getDatasetMeta(0);
                    ctx.save();
                    ctx.fillStyle = "#34364d";
                    ctx.font = "600 11px Segoe UI, Arial, sans-serif";
                    ctx.textBaseline = "middle";
                    meta.data.forEach((bar, index) => {
                        const amount = `Rs. ${Number(workerSales[index]).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                        ctx.fillText(amount, bar.x + 7, bar.y);
                    });
                    ctx.restore();
                }
            }]
        });
    }

    const sales = <?= json_encode($chartSales) ?>;
    const purchases = <?= json_encode($chartPurchases) ?>;
    const losses = <?= json_encode($chartLosses) ?>;
    const tradeChartCanvas = canvas;
    if (tradeChartCanvas && ![...sales, ...purchases].some(value => Number(value) > 0)) {
        tradeChartCanvas.parentElement.innerHTML = '<div class="chart-empty"><i class="bi bi-bar-chart-line"></i><strong>No transaction data yet</strong><span>Record a sale or purchase to see your daily activity here.</span></div>';
    } else if (tradeChartCanvas) {
        new Chart(tradeChartCanvas, {
            type: "line",
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {label: "Sales", data: sales, borderColor: "#6658d3", backgroundColor: "rgba(102,88,211,.12)", fill: true, tension: .38, pointRadius: context => context.dataIndex === context.dataset.data.length - 1 ? 4 : 2, pointHoverRadius: 6, pointBackgroundColor: "#6658d3", borderWidth: 2.5},
                    {label: "Purchases", data: purchases, borderColor: "#25a77a", backgroundColor: "rgba(37,167,122,.07)", fill: true, tension: .38, pointRadius: context => context.dataIndex === context.dataset.data.length - 1 ? 4 : 2, pointHoverRadius: 6, pointBackgroundColor: "#25a77a", borderWidth: 2.5},
                    {label: "Daily loss", data: losses, borderColor: "#dc3545", backgroundColor: "rgba(220,53,69,.12)", pointBackgroundColor: "#dc3545", pointBorderColor: "#fff", pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6, borderWidth: 2.5, borderDash: [6, 4], fill: false, tension: 0, spanGaps: false}
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {mode: "index", intersect: false},
                plugins: {legend: {position: "bottom", labels: {usePointStyle: true, boxWidth: 7, padding: 18}}, tooltip: {callbacks: {label: context => `${context.dataset.label}: Rs. ${Number(context.raw).toLocaleString(undefined, {minimumFractionDigits: 2})}`}}},
                scales: {y: {beginAtZero: true, grid: {color: "#f0f1f5"}, ticks: {callback: value => "Rs. " + Number(value).toLocaleString()}}, x: {grid: {display: false}}}
            }
        });
    }

    const monthlyCanvas = document.getElementById("monthlyTradeChart");
    if (!monthlyCanvas) return;
    const monthSales = <?= json_encode($monthSales) ?>;
    const monthPurchases = <?= json_encode($monthPurchases) ?>;
    const hasMonthlyActivity = monthSales > 0 || monthPurchases > 0;
    new Chart(monthlyCanvas, {
        type: "doughnut",
        data: {
            labels: hasMonthlyActivity ? ["Sales", "Purchases"] : ["No activity"],
            datasets: [{
                data: hasMonthlyActivity ? [monthSales, monthPurchases] : [1],
                backgroundColor: hasMonthlyActivity ? ["#6658d3", "#25a77a"] : ["#e9ebf2"],
                borderColor: "#fff",
                borderWidth: 4,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "74%",
            plugins: {
                legend: {display: false},
                tooltip: {enabled: hasMonthlyActivity, callbacks: {label: context => `${context.label}: Rs. ${Number(context.raw).toLocaleString(undefined, {minimumFractionDigits: 2})}`}}
            }
        },
        plugins: [{
            id: "monthlyCenterLabel",
            afterDraw(chart) {
                const {ctx, chartArea} = chart;
                if (!chartArea) return;
                const x = (chartArea.left + chartArea.right) / 2;
                const y = (chartArea.top + chartArea.bottom) / 2;
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#85879a";
                ctx.font = "600 10px Segoe UI, Arial, sans-serif";
                ctx.fillText("TOTAL", x, y - 10);
                ctx.fillStyle = "#20213a";
                ctx.font = "700 14px Segoe UI, Arial, sans-serif";
                ctx.fillText(`Rs. ${(monthSales + monthPurchases).toLocaleString(undefined, {maximumFractionDigits: 0})}`, x, y + 10);
                ctx.restore();
            }
        }]
    });
});
</script>
<?php require "includes/footer.php"; ?>
