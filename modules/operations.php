<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$module = $_GET["module"] ?? "";
$today = date("Y-m-d");
$userId = (int) ($_SESSION["user_id"] ?? 0);
$formError = "";
$notice = "";

$optionQueries = [
    "customers" => "SELECT id, name AS label FROM customers WHERE status = 1 ORDER BY name",
    "vendors" => "SELECT id, name AS label FROM vendors WHERE status = 1 ORDER BY name",
    "workers" => "SELECT id, name AS label FROM workers WHERE status = 1 ORDER BY name",
    "variants" => "SELECT pv.id, CONCAT(p.name, ' · ', COALESCE(pv.sku, CONCAT('Variant ', pv.id)), ' (stock: ', pv.current_stock, ')') AS label FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.status = 1 ORDER BY p.name",
    "payment_methods" => "SELECT id, name AS label FROM payment_methods WHERE status = 1 ORDER BY name",
    "expense_categories" => "SELECT id, name AS label FROM expense_categories WHERE status = 1 ORDER BY name",
    "accounts" => "SELECT id, CONCAT(COALESCE(account_code, ''), ' ', account_name) AS label FROM chart_of_accounts WHERE status = 1 ORDER BY account_name",
    "roles" => "SELECT id, name AS label FROM roles WHERE status = 1 ORDER BY name",
];

$modules = [
    "customers" => ["title" => "Customers", "description" => "Manage customer contacts and balances.", "query" => "SELECT id, name, phone, email, opening_balance, credit_limit, status FROM customers ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Customer", "phone" => "Phone", "email" => "Email", "opening_balance" => "Opening balance", "credit_limit" => "Credit limit", "status" => "Status"], "fields" => [
        ["name", "Name", "text", true], ["phone", "Phone", "text"], ["whatsapp", "WhatsApp", "text"], ["email", "Email", "email"], ["address", "Address", "textarea"], ["opening_balance", "Opening balance", "number"], ["credit_limit", "Credit limit", "number"], ["notes", "Notes", "textarea"], ["status", "Status", "status"],
    ], "table" => "customers"],
    "vendors" => ["title" => "Vendors", "description" => "Manage suppliers and vendor accounts.", "query" => "SELECT id, name, company, phone, email, opening_balance, status FROM vendors ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Vendor", "company" => "Company", "phone" => "Phone", "email" => "Email", "opening_balance" => "Opening balance", "status" => "Status"], "fields" => [
        ["name", "Name", "text", true], ["company", "Company", "text"], ["phone", "Phone", "text"], ["whatsapp", "WhatsApp", "text"], ["email", "Email", "email"], ["address", "Address", "textarea"], ["opening_balance", "Opening balance", "number"], ["notes", "Notes", "textarea"], ["status", "Status", "status"],
    ], "table" => "vendors"],
    "workers" => ["title" => "Workers", "description" => "Manage sales staff and automatic commission rules.", "query" => "SELECT id, name, phone, commission_type, commission_rate, status FROM workers ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Worker", "phone" => "Phone", "commission_type" => "Commission type", "commission_rate" => "Commission rate", "status" => "Status"], "fields" => [
        ["name", "Name", "text", true], ["phone", "Phone", "text"], ["address", "Address", "textarea"], ["joining_date", "Joining date", "date"], ["commission_type", "Commission type", "select", true, ["percentage", "fixed", "quantity"]], ["commission_rate", "Commission rate", "number", true], ["status", "Status", "status"],
    ], "table" => "workers"],
    "payment-methods" => ["title" => "Payment methods", "description" => "Set the payment methods available at checkout.", "query" => "SELECT id, name, status, created_at FROM payment_methods ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Method", "status" => "Status", "created_at" => "Created"], "fields" => [
        ["name", "Name", "text", true], ["status", "Status", "status"],
    ], "table" => "payment_methods"],
    "expense-categories" => ["title" => "Expense categories", "description" => "Organize business expense entries.", "query" => "SELECT id, name, status FROM expense_categories ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Category", "status" => "Status"], "fields" => [
        ["name", "Name", "text", true], ["status", "Status", "status"],
    ], "table" => "expense_categories"],
    "expenses" => ["title" => "Expenses", "description" => "Record and review business expenses.", "query" => "SELECT e.id, e.expense_date, ec.name AS category, e.amount, pm.name AS payment_method, e.description FROM expenses e LEFT JOIN expense_categories ec ON ec.id = e.expense_category_id LEFT JOIN payment_methods pm ON pm.id = e.payment_method_id ORDER BY e.id DESC", "columns" => ["id" => "#", "expense_date" => "Date", "category" => "Category", "amount" => "Amount", "payment_method" => "Payment method", "description" => "Description"], "fields" => [
        ["expense_category_id", "Category", "option:expense_categories"], ["expense_date", "Date", "date", true], ["amount", "Amount", "number", true], ["payment_method_id", "Payment method", "option:payment_methods"], ["description", "Description", "textarea"],
    ], "table" => "expenses", "append" => ["created_by" => "user"]],
    "customer-payments" => ["title" => "Customer payments", "description" => "Record money received from customers.", "query" => "SELECT cp.id, cp.payment_date, c.name AS party, cp.amount, pm.name AS payment_method, cp.reference_no FROM customer_payments cp JOIN customers c ON c.id = cp.customer_id LEFT JOIN payment_methods pm ON pm.id = cp.payment_method_id ORDER BY cp.id DESC", "columns" => ["id" => "#", "payment_date" => "Date", "party" => "Customer", "amount" => "Amount", "payment_method" => "Method", "reference_no" => "Reference"], "fields" => [
        ["customer_id", "Customer", "option:customers", true], ["payment_date", "Payment date", "date", true], ["amount", "Amount", "number", true], ["payment_method_id", "Payment method", "option:payment_methods"], ["reference_no", "Reference", "text"], ["notes", "Notes", "textarea"],
    ], "table" => "customer_payments", "append" => ["created_by" => "user"]],
    "vendor-payments" => ["title" => "Vendor payments", "description" => "Record payments made to vendors.", "query" => "SELECT vp.id, vp.payment_date, v.name AS party, vp.amount, pm.name AS payment_method, vp.reference_no FROM vendor_payments vp JOIN vendors v ON v.id = vp.vendor_id LEFT JOIN payment_methods pm ON pm.id = vp.payment_method_id ORDER BY vp.id DESC", "columns" => ["id" => "#", "payment_date" => "Date", "party" => "Vendor", "amount" => "Amount", "payment_method" => "Method", "reference_no" => "Reference"], "fields" => [
        ["vendor_id", "Vendor", "option:vendors", true], ["payment_date", "Payment date", "date", true], ["amount", "Amount", "number", true], ["payment_method_id", "Payment method", "option:payment_methods"], ["reference_no", "Reference", "text"], ["notes", "Notes", "textarea"],
    ], "table" => "vendor_payments", "append" => ["created_by" => "user"]],
    "worker-expenses" => ["title" => "Worker expenses & payments", "description" => "Record worker advances, expenses and payments.", "query" => "SELECT we.id, we.expense_date, w.name AS worker, we.expense_type, we.amount, we.status, we.description FROM worker_expenses we JOIN workers w ON w.id = we.worker_id ORDER BY we.id DESC", "columns" => ["id" => "#", "expense_date" => "Date", "worker" => "Worker", "expense_type" => "Type", "amount" => "Amount", "status" => "Status", "description" => "Details"], "fields" => [
        ["worker_id", "Worker", "option:workers", true], ["expense_date", "Date", "date", true], ["expense_type", "Type", "text"], ["amount", "Amount", "number", true], ["description", "Description", "textarea"], ["status", "Payment status", "select", true, ["unpaid", "paid"]],
    ], "table" => "worker_expenses", "append" => ["created_by" => "user"]],
    "users" => ["title" => "Users", "description" => "Manage team access to the system.", "query" => "SELECT u.id, u.name, u.username, u.email, r.name AS role, u.status, u.last_login FROM users u LEFT JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC", "columns" => ["id" => "#", "name" => "Name", "username" => "Username", "email" => "Email", "role" => "Role", "status" => "Status", "last_login" => "Last login"], "fields" => [
        ["name", "Name", "text", true], ["username", "Username", "text", true], ["email", "Email", "email"], ["role_id", "Role", "option:roles"], ["password", "Password", "password", true], ["status", "Status", "status"],
    ], "table" => "users", "password" => "password", "append" => ["role_id" => "optional-role"]],
    "roles" => ["title" => "Roles", "description" => "Define access roles for system users.", "query" => "SELECT id, name, description, status FROM roles ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Role", "description" => "Description", "status" => "Status"], "fields" => [
        ["name", "Name", "text", true], ["description", "Description", "textarea"], ["status", "Status", "status"],
    ], "table" => "roles"],
    "permissions" => ["title" => "Permissions", "description" => "Manage the available access permissions.", "query" => "SELECT id, name, description, created_at FROM permissions ORDER BY id DESC", "columns" => ["id" => "#", "name" => "Permission", "description" => "Description", "created_at" => "Created"], "fields" => [
        ["name", "Name", "text", true], ["description", "Description", "textarea"],
    ], "table" => "permissions"],
    "accounts" => ["title" => "Chart of accounts", "description" => "Set up your financial account structure.", "query" => "SELECT id, account_code, account_name, account_type, status FROM chart_of_accounts ORDER BY id DESC", "columns" => ["id" => "#", "account_code" => "Code", "account_name" => "Account", "account_type" => "Type", "status" => "Status"], "fields" => [
        ["account_code", "Account code", "text"], ["account_name", "Account name", "text", true], ["account_type", "Account type", "select", true, ["asset", "liability", "equity", "income", "expense"]], ["status", "Status", "status"],
    ], "table" => "chart_of_accounts"],
    "transactions" => ["title" => "Account transactions", "description" => "View and record debits and credits.", "query" => "SELECT at.id, at.transaction_date, coa.account_name, at.reference_type, at.description, at.debit, at.credit FROM account_transactions at JOIN chart_of_accounts coa ON coa.id = at.account_id ORDER BY at.id DESC", "columns" => ["id" => "#", "transaction_date" => "Date", "account_name" => "Account", "reference_type" => "Type", "description" => "Description", "debit" => "Debit", "credit" => "Credit"], "fields" => [
        ["account_id", "Account", "option:accounts", true], ["transaction_date", "Date", "date", true], ["reference_type", "Reference type", "text"], ["description", "Description", "textarea"], ["debit", "Debit", "number"], ["credit", "Credit", "number"],
    ], "table" => "account_transactions", "append" => ["created_by" => "user"]],
    "settings" => ["title" => "System settings", "description" => "Manage general system configuration values.", "query" => "SELECT id, setting_key, setting_value, updated_at FROM settings ORDER BY setting_key", "columns" => ["id" => "#", "setting_key" => "Setting", "setting_value" => "Value", "updated_at" => "Updated"], "fields" => [
        ["setting_key", "Setting key", "text", true], ["setting_value", "Setting value", "textarea"],
    ], "table" => "settings"],
];

