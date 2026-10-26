CREATE DATABASE IF NOT EXISTS paper_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE paper_management;

-- =========================================
-- USERS
-- =========================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NULL,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    status TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE SET NULL
);

-- =========================================
-- PERMISSIONS
-- =========================================

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,

    UNIQUE KEY unique_role_permission (role_id, permission_id),

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE,

    FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON DELETE CASCADE
);

-- =========================================
-- PRODUCT MASTER DATA
-- =========================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color_code VARCHAR(20) NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    short_name VARCHAR(30) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    width DECIMAL(10,2) NULL,
    height DECIMAL(10,2) NULL,
    unit VARCHAR(30) NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE gsm (
    id INT AUTO_INCREMENT PRIMARY KEY,
    value DECIMAL(10,2) NOT NULL UNIQUE,
    unit VARCHAR(30) DEFAULT 'GSM',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- PRODUCTS
-- =========================================

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,
    brand_id INT NULL,

    name VARCHAR(200) NOT NULL,
    product_code VARCHAR(100) UNIQUE,
    barcode VARCHAR(100) UNIQUE,

    description TEXT NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (brand_id)
        REFERENCES brands(id)
        ON DELETE SET NULL
);

-- =========================================
-- PRODUCT VARIANTS
-- =========================================

CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL,

    color_id INT NULL,
    gsm_id INT NULL,
    size_id INT NULL,
    unit_id INT NOT NULL,

    sku VARCHAR(100) UNIQUE,

    purchase_rate DECIMAL(15,2) DEFAULT 0,
    sale_rate DECIMAL(15,2) DEFAULT 0,
    wholesale_rate DECIMAL(15,2) DEFAULT 0,
    retail_rate DECIMAL(15,2) DEFAULT 0,

    minimum_sale_rate DECIMAL(15,2) DEFAULT 0,

    minimum_stock DECIMAL(15,3) DEFAULT 0,

    opening_stock DECIMAL(15,3) DEFAULT 0,
    current_stock DECIMAL(15,3) DEFAULT 0,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE,

    FOREIGN KEY (color_id)
        REFERENCES colors(id)
        ON DELETE SET NULL,

    FOREIGN KEY (gsm_id)
        REFERENCES gsm(id)
        ON DELETE SET NULL,

    FOREIGN KEY (size_id)
        REFERENCES sizes(id)
        ON DELETE SET NULL,

    FOREIGN KEY (unit_id)
        REFERENCES units(id)
        ON DELETE RESTRICT
);

-- =========================================
-- CUSTOMERS
-- =========================================

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(200) NOT NULL,
    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(150) NULL,

    address TEXT NULL,

    opening_balance DECIMAL(15,2) DEFAULT 0,
    credit_limit DECIMAL(15,2) DEFAULT 0,

    notes TEXT NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================
-- VENDORS
-- =========================================

CREATE TABLE vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(200) NOT NULL,
    company VARCHAR(200) NULL,

    phone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    email VARCHAR(150) NULL,

    address TEXT NULL,

    opening_balance DECIMAL(15,2) DEFAULT 0,

    notes TEXT NULL,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================
-- WORKERS
-- =========================================

CREATE TABLE workers (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(200) NOT NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,

    joining_date DATE NULL,

    commission_type ENUM(
        'percentage',
        'fixed',
        'quantity',
        'custom'
    ) DEFAULT 'percentage',

    commission_rate DECIMAL(15,2) DEFAULT 0,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================
-- PAYMENT METHODS
-- =========================================

CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL UNIQUE,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- PURCHASES
-- =========================================

CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,

    invoice_no VARCHAR(100) NOT NULL UNIQUE,

    vendor_id INT NOT NULL,

    purchase_date DATE NOT NULL,

    subtotal DECIMAL(15,2) DEFAULT 0,
    discount DECIMAL(15,2) DEFAULT 0,
    tax DECIMAL(15,2) DEFAULT 0,
    other_charges DECIMAL(15,2) DEFAULT 0,

    grand_total DECIMAL(15,2) DEFAULT 0,

    paid_amount DECIMAL(15,2) DEFAULT 0,
    remaining_amount DECIMAL(15,2) DEFAULT 0,

    payment_method_id INT NULL,

    notes TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_id INT NOT NULL,
    product_variant_id INT NOT NULL,

    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,

    rate DECIMAL(15,2) NOT NULL DEFAULT 0,

    discount DECIMAL(15,2) DEFAULT 0,

    total DECIMAL(15,2) DEFAULT 0,

    FOREIGN KEY (purchase_id)
        REFERENCES purchases(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_variant_id)
        REFERENCES product_variants(id)
        ON DELETE RESTRICT
);

-- =========================================
-- PURCHASE RETURNS
-- =========================================

CREATE TABLE purchase_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,

    return_no VARCHAR(100) NOT NULL UNIQUE,

    purchase_id INT NULL,
    vendor_id INT NOT NULL,

    return_date DATE NOT NULL,

    total_amount DECIMAL(15,2) DEFAULT 0,

    reason TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (purchase_id)
        REFERENCES purchases(id)
        ON DELETE SET NULL,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE purchase_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_return_id INT NOT NULL,
    product_variant_id INT NOT NULL,

    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    rate DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,

    FOREIGN KEY (purchase_return_id)
        REFERENCES purchase_returns(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_variant_id)
        REFERENCES product_variants(id)
        ON DELETE RESTRICT
);

-- =========================================
-- SALES
-- =========================================

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,

    invoice_no VARCHAR(100) NOT NULL UNIQUE,

    customer_id INT NULL,
    worker_id INT NULL,

    sale_date DATE NOT NULL,

    subtotal DECIMAL(15,2) DEFAULT 0,
    discount DECIMAL(15,2) DEFAULT 0,
    tax DECIMAL(15,2) DEFAULT 0,
    other_charges DECIMAL(15,2) DEFAULT 0,

    grand_total DECIMAL(15,2) DEFAULT 0,

    paid_amount DECIMAL(15,2) DEFAULT 0,
    remaining_amount DECIMAL(15,2) DEFAULT 0,

    payment_method_id INT NULL,

    notes TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE SET NULL,

    FOREIGN KEY (worker_id)
        REFERENCES workers(id)
        ON DELETE SET NULL,

    FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    sale_id INT NOT NULL,
    product_variant_id INT NOT NULL,

    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,

    rate DECIMAL(15,2) NOT NULL DEFAULT 0,

    cost_rate DECIMAL(15,2) DEFAULT 0,

    discount DECIMAL(15,2) DEFAULT 0,

    total DECIMAL(15,2) DEFAULT 0,

    FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_variant_id)
        REFERENCES product_variants(id)
        ON DELETE RESTRICT
);

-- =========================================
-- SALES RETURNS
-- =========================================

CREATE TABLE sales_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,

    return_no VARCHAR(100) NOT NULL UNIQUE,

    sale_id INT NULL,
    customer_id INT NULL,

    return_date DATE NOT NULL,

    total_amount DECIMAL(15,2) DEFAULT 0,

    reason TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE SET NULL,

    FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE sales_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    sales_return_id INT NOT NULL,
    product_variant_id INT NOT NULL,

    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    rate DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,

    FOREIGN KEY (sales_return_id)
        REFERENCES sales_returns(id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_variant_id)
        REFERENCES product_variants(id)
        ON DELETE RESTRICT
);

-- =========================================
-- STOCK MOVEMENT
-- =========================================

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,

    product_variant_id INT NOT NULL,

    movement_type ENUM(
        'opening',
        'purchase',
        'purchase_return',
        'sale',
        'sale_return',
        'adjustment',
        'damage',
        'loss'
    ) NOT NULL,

    reference_id INT NULL,
    reference_no VARCHAR(100) NULL,

    quantity_in DECIMAL(15,3) DEFAULT 0,
    quantity_out DECIMAL(15,3) DEFAULT 0,

    balance_stock DECIMAL(15,3) DEFAULT 0,

    rate DECIMAL(15,2) DEFAULT 0,

    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    created_by INT NULL,

    notes TEXT NULL,

    FOREIGN KEY (product_variant_id)
        REFERENCES product_variants(id)
        ON DELETE CASCADE,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- PAYMENTS
-- =========================================