$listViews = [
    "sales" => ["Sales", "Sales invoices, salesperson, and amounts due.", "SELECT s.id, s.invoice_no, s.sale_date, COALESCE(c.name, 'Walk-in') AS customer, w.name AS worker, s.grand_total, s.paid_amount, s.remaining_amount FROM sales s LEFT JOIN customers c ON c.id = s.customer_id LEFT JOIN workers w ON w.id = s.worker_id ORDER BY s.id DESC", ["id" => "#", "invoice_no" => "Invoice", "sale_date" => "Date", "customer" => "Customer", "worker" => "Worker", "grand_total" => "Total", "paid_amount" => "Paid", "remaining_amount" => "Balance"]],
    "purchases" => ["Purchases", "Purchase invoices and amounts due.", "SELECT p.id, p.invoice_no, p.purchase_date, v.name AS vendor, p.grand_total, p.paid_amount, p.remaining_amount FROM purchases p JOIN vendors v ON v.id = p.vendor_id ORDER BY p.id DESC", ["id" => "#", "invoice_no" => "Invoice", "purchase_date" => "Date", "vendor" => "Vendor", "grand_total" => "Total", "paid_amount" => "Paid", "remaining_amount" => "Balance"]],
    "purchase-returns" => ["Purchase returns", "Stock returned to vendors.", "SELECT pr.id, pr.return_no, pr.return_date, v.name AS vendor, pr.total_amount, pr.reason FROM purchase_returns pr JOIN vendors v ON v.id = pr.vendor_id ORDER BY pr.id DESC", ["id" => "#", "return_no" => "Return #", "return_date" => "Date", "vendor" => "Vendor", "total_amount" => "Amount", "reason" => "Reason"]],
    "sales-returns" => ["Sales returns", "Goods returned by customers.", "SELECT sr.id, sr.return_no, sr.return_date, COALESCE(c.name, 'Walk-in') AS customer, sr.total_amount, sr.reason FROM sales_returns sr LEFT JOIN customers c ON c.id = sr.customer_id ORDER BY sr.id DESC", ["id" => "#", "return_no" => "Return #", "return_date" => "Date", "customer" => "Customer", "total_amount" => "Amount", "reason" => "Reason"]],
    "stock" => ["Stock movement", "Current balances and stock movement history.", "SELECT sm.id, sm.movement_date, p.name AS product, pv.sku, sm.movement_type, sm.quantity_in, sm.quantity_out, sm.balance_stock FROM stock_movements sm JOIN product_variants pv ON pv.id = sm.product_variant_id JOIN products p ON p.id = pv.product_id ORDER BY sm.id DESC LIMIT 250", ["id" => "#", "movement_date" => "Date", "product" => "Product", "sku" => "SKU", "movement_type" => "Movement", "quantity_in" => "In", "quantity_out" => "Out", "balance_stock" => "Balance"]],
    "customer-ledger" => ["Customer ledger", "Sales and payments recorded against customers.", "SELECT c.id, c.name, c.phone, c.opening_balance, COALESCE(s.total_sales, 0) AS sales, COALESCE(cp.total_paid, 0) AS payments, c.opening_balance + COALESCE(s.total_sales, 0) - COALESCE(cp.total_paid, 0) AS balance FROM customers c LEFT JOIN (SELECT customer_id, SUM(grand_total) AS total_sales FROM sales WHERE customer_id IS NOT NULL GROUP BY customer_id) s ON s.customer_id = c.id LEFT JOIN (SELECT customer_id, SUM(amount) AS total_paid FROM customer_payments GROUP BY customer_id) cp ON cp.customer_id = c.id ORDER BY c.name", ["id" => "#", "name" => "Customer", "phone" => "Phone", "opening_balance" => "Opening", "sales" => "Sales", "payments" => "Payments", "balance" => "Balance"]],
    "vendor-ledger" => ["Vendor ledger", "Purchases and payments recorded against vendors.", "SELECT v.id, v.name, v.phone, v.opening_balance, COALESCE(p.total_purchases, 0) AS purchases, COALESCE(vp.total_paid, 0) AS payments, v.opening_balance + COALESCE(p.total_purchases, 0) - COALESCE(vp.total_paid, 0) AS balance FROM vendors v LEFT JOIN (SELECT vendor_id, SUM(grand_total) AS total_purchases FROM purchases GROUP BY vendor_id) p ON p.vendor_id = v.id LEFT JOIN (SELECT vendor_id, SUM(amount) AS total_paid FROM vendor_payments GROUP BY vendor_id) vp ON vp.vendor_id = v.id ORDER BY v.name", ["id" => "#", "name" => "Vendor", "phone" => "Phone", "opening_balance" => "Opening", "purchases" => "Purchases", "payments" => "Payments", "balance" => "Balance"]],
    "worker-ledger" => ["Worker ledger", "Sales and commission balances by worker.", "SELECT w.id, w.name, w.phone, COALESCE(c.sales_count, 0) AS sales_count, COALESCE(c.sales_total, 0) AS sales_total, COALESCE(c.total_commission, 0) AS commission, COALESCE(c.commission_paid, 0) AS commission_paid, COALESCE(c.commission_due, 0) AS commission_due, COALESCE(e.total_expenses, 0) AS expenses FROM workers w LEFT JOIN (SELECT worker_id, COUNT(*) AS sales_count, SUM(sale_amount) AS sales_total, SUM(commission_amount) AS total_commission, SUM(CASE WHEN status='paid' THEN commission_amount ELSE 0 END) AS commission_paid, SUM(CASE WHEN status='unpaid' THEN commission_amount ELSE 0 END) AS commission_due FROM worker_commissions GROUP BY worker_id) c ON c.worker_id = w.id LEFT JOIN (SELECT worker_id, SUM(amount) AS total_expenses FROM worker_expenses GROUP BY worker_id) e ON e.worker_id = w.id ORDER BY w.name", ["id" => "#", "name" => "Worker", "phone" => "Phone", "sales_count" => "Sales", "sales_total" => "Sales total", "commission" => "Earned commission", "commission_paid" => "Paid", "commission_due" => "Due", "expenses" => "Worker payments"]],
    "commissions" => ["Worker commissions", "Automatically calculated commissions for each worker's sales.", "SELECT wc.id, wc.commission_date, w.name AS worker, s.invoice_no, wc.sale_amount, wc.commission_type, wc.commission_rate, wc.commission_amount, wc.status FROM worker_commissions wc JOIN workers w ON w.id = wc.worker_id LEFT JOIN sales s ON s.id = wc.sale_id ORDER BY wc.commission_date DESC, wc.id DESC", ["id" => "#", "commission_date" => "Date", "worker" => "Worker", "invoice_no" => "Sale invoice", "sale_amount" => "Sale total", "commission_type" => "Rule", "commission_rate" => "Rate", "commission_amount" => "Commission", "status" => "Status"]],
    "cash" => ["Cash account", "Cash-related account transactions.", "SELECT at.id, at.transaction_date, coa.account_name, at.description, at.debit, at.credit FROM account_transactions at JOIN chart_of_accounts coa ON coa.id = at.account_id WHERE coa.account_name LIKE '%cash%' ORDER BY at.id DESC", ["id" => "#", "transaction_date" => "Date", "account_name" => "Account", "description" => "Description", "debit" => "Debit", "credit" => "Credit"]],
    "bank" => ["Bank account", "Bank-related account transactions.", "SELECT at.id, at.transaction_date, coa.account_name, at.description, at.debit, at.credit FROM account_transactions at JOIN chart_of_accounts coa ON coa.id = at.account_id WHERE coa.account_name LIKE '%bank%' ORDER BY at.id DESC", ["id" => "#", "transaction_date" => "Date", "account_name" => "Account", "description" => "Description", "debit" => "Debit", "credit" => "Credit"]],
    "report-sales" => ["Sales report", "Sales totals grouped by date.", "SELECT sale_date AS report_date, COUNT(*) AS invoices, SUM(grand_total) AS total, SUM(paid_amount) AS paid, SUM(remaining_amount) AS balance FROM sales GROUP BY sale_date ORDER BY sale_date DESC LIMIT 100", ["report_date" => "Date", "invoices" => "Invoices", "total" => "Sales total", "paid" => "Paid", "balance" => "Balance"]],
    "report-purchases" => ["Purchase report", "Purchase totals grouped by date.", "SELECT purchase_date AS report_date, COUNT(*) AS invoices, SUM(grand_total) AS total, SUM(paid_amount) AS paid, SUM(remaining_amount) AS balance FROM purchases GROUP BY purchase_date ORDER BY purchase_date DESC LIMIT 100", ["report_date" => "Date", "invoices" => "Invoices", "total" => "Purchase total", "paid" => "Paid", "balance" => "Balance"]],
    "report-stock" => ["Stock report", "On-hand inventory value and reorder levels.", "SELECT p.name AS product, pv.sku, pv.current_stock, pv.minimum_stock, pv.purchase_rate, pv.current_stock * pv.purchase_rate AS stock_value FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.status = 1 ORDER BY pv.current_stock ASC", ["product" => "Product", "sku" => "SKU", "current_stock" => "In stock", "minimum_stock" => "Minimum", "purchase_rate" => "Cost/unit", "stock_value" => "Stock value"]],
    "report-expenses" => ["Expense report", "Expenses grouped by category.", "SELECT ec.name AS category, COUNT(e.id) AS entries, COALESCE(SUM(e.amount), 0) AS total FROM expense_categories ec LEFT JOIN expenses e ON e.expense_category_id = ec.id GROUP BY ec.id, ec.name ORDER BY total DESC", ["category" => "Category", "entries" => "Entries", "total" => "Total"]],
    "report-profit" => ["Profit & loss", "Summary of sales, cost of goods and expenses.", "SELECT 'Sales revenue' AS account, COALESCE(SUM(grand_total), 0) AS amount FROM sales UNION ALL SELECT 'Cost of goods sold', COALESCE(SUM(si.quantity * si.cost_rate), 0) FROM sale_items si UNION ALL SELECT 'Operating expenses', COALESCE(SUM(amount), 0) FROM expenses UNION ALL SELECT 'Gross profit (sales less cost)', COALESCE((SELECT SUM(grand_total) FROM sales), 0) - COALESCE((SELECT SUM(quantity * cost_rate) FROM sale_items), 0)", ["account" => "Account", "amount" => "Amount"]],
    "report-customers" => ["Customer report", "Customer count and outstanding balances.", "SELECT c.name, c.phone, COUNT(s.id) AS invoices, COALESCE(SUM(s.grand_total), 0) AS sales, COALESCE(SUM(s.remaining_amount), 0) AS due FROM customers c LEFT JOIN sales s ON s.customer_id = c.id GROUP BY c.id, c.name, c.phone ORDER BY sales DESC", ["name" => "Customer", "phone" => "Phone", "invoices" => "Invoices", "sales" => "Sales", "due" => "Outstanding"]],
    "report-vendors" => ["Vendor report", "Vendor purchase activity and outstanding balances.", "SELECT v.name, v.phone, COUNT(p.id) AS invoices, COALESCE(SUM(p.grand_total), 0) AS purchases, COALESCE(SUM(p.remaining_amount), 0) AS due FROM vendors v LEFT JOIN purchases p ON p.vendor_id = v.id GROUP BY v.id, v.name, v.phone ORDER BY purchases DESC", ["name" => "Vendor", "phone" => "Phone", "invoices" => "Invoices", "purchases" => "Purchases", "due" => "Outstanding"]],
    "report-workers" => ["Worker daily sales & commission", "Daily sales totals and automatically earned commission by salesperson.", "SELECT wc.commission_date AS report_date, w.name AS worker, COUNT(DISTINCT wc.sale_id) AS sales_count, SUM(wc.sale_amount) AS sales_total, SUM(wc.commission_amount) AS commission, SUM(CASE WHEN wc.status = 'paid' THEN wc.commission_amount ELSE 0 END) AS commission_paid, SUM(CASE WHEN wc.status = 'unpaid' THEN wc.commission_amount ELSE 0 END) AS commission_due FROM worker_commissions wc JOIN workers w ON w.id = wc.worker_id GROUP BY wc.commission_date, w.id, w.name ORDER BY wc.commission_date DESC, w.name", ["report_date" => "Date", "worker" => "Worker", "sales_count" => "Sales", "sales_total" => "Daily sales", "commission" => "Commission earned", "commission_paid" => "Paid", "commission_due" => "Outstanding"]],
    "report-financial" => ["Financial report", "Account balances from recorded debit and credit entries.", "SELECT coa.account_code, coa.account_name, coa.account_type, COALESCE(SUM(at.debit), 0) AS debit, COALESCE(SUM(at.credit), 0) AS credit, COALESCE(SUM(at.debit - at.credit), 0) AS balance FROM chart_of_accounts coa LEFT JOIN account_transactions at ON at.account_id = coa.id GROUP BY coa.id, coa.account_code, coa.account_name, coa.account_type ORDER BY coa.account_type, coa.account_name", ["account_code" => "Code", "account_name" => "Account", "account_type" => "Type", "debit" => "Debit", "credit" => "Credit", "balance" => "Balance"]],
];

$workflowTitles = [
    "purchase" => ["New purchase", "Receive stock from a vendor. Stock is updated automatically."],
    "sale" => ["New sale", "Record a sale. Stock availability is checked and reduced automatically."],
    "opening-stock" => ["Opening stock", "Set a starting stock quantity for a product variant."],
    "stock-adjustment" => ["Stock adjustment", "Increase or decrease stock and keep a movement record."],
    "purchase-return" => ["Purchase return", "Return items to a vendor and reduce stock."],
    "sale-return" => ["Sales return", "Receive returned items and add them back into stock."],
];
$returnModule = ["purchase-returns" => "purchase-return", "sales-returns" => "sale-return"];

if (isset($returnModule[$module])) {
    $module = $returnModule[$module];
}

if ($module === "worker-payments") {
    $module = "worker-expenses";
}
if (in_array($module, ["company-settings", "invoice-settings"], true)) {
    $module = "settings";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $module === "backup") {
    $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
    $backup = ["database" => "paper_management", "created_at" => date(DATE_ATOM), "tables" => []];
    foreach ($tables as $table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            continue;
        }
        $backup["tables"][$table] = $pdo->query("SELECT * FROM `$table`")->fetchAll();
    }
    header("Content-Type: application/json; charset=utf-8");
    header("Content-Disposition: attachment; filename=paper-management-backup-" . date("Ymd-His") . ".json");
    echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($workflowTitles[$module])) {
    try {
        $pdo->beginTransaction();
        $variantId = filter_input(INPUT_POST, "product_variant_id", FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_FLOAT);
        $submittedRate = filter_input(INPUT_POST, "rate", FILTER_VALIDATE_FLOAT);
        $rateType = $_POST["rate_type"] ?? "retail";
        $date = $_POST["entry_date"] ?? $today;
        $partyId = filter_input(INPUT_POST, "party_id", FILTER_VALIDATE_INT);
        $workerId = filter_input(INPUT_POST, "worker_id", FILTER_VALIDATE_INT);
        $reason = trim($_POST["reason"] ?? "");

        if (!$variantId || !$quantity || $quantity <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException("Enter a valid product, date and quantity.");
        }

        $variantQuery = $pdo->prepare("SELECT pv.*, p.name AS product_name FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE pv.id = ? FOR UPDATE");
        $variantQuery->execute([$variantId]);
        $variant = $variantQuery->fetch();
        if (!$variant) {
            throw new InvalidArgumentException("Select a valid product variant.");
        }

        if ($module === "sale") {
            if (!in_array($rateType, ["retail", "wholesale"], true)) {
                throw new InvalidArgumentException("Choose a valid sale price type.");
            }
            $rate = $rateType === "wholesale"
                ? ((float) $variant["wholesale_rate"] > 0 ? (float) $variant["wholesale_rate"] : (float) $variant["sale_rate"])
                : ((float) $variant["retail_rate"] > 0 ? (float) $variant["retail_rate"] : (float) $variant["sale_rate"]);
            if ((float) $variant["minimum_sale_rate"] > 0 && $rate < (float) $variant["minimum_sale_rate"]) {
                throw new InvalidArgumentException("The selected product price is below its minimum sale rate. Update the product's variant prices first.");
            }
        } else {
            $rate = $submittedRate;
            if ($rate === false || $rate === null || $rate < 0) {
                throw new InvalidArgumentException("Enter a valid non-negative rate.");
            }
        }

        $amount = round($quantity * $rate, 2);
        if ($module === "sale" && $quantity > (float) $variant["current_stock"]) {
            throw new InvalidArgumentException("Not enough stock. Available quantity is " . number_format((float) $variant["current_stock"], 3) . ".");
        }
        if ($module === "purchase-return" && $quantity > (float) $variant["current_stock"]) {
            throw new InvalidArgumentException("Cannot return more stock than currently available.");
        }

        if (in_array($module, ["purchase", "sale"], true)) {
            $partyTable = $module === "purchase" ? "vendors" : "customers";
            if ($module === "purchase" && !$partyId) {
                throw new InvalidArgumentException("Choose a vendor.");
            }
            if ($partyId) {
                $partyCheck = $pdo->prepare("SELECT id FROM `$partyTable` WHERE id = ? AND status = 1");
                $partyCheck->execute([$partyId]);
                if (!$partyCheck->fetchColumn()) {
                    throw new InvalidArgumentException("Select an active party.");
                }
            }
            if ($module === "sale") {
                if (!$workerId) {
                    throw new InvalidArgumentException("Choose the worker who made this sale.");
                }
                $workerQuery = $pdo->prepare("SELECT id, name, commission_type, commission_rate FROM workers WHERE id = ? AND status = 1 FOR UPDATE");
                $workerQuery->execute([$workerId]);
                $worker = $workerQuery->fetch();
                if (!$worker) {
                    throw new InvalidArgumentException("Choose an active sales worker.");
                }
            }
            $paymentMethodId = filter_input(INPUT_POST, "payment_method_id", FILTER_VALIDATE_INT) ?: null;
            $paid = filter_input(INPUT_POST, "paid_amount", FILTER_VALIDATE_FLOAT);
            $paid = $paid === false ? 0 : $paid;
            if ($paid < 0 || $paid > $amount) {
                throw new InvalidArgumentException("Paid amount must be between zero and the invoice total.");
            }
            $invoiceNo = ($module === "purchase" ? "PUR-" : "SAL-") . date("Ymd-His") . "-" . random_int(100, 999);
            if ($module === "purchase") {
                $insert = $pdo->prepare("INSERT INTO purchases (invoice_no, vendor_id, purchase_date, subtotal, grand_total, paid_amount, remaining_amount, payment_method_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$invoiceNo, $partyId, $date, $amount, $amount, $paid, $amount - $paid, $paymentMethodId, $reason ?: null, $userId]);
                $headerId = (int) $pdo->lastInsertId();
                $itemInsert = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_variant_id, quantity, rate, total) VALUES (?, ?, ?, ?, ?)");
                $itemInsert->execute([$headerId, $variantId, $quantity, $rate, $amount]);
                $movementType = "purchase";
                $in = $quantity;
                $out = 0;
                $newStock = (float) $variant["current_stock"] + $quantity;
            } else {
                $insert = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, worker_id, sale_date, subtotal, grand_total, paid_amount, remaining_amount, payment_method_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$invoiceNo, $partyId ?: null, $workerId, $date, $amount, $amount, $paid, $amount - $paid, $paymentMethodId, $reason ?: null, $userId]);
                $headerId = (int) $pdo->lastInsertId();
                $itemInsert = $pdo->prepare("INSERT INTO sale_items (sale_id, product_variant_id, quantity, rate, cost_rate, total) VALUES (?, ?, ?, ?, ?, ?)");
                $itemInsert->execute([$headerId, $variantId, $quantity, $rate, $variant["purchase_rate"], $amount]);
                $commissionType = $worker["commission_type"];
                $commissionRate = (float) $worker["commission_rate"];
                if ($commissionType === "percentage") {
                    $commissionAmount = round($amount * $commissionRate / 100, 2);
                } elseif ($commissionType === "quantity") {
                    $commissionAmount = round($quantity * $commissionRate, 2);
                } else {
                    $commissionAmount = round($commissionRate, 2);
                }
                $commission = $pdo->prepare("
                    INSERT INTO worker_commissions (
                        worker_id, sale_id, commission_date, sale_amount,
                        commission_type, commission_rate, commission_amount,
                        status, notes, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', ?, ?)
                ");
                $commission->execute([
                    $workerId,
                    $headerId,
                    $date,
                    $amount,
                    $commissionType,
                    $commissionRate,
                    $commissionAmount,
                    "Automatically calculated from sale invoice " . $invoiceNo,
                    $userId,
                ]);
                $movementType = "sale";
                $in = 0;
                $out = $quantity;
                $newStock = (float) $variant["current_stock"] - $quantity;
            }
            $refId = $headerId;
            $refNo = $invoiceNo;
        } elseif (in_array($module, ["purchase-return", "sale-return"], true)) {
            $partyTable = $module === "purchase-return" ? "vendors" : "customers";
            if ($module === "purchase-return" && !$partyId) {
                throw new InvalidArgumentException("Choose a vendor for the purchase return.");
            }
            $partyCheck = $pdo->prepare("SELECT id FROM `$partyTable` WHERE id = ? AND status = 1");
            if ($partyId) {
                $partyCheck->execute([$partyId]);
                if (!$partyCheck->fetchColumn()) {
                    throw new InvalidArgumentException("Select an active party.");
                }
            }
            $returnNo = ($module === "purchase-return" ? "PR-" : "SR-") . date("Ymd-His") . "-" . random_int(100, 999);
            if ($module === "purchase-return") {
                $insert = $pdo->prepare("INSERT INTO purchase_returns (return_no, vendor_id, return_date, total_amount, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $insert->execute([$returnNo, $partyId, $date, $amount, $reason ?: null, $userId]);
                $headerId = (int) $pdo->lastInsertId();
                $itemInsert = $pdo->prepare("INSERT INTO purchase_return_items (purchase_return_id, product_variant_id, quantity, rate, total) VALUES (?, ?, ?, ?, ?)");
                $itemInsert->execute([$headerId, $variantId, $quantity, $rate, $amount]);
                $movementType = "purchase_return";
                $in = 0;
                $out = $quantity;
                $newStock = (float) $variant["current_stock"] - $quantity;
            } else {
                $insert = $pdo->prepare("INSERT INTO sales_returns (return_no, customer_id, return_date, total_amount, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $insert->execute([$returnNo, $partyId ?: null, $date, $amount, $reason ?: null, $userId]);
                $headerId = (int) $pdo->lastInsertId();
                $itemInsert = $pdo->prepare("INSERT INTO sales_return_items (sales_return_id, product_variant_id, quantity, rate, total) VALUES (?, ?, ?, ?, ?)");
                $itemInsert->execute([$headerId, $variantId, $quantity, $rate, $amount]);
                $movementType = "sale_return";
                $in = $quantity;
                $out = 0;
                $newStock = (float) $variant["current_stock"] + $quantity;
            }
            $refId = $headerId;
            $refNo = $returnNo;
        } else {
            $movementType = $module === "opening-stock" ? "opening" : "adjustment";
            $direction = $_POST["direction"] ?? "in";
            if ($module === "stock-adjustment" && !in_array($direction, ["in", "out"], true)) {
                throw new InvalidArgumentException("Choose whether to add or remove stock.");
            }
            if ($module === "opening-stock") {
                $newStock = $quantity;
                $difference = $quantity - (float) $variant["current_stock"];
                $in = max(0, $difference);
                $out = max(0, -$difference);
            } else {
                $in = $direction === "in" ? $quantity : 0;
                $out = $direction === "out" ? $quantity : 0;
                if ($out > (float) $variant["current_stock"]) {
                    throw new InvalidArgumentException("Cannot remove more stock than currently available.");
                }
                $newStock = (float) $variant["current_stock"] + $in - $out;
            }
            $refId = null;
            $refNo = null;
        }

        $stockUpdate = $pdo->prepare("UPDATE product_variants SET current_stock = ? WHERE id = ?");
        $stockUpdate->execute([$newStock, $variantId]);
        $movement = $pdo->prepare("INSERT INTO stock_movements (product_variant_id, movement_type, reference_id, reference_no, quantity_in, quantity_out, balance_stock, rate, movement_date, created_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $movement->execute([$variantId, $movementType, $refId, $refNo, $in, $out, $newStock, $rate, $date . " " . date("H:i:s"), $userId, $reason ?: null]);
        $pdo->commit();
        header("Location: operations.php?module=" . rawurlencode($module) . "&saved=1");
        exit;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception instanceof PDOException && $exception->getCode() !== "23000") {
            error_log($exception->getMessage());
        }
        $formError = $exception instanceof InvalidArgumentException ? $exception->getMessage() : "Unable to save this entry. Check the selected details and try again.";
        if ($exception instanceof PDOException && $exception->getCode() === "23000") {
            $formError = "This record conflicts with an existing entry. Check for a duplicate value and try again.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($modules[$module])) {
    $config = $modules[$module];
    try {
        $values = [];
        foreach ($config["fields"] as $field) {
            [$name, $label, $type] = $field;
            $value = trim((string) ($_POST[$name] ?? ""));
            $required = !empty($field[3]);
            if ($required && $value === "") {
                throw new InvalidArgumentException($label . " is required.");
            }
            if ($value === "") {
                continue;
            }
            if ($type === "number" && (!is_numeric($value) || (float) $value < 0)) {
                throw new InvalidArgumentException("Enter a valid non-negative " . strtolower($label) . ".");
            }
            if ($type === "email" && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Enter a valid email address.");
            }
            if ($type === "select" && isset($field[4]) && !in_array($value, $field[4], true)) {
                throw new InvalidArgumentException("Choose a valid " . strtolower($label) . ".");
            }
            if ($type === "status") {
                $value = (int) $value === 1 ? 1 : 0;
            }
            if ($type === "password") {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            if ($type === "option:roles" && $name === "role_id") {
                $value = filter_var($value, FILTER_VALIDATE_INT) ?: null;
            } elseif (strpos($type, "option:") === 0) {
                $value = filter_var($value, FILTER_VALIDATE_INT);
                if (!$value) {
                    throw new InvalidArgumentException("Choose a valid " . strtolower($label) . ".");
                }
            }
            $values[$name] = $value;
        }
        if ($module === "workers" && ($_POST["commission_type"] ?? "") === "percentage" && (float) ($_POST["commission_rate"] ?? 0) > 100) {
            throw new InvalidArgumentException("Percentage commission cannot be greater than 100%.");
        }
        foreach (($config["append"] ?? []) as $name => $how) {
            if ($how === "user") {
                $values[$name] = $userId;
            } elseif ($how === "optional-role" && !isset($values[$name])) {
                $values[$name] = null;
            }
        }
        if (!$values) {
            throw new InvalidArgumentException("Enter at least one value.");
        }
        $names = array_keys($values);
        $sql = "INSERT INTO `" . $config["table"] . "` (`" . implode("`, `", $names) . "`) VALUES (" . implode(", ", array_fill(0, count($names), "?")) . ")";
        $insert = $pdo->prepare($sql);
        $insert->execute(array_values($values));
        header("Location: operations.php?module=" . rawurlencode($module) . "&saved=1");
        exit;
    } catch (Throwable $exception) {
        if ($exception instanceof PDOException && $exception->getCode() !== "23000") {
            error_log($exception->getMessage());
        }
        $formError = $exception instanceof InvalidArgumentException ? $exception->getMessage() : ($exception instanceof PDOException && $exception->getCode() === "23000" ? "A record with one of these unique values already exists." : "Unable to save this record. Please check the values and try again.");
    }
}

$isWorkflow = isset($workflowTitles[$module]);
$isReport = strpos($module, "report-") === 0;
$pageTitle = $isWorkflow ? $workflowTitles[$module][0] : ($modules[$module]["title"] ?? ($listViews[$module][0] ?? ($module === "backup" ? "Backup" : "Business management")));
if (isset($_GET["saved"])) {
    $notice = "Saved successfully.";
}
require "../includes/header.php";

function operationField($name, $label, $type, $required, $options, $today)
{
    $id = "field-" . preg_replace('/[^a-zA-Z0-9_-]/', "-", $name);
    $requiredAttr = $required ? " required" : "";
    $value = $today;
    echo '<div class="col-12 col-md-6"><label class="form-label small fw-semibold" for="' . htmlspecialchars($id, ENT_QUOTES, "UTF-8") . '">' . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . ($required ? ' <span class="text-danger">*</span>' : "") . '</label>';
    if (strpos($type, "option:") === 0) {
        $source = substr($type, 7);
        echo '<select class="form-select" id="' . htmlspecialchars($id, ENT_QUOTES, "UTF-8") . '" name="' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '"' . $requiredAttr . '><option value="">Choose ' . htmlspecialchars(strtolower($label), ENT_QUOTES, "UTF-8") . '</option>';
        foreach (($options[$source] ?? []) as $option) {
            echo '<option value="' . (int) $option["id"] . '">' . htmlspecialchars($option["label"], ENT_QUOTES, "UTF-8") . '</option>';
        }
        echo '</select>';
    } elseif ($type === "status") {
        echo '<select class="form-select" id="' . $id . '" name="' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '"><option value="1">Active</option><option value="0">Inactive</option></select>';
    } elseif ($type === "textarea") {
        echo '<textarea class="form-control" id="' . htmlspecialchars($id, ENT_QUOTES, "UTF-8") . '" name="' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '" rows="2"></textarea>';
    } elseif ($type === "select") {
        echo '<select class="form-select" id="' . htmlspecialchars($id, ENT_QUOTES, "UTF-8") . '" name="' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '"' . $requiredAttr . '><option value="">Choose ' . htmlspecialchars(strtolower($label), ENT_QUOTES, "UTF-8") . '</option>';
        foreach (($options ?? []) as $option) {
            $optionLabels = [
                "percentage" => "Percentage of sale total",
                "fixed" => "Fixed amount per sale",
                "quantity" => "Amount per unit sold",
            ];
            $optionLabel = $optionLabels[$option] ?? ucfirst($option);
            echo '<option value="' . htmlspecialchars($option, ENT_QUOTES, "UTF-8") . '">' . htmlspecialchars($optionLabel, ENT_QUOTES, "UTF-8") . '</option>';
        }
        echo '</select>';
    } else {
        $inputType = in_array($type, ["text", "number", "date", "email", "password"], true) ? $type : "text";
        $min = $inputType === "number" ? ' min="0" step="0.001"' : "";
        $default = $inputType === "date" ? ' value="' . $value . '"' : "";
        echo '<input class="form-control" type="' . $inputType . '" id="' . htmlspecialchars($id, ENT_QUOTES, "UTF-8") . '" name="' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '"' . $default . $min . $requiredAttr . '>';
    }
    if ($name === "commission_rate") {
        echo '<small class="form-text text-secondary">Percentage: percent of sale total. Fixed: amount per sale. Per unit: amount multiplied by quantity sold.</small>';
    }
    echo '</div>';
}

if ($notice): ?><div class="alert alert-success py-2"><?= htmlspecialchars($notice, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
<?php if ($formError): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($formError, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>

<?php if ($isWorkflow):
    $optionSources = ["variants" => $pdo->query($optionQueries["variants"])->fetchAll(), "payment_methods" => $pdo->query($optionQueries["payment_methods"])->fetchAll()];
    if ($module === "sale") {
        $optionSources["workers"] = $pdo->query("SELECT id, name, commission_type, commission_rate FROM workers WHERE status = 1 ORDER BY name")->fetchAll();
        $saleVariants = $pdo->query("
            SELECT pv.id, p.name AS product_name, pv.sku, pv.current_stock,
                   pv.sale_rate, pv.retail_rate, pv.wholesale_rate, pv.minimum_sale_rate,
                   u.short_name
            FROM product_variants pv
            JOIN products p ON p.id = pv.product_id
            LEFT JOIN units u ON u.id = pv.unit_id
            WHERE pv.status = 1
            ORDER BY p.name, pv.sku
        ")->fetchAll();
    }
    $partySource = in_array($module, ["purchase", "purchase-return"], true) ? "vendors" : "customers";
    $optionSources[$partySource] = $pdo->query($optionQueries[$partySource])->fetchAll();
?>
    <div class="mb-4"><h1 class="h4 mb-1"><?= htmlspecialchars($workflowTitles[$module][0], ENT_QUOTES, "UTF-8") ?></h1><p class="text-secondary mb-0"><?= htmlspecialchars($workflowTitles[$module][1], ENT_QUOTES, "UTF-8") ?></p></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <form method="post" class="row g-3">
            <?php if (in_array($module, ["purchase", "sale", "purchase-return", "sale-return"], true)): ?>
                <?php if ($module === "sale" || $module === "sale-return"): ?>
                    <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Customer</label><select class="form-select" name="party_id"><option value="">Walk-in / not specified</option><?php foreach ($optionSources["customers"] as $option): ?><option value="<?= (int) $option["id"] ?>"><?= htmlspecialchars($option["label"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select></div>
                <?php else: ?>
                    <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Vendor <span class="text-danger">*</span></label><select class="form-select" name="party_id" required><option value="">Choose vendor</option><?php foreach ($optionSources["vendors"] as $option): ?><option value="<?= (int) $option["id"] ?>"><?= htmlspecialchars($option["label"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select></div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($module === "sale"): ?>
                <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Sales worker <span class="text-danger">*</span></label><select class="form-select" name="worker_id" id="saleWorker" required><option value="">Choose worker</option><?php foreach ($optionSources["workers"] as $workerOption): ?><option value="<?= (int) $workerOption["id"] ?>" data-commission-type="<?= htmlspecialchars($workerOption["commission_type"], ENT_QUOTES, "UTF-8") ?>" data-commission-rate="<?= htmlspecialchars((string) $workerOption["commission_rate"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($workerOption["name"], ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars($workerOption["commission_type"] === "percentage" ? rtrim(rtrim(number_format((float) $workerOption["commission_rate"], 2), "0"), ".") . "%" : "commission configured", ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select><small class="text-secondary">Each sale is recorded under the selected worker.</small></div>
            <?php endif; ?>
            <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Product variant <span class="text-danger">*</span></label><select class="form-select" name="product_variant_id" id="productVariant" required><option value="">Choose product</option><?php if ($module === "sale"): foreach ($saleVariants as $variantOption): $retail = (float) $variantOption["retail_rate"] > 0 ? (float) $variantOption["retail_rate"] : (float) $variantOption["sale_rate"]; $wholesale = (float) $variantOption["wholesale_rate"] > 0 ? (float) $variantOption["wholesale_rate"] : (float) $variantOption["sale_rate"]; ?><option value="<?= (int) $variantOption["id"] ?>" data-retail-rate="<?= htmlspecialchars((string) $retail, ENT_QUOTES, "UTF-8") ?>" data-wholesale-rate="<?= htmlspecialchars((string) $wholesale, ENT_QUOTES, "UTF-8") ?>" data-minimum-rate="<?= htmlspecialchars((string) $variantOption["minimum_sale_rate"], ENT_QUOTES, "UTF-8") ?>" data-stock="<?= htmlspecialchars((string) $variantOption["current_stock"], ENT_QUOTES, "UTF-8") ?>" data-unit="<?= htmlspecialchars($variantOption["short_name"] ?? "units", ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($variantOption["product_name"] . " · " . ($variantOption["sku"] ?: "Variant " . $variantOption["id"]) . " (stock: " . $variantOption["current_stock"] . ")", ENT_QUOTES, "UTF-8") ?></option><?php endforeach; else: foreach ($optionSources["variants"] as $option): ?><option value="<?= (int) $option["id"] ?>"><?= htmlspecialchars($option["label"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; endif; ?></select></div>
            <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label><input class="form-control" type="date" name="entry_date" value="<?= $today ?>" required></div>
            <div class="col-12 col-md-6"><label class="form-label small fw-semibold"><?= $module === "opening-stock" ? "New opening quantity" : "Quantity" ?> <span class="text-danger">*</span></label><input class="form-control" type="number" min="0.001" step="0.001" name="quantity" required></div>
            <?php if ($module !== "opening-stock" && $module !== "stock-adjustment"): ?>
                <?php if ($module === "sale"): ?>
                    <div class="col-12 col-md-6"><label class="form-label small fw-semibold" for="saleRateType">Price type</label><select class="form-select" name="rate_type" id="saleRateType"><option value="retail">Retail price</option><option value="wholesale">Wholesale price</option></select></div>
                    <div class="col-12 col-md-6"><label class="form-label small fw-semibold" for="saleRate">Saved price per unit</label><div class="input-group"><span class="input-group-text">Rs.</span><input class="form-control" type="number" min="0" step="0.01" id="saleRate" name="rate" value="0.00" readonly required></div><small class="text-secondary">Automatically filled from the selected product price.</small></div>
                <?php else: ?>
                    <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Rate per unit <span class="text-danger">*</span></label><input class="form-control" type="number" min="0" step="0.01" name="rate" required></div>
                <?php endif; ?>
            <?php else: ?>
                <input type="hidden" name="rate" value="0">
            <?php endif; ?>
            <?php if ($module === "sale"): ?>
                <div class="col-12"><div class="sale-summary-panel"><div><span>Invoice total</span><strong id="saleTotal">Rs. 0.00</strong></div><div><span>Worker commission estimate</span><strong id="saleCommission">Choose a worker</strong></div><small id="saleCommissionHelp">Commission will be recorded in the worker's daily ledger when the sale is saved.</small></div></div>
            <?php endif; ?>
            <?php if ($module === "stock-adjustment"): ?>
                <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Adjustment <span class="text-danger">*</span></label><select class="form-select" name="direction"><option value="in">Add stock</option><option value="out">Remove stock</option></select></div>
            <?php endif; ?>
            <?php if (in_array($module, ["purchase", "sale"], true)): ?>
                <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Paid amount</label><input class="form-control" type="number" min="0" step="0.01" name="paid_amount" value="0"></div>
                <div class="col-12 col-md-6"><label class="form-label small fw-semibold">Payment method</label><select class="form-select" name="payment_method_id"><option value="">Not specified</option><?php foreach ($optionSources["payment_methods"] as $option): ?><option value="<?= (int) $option["id"] ?>"><?= htmlspecialchars($option["label"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <div class="col-12"><label class="form-label small fw-semibold">Notes / reason</label><textarea class="form-control" name="reason" rows="2"></textarea></div>
            <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>Save entry</button></div>
        </form>
    </div></div>
    <?php
    $listModule = ["purchase" => "purchases", "sale" => "sales", "purchase-return" => "purchase-returns", "sale-return" => "sales-returns", "opening-stock" => "stock", "stock-adjustment" => "stock"][$module];
    $listConfig = $listViews[$listModule];
    $listRows = $pdo->query($listConfig[2])->fetchAll();
    ?>
    <div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white border-0 pt-3"><strong><?= htmlspecialchars($listConfig[0], ENT_QUOTES, "UTF-8") ?> · Recent entries</strong></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><?php foreach ($listConfig[3] as $label): ?><th><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></th><?php endforeach; ?></tr></thead><tbody><?php if (!$listRows): ?><tr><td colspan="<?= count($listConfig[3]) ?>" class="text-center text-secondary py-4">No records yet.</td></tr><?php else: foreach (array_slice($listRows, 0, 10) as $row): ?><tr><?php foreach ($listConfig[3] as $key => $label): ?><td><?= htmlspecialchars((string) ($row[$key] ?? ""), ENT_QUOTES, "UTF-8") ?></td><?php endforeach; ?></tr><?php endforeach; endif; ?></tbody></table></div></div>
<?php elseif (isset($modules[$module])):
    $config = $modules[$module];
    $optionData = [];
    foreach ($config["fields"] as $field) {
        if (strpos($field[2], "option:") === 0) {
            $source = substr($field[2], 7);
            if (!isset($optionData[$source])) {
                $optionData[$source] = $pdo->query($optionQueries[$source])->fetchAll();
            }
        }
    }
    $rows = $pdo->query($config["query"])->fetchAll();
?>
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-4"><div><h1 class="h4 mb-1"><?= htmlspecialchars($config["title"], ENT_QUOTES, "UTF-8") ?></h1><p class="text-secondary mb-0"><?= htmlspecialchars($config["description"], ENT_QUOTES, "UTF-8") ?></p><?php if ($module === "workers"): ?><p class="small text-secondary mt-2 mb-0">Set each worker's commission rule once. New sales are assigned to a worker and commission is calculated automatically.</p><?php endif; ?></div><span class="badge text-bg-light border"><?= count($rows) ?> records</span></div>
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-0 pt-3"><strong>Add <?= htmlspecialchars(rtrim($config["title"], "s"), ENT_QUOTES, "UTF-8") ?></strong></div><div class="card-body"><form method="post" class="row g-3">
                <?php foreach ($config["fields"] as $field): $fieldOptions = isset($field[4]) && is_string($field[4]) ? ($optionData[$field[4]] ?? null) : ($field[4] ?? null); operationField($field[0], $field[1], $field[2], !empty($field[3]), $fieldOptions, $today); endforeach; ?>
                <div class="col-12"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Save record</button></div>
            </form></div></div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><?php foreach ($config["columns"] as $label): ?><th><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></th><?php endforeach; ?></tr></thead><tbody>
                <?php if (!$rows): ?><tr><td colspan="<?= count($config["columns"]) ?>" class="text-center text-secondary py-5">No records yet. Use the form to add one.</td></tr><?php else: foreach ($rows as $row): ?><tr><?php foreach ($config["columns"] as $key => $label): ?><td><?php if ($key === "status"): ?><span class="badge <?= in_array($row[$key], ["paid", 1, "1"], true) ? "text-bg-success" : "text-bg-warning" ?>"><?= htmlspecialchars(ucfirst((string) $row[$key]), ENT_QUOTES, "UTF-8") ?></span><?php elseif ($key === "commission_type"): ?><?= htmlspecialchars(["percentage" => "Percentage", "fixed" => "Per sale", "quantity" => "Per unit", "custom" => "Custom"][strtolower((string) ($row[$key] ?? ""))] ?? ucfirst((string) $row[$key]), ENT_QUOTES, "UTF-8") ?><?php elseif ($key === "commission_rate" && ($row["commission_type"] ?? "") === "percentage" && is_numeric($row[$key] ?? null)): ?><?= number_format((float) $row[$key], 2) ?>%<?php elseif (in_array($key, ["amount", "opening_balance", "credit_limit", "commission_rate", "commission_amount", "sale_amount", "sales_total", "commission_paid", "commission_due", "sales", "payments", "balance", "purchases", "commission", "expenses", "debit", "credit"], true) && is_numeric($row[$key] ?? null)): ?>Rs. <?= number_format((float) $row[$key], 2) ?><?php elseif (is_string($row[$key] ?? null) && strlen($row[$key]) > 90): ?><?= htmlspecialchars(substr($row[$key], 0, 90) . "…", ENT_QUOTES, "UTF-8") ?><?php else: ?><?= htmlspecialchars((string) ($row[$key] ?? ""), ENT_QUOTES, "UTF-8") ?><?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; endif; ?>
            </tbody></table></div></div>
        </div>
    </div>
<?php elseif (isset($listViews[$module])):
    $view = $listViews[$module];
    $rows = $pdo->query($view[2])->fetchAll();
?>
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-4"><div><h1 class="h4 mb-1"><?= htmlspecialchars($view[0], ENT_QUOTES, "UTF-8") ?></h1><p class="text-secondary mb-0"><?= htmlspecialchars($view[1], ENT_QUOTES, "UTF-8") ?></p></div><span class="badge text-bg-light border"><?= count($rows) ?> records</span></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><?php foreach ($view[3] as $label): ?><th><?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?></th><?php endforeach; ?></tr></thead><tbody>
        <?php if (!$rows): ?><tr><td colspan="<?= count($view[3]) ?>" class="text-center text-secondary py-5">No records found for this report.</td></tr><?php else: foreach ($rows as $row): ?><tr><?php foreach ($view[3] as $key => $label): ?><td><?php if (in_array($key, ["grand_total", "paid_amount", "remaining_amount", "total_amount", "amount", "opening_balance", "sales", "payments", "balance", "purchases", "commission", "expenses", "debit", "credit", "stock_value", "purchase_rate", "total", "paid", "due"], true) && is_numeric($row[$key] ?? null)): ?>Rs. <?= number_format((float) $row[$key], 2) ?><?php elseif ($key === "movement_type"): ?><span class="badge text-bg-light"><?= htmlspecialchars(str_replace("_", " ", (string) $row[$key]), ENT_QUOTES, "UTF-8") ?></span><?php elseif (preg_match('/_date$|^report_date$/', $key) && !empty($row[$key])): ?><?= htmlspecialchars(date("d M Y", strtotime($row[$key])), ENT_QUOTES, "UTF-8") ?><?php else: ?><?= htmlspecialchars((string) ($row[$key] ?? ""), ENT_QUOTES, "UTF-8") ?><?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
<?php elseif ($module === "backup"): ?>
    <div class="mb-4"><h1 class="h4 mb-1">Database backup</h1><p class="text-secondary mb-0">Download a JSON snapshot of the current application data.</p></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4"><div class="d-flex align-items-start gap-3"><span class="quick-icon"><i class="bi bi-database-down"></i></span><div><h2 class="h6">Export application data</h2><p class="text-secondary">The backup contains every database table, including user password hashes. Store the downloaded file securely.</p><form method="post"><button class="btn btn-primary" type="submit"><i class="bi bi-download me-1"></i>Download JSON backup</button></form></div></div></div></div>
<?php else: ?>
    <div class="alert alert-warning">This menu entry is not available.</div>
<?php endif; ?>

<?php if ($module === "sale"): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const variantSelect = document.getElementById("productVariant");
    const workerSelect = document.getElementById("saleWorker");
    const rateType = document.getElementById("saleRateType");
    const rateInput = document.getElementById("saleRate");
    const quantityInput = document.querySelector('input[name="quantity"]');
    const totalOutput = document.getElementById("saleTotal");
    const commissionOutput = document.getElementById("saleCommission");
    const commissionHelp = document.getElementById("saleCommissionHelp");
    const formatMoney = value => "Rs. " + Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function updateSalePreview() {
        const variant = variantSelect.selectedOptions[0];
        const worker = workerSelect.selectedOptions[0];
        const priceKey = rateType.value === "wholesale" ? "wholesaleRate" : "retailRate";
        const unitRate = variant && variant.value ? Number(variant.dataset[priceKey] || 0) : 0;
        const quantity = Number(quantityInput.value || 0);
        const total = unitRate * quantity;
        rateInput.value = unitRate.toFixed(2);
        totalOutput.textContent = formatMoney(total);

        if (variant && variant.value) {
            quantityInput.max = variant.dataset.stock || "";
            quantityInput.setCustomValidity(quantity > Number(variant.dataset.stock || 0) ? "Quantity is greater than available stock." : "");
        } else {
            quantityInput.removeAttribute("max");
            quantityInput.setCustomValidity("");
        }

        if (!worker || !worker.value) {
            commissionOutput.textContent = "Choose a worker";
            commissionHelp.textContent = "Commission will be recorded in the worker's daily ledger when the sale is saved.";
            return;
        }

        const commissionType = worker.dataset.commissionType;
        const commissionRate = Number(worker.dataset.commissionRate || 0);
        let commission = 0;
        if (commissionType === "percentage") {
            commission = total * commissionRate / 100;
            commissionHelp.textContent = commissionRate + "% of this sale's total.";
        } else if (commissionType === "quantity") {
            commission = quantity * commissionRate;
            commissionHelp.textContent = formatMoney(commissionRate) + " per unit sold.";
        } else {
            commission = commissionRate;
            commissionHelp.textContent = "Fixed commission per sale.";
        }
        commissionOutput.textContent = formatMoney(commission);
    }

    [variantSelect, workerSelect, rateType, quantityInput].forEach(element => {
        element.addEventListener("change", updateSalePreview);
        element.addEventListener("input", updateSalePreview);
    });
    updateSalePreview();
});
</script>
<?php endif; ?>

<?php require "../includes/footer.php"; ?>