CREATE TABLE customer_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,
    sale_id INT NULL,

    payment_date DATE NOT NULL,

    amount DECIMAL(15,2) NOT NULL,

    payment_method_id INT NULL,

    reference_no VARCHAR(100) NULL,

    notes TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES customers(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE SET NULL,

    FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE vendor_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,
    purchase_id INT NULL,

    payment_date DATE NOT NULL,

    amount DECIMAL(15,2) NOT NULL,

    payment_method_id INT NULL,

    reference_no VARCHAR(100) NULL,

    notes TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (purchase_id)
        REFERENCES purchases(id)
        ON DELETE SET NULL,

    FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- WORKER COMMISSIONS
-- =========================================

CREATE TABLE worker_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    worker_id INT NOT NULL,
    sale_id INT NULL,

    commission_date DATE NOT NULL,

    sale_amount DECIMAL(15,2) DEFAULT 0,

    commission_type VARCHAR(50) NULL,
    commission_rate DECIMAL(15,2) DEFAULT 0,

    commission_amount DECIMAL(15,2) DEFAULT 0,

    status ENUM(
        'unpaid',
        'paid'
    ) DEFAULT 'unpaid',

    notes TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (worker_id)
        REFERENCES workers(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- WORKER EXPENSES
-- =========================================

CREATE TABLE worker_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,

    worker_id INT NOT NULL,

    expense_date DATE NOT NULL,

    expense_type VARCHAR(100) NULL,

    amount DECIMAL(15,2) NOT NULL,

    description TEXT NULL,

    status ENUM(
        'unpaid',
        'paid'
    ) DEFAULT 'unpaid',

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (worker_id)
        REFERENCES workers(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- GENERAL EXPENSES
-- =========================================

CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL UNIQUE,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,

    expense_category_id INT NULL,

    expense_date DATE NOT NULL,

    amount DECIMAL(15,2) NOT NULL,

    payment_method_id INT NULL,

    description TEXT NULL,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (expense_category_id)
        REFERENCES expense_categories(id)
        ON DELETE SET NULL,

    FOREIGN KEY (payment_method_id)
        REFERENCES payment_methods(id)
        ON DELETE SET NULL,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- CHART OF ACCOUNTS
-- =========================================

CREATE TABLE chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,

    parent_id INT NULL,

    account_code VARCHAR(50) UNIQUE,
    account_name VARCHAR(200) NOT NULL,

    account_type ENUM(
        'asset',
        'liability',
        'equity',
        'income',
        'expense'
    ) NOT NULL,

    is_system TINYINT(1) DEFAULT 0,

    status TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id)
        REFERENCES chart_of_accounts(id)
        ON DELETE SET NULL
);

-- =========================================
-- ACCOUNT TRANSACTIONS
-- =========================================

CREATE TABLE account_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    account_id INT NOT NULL,

    transaction_date DATE NOT NULL,

    reference_type VARCHAR(100) NULL,
    reference_id INT NULL,

    description TEXT NULL,

    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (account_id)
        REFERENCES chart_of_accounts(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- AUDIT LOG
-- =========================================

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NULL,

    record_id INT NULL,

    description TEXT NULL,

    ip_address VARCHAR(45) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================
-- SETTINGS
-- =========================================

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(150) NOT NULL UNIQUE,
    setting_value TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================
-- DEFAULT ROLES
-- =========================================

INSERT INTO roles (name, description)
VALUES
('Admin', 'Full system access'),
('Manager', 'Management access'),
('Staff', 'Limited operational access');

-- =========================================
-- DEFAULT PAYMENT METHODS
-- =========================================

INSERT INTO payment_methods (name)
VALUES
('Cash'),
('Bank'),
('Online'),
('Cheque'),
('Other');

-- =========================================
-- DEFAULT UNITS
-- =========================================

INSERT INTO units (name, short_name)
VALUES
('Piece', 'pcs'),
('Sheet', 'sheet'),
('Packet', 'pkt'),
('Box', 'box'),
('Ream', 'ream'),
('Kilogram', 'kg'),
('Gram', 'g'),
('Meter', 'm'),
('Roll', 'roll');

-- =========================================
-- DEFAULT EXPENSE CATEGORIES
-- =========================================

INSERT INTO expense_categories (name)
VALUES
('Rent'),
('Electricity'),
('Internet'),
('Transport'),
('Salary'),
('Maintenance'),
('Printing'),
('Food'),
('Other');

-- =========================================
-- DEFAULT SETTINGS
-- =========================================

INSERT INTO settings (setting_key, setting_value)
VALUES
('company_name', 'Paper Management System'),
('currency', 'PKR'),
('sale_prefix', 'SALE-'),
('purchase_prefix', 'PUR-'),
('sale_return_prefix', 'SR-'),
('purchase_return_prefix', 'PR-'),
('low_stock_alert', '1');