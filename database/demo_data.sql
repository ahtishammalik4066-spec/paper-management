DELIMITER $$

DROP PROCEDURE IF EXISTS seed_paperflow_demo_data$$

CREATE PROCEDURE seed_paperflow_demo_data()
main: BEGIN
    DECLARE demo_tag VARCHAR(40) DEFAULT 'DEMO-PF-20261024';
    DECLARE category_id INT;
    DECLARE brand_id INT;
    DECLARE color_id INT;
    DECLARE customer_one_id INT;
    DECLARE customer_two_id INT;
    DECLARE customer_three_id INT;
    DECLARE vendor_one_id INT;
    DECLARE vendor_two_id INT;
    DECLARE worker_id INT;
    DECLARE payment_method_id INT;
    DECLARE user_id INT;
    DECLARE variant_id INT;
    DECLARE sale_id INT;
    DECLARE purchase_id INT;
    DECLARE item_quantity DECIMAL(15,3);
    DECLARE item_rate DECIMAL(15,2);
    DECLARE item_total DECIMAL(15,2);
    DECLARE stock_balance DECIMAL(15,3);
    DECLARE entry_date DATE;
    DECLARE variant_sku VARCHAR(100);
    DECLARE customer_id INT;
    DECLARE vendor_id INT;
    DECLARE invoice_number VARCHAR(100);
    DECLARE iteration INT DEFAULT 0;
    DECLARE worker_sales_today INT DEFAULT 0;
    DECLARE worker_sales_id INT;
    DECLARE worker_commission_type VARCHAR(50);
    DECLARE worker_commission_rate DECIMAL(15,2);

    IF EXISTS (
        SELECT 1
        FROM sales
        WHERE notes LIKE CONCAT(demo_tag, '%')
        LIMIT 1
    ) THEN
        START TRANSACTION;

        SELECT id INTO user_id
        FROM users
        WHERE username = 'admin'
        LIMIT 1;

        SELECT id INTO payment_method_id
        FROM payment_methods
        WHERE name = 'Cash'
        LIMIT 1;

        IF NOT EXISTS (
            SELECT 1
            FROM purchases
            WHERE invoice_no = 'DEMO-PUR-LOSS-20261024'
        ) THEN
            SELECT id INTO vendor_id
            FROM vendors
            WHERE notes = demo_tag
            ORDER BY id
            LIMIT 1;

            SELECT id, purchase_rate
            INTO variant_id, item_rate
            FROM product_variants
            WHERE sku = 'DEMO-PF-20261024-THERMAL-BOX'
            LIMIT 1;

            SET item_quantity = 250;
            SET item_total = ROUND(item_quantity * item_rate, 2);
            SET invoice_number = 'DEMO-PUR-LOSS-20261024';
            INSERT INTO purchases (
                invoice_no, vendor_id, purchase_date, subtotal, grand_total,
                paid_amount, remaining_amount, payment_method_id, notes, created_by
            )
            VALUES (
                invoice_number, vendor_id, CURDATE(), item_total, item_total,
                ROUND(item_total * 0.65, 2), item_total - ROUND(item_total * 0.65, 2),
                payment_method_id, CONCAT(demo_tag, ' | sample monthly loss transaction'), user_id
            );
            SET purchase_id = LAST_INSERT_ID();

            INSERT INTO purchase_items (purchase_id, product_variant_id, quantity, rate, total)
            VALUES (purchase_id, variant_id, item_quantity, item_rate, item_total);

            UPDATE product_variants
            SET current_stock = current_stock + item_quantity
            WHERE id = variant_id;

            SELECT current_stock INTO stock_balance
            FROM product_variants
            WHERE id = variant_id;

            INSERT INTO stock_movements (
                product_variant_id, movement_type, reference_id, reference_no,
                quantity_in, quantity_out, balance_stock, rate, movement_date,
                created_by, notes
            )
            VALUES (
                variant_id, 'purchase', purchase_id, invoice_number,
                item_quantity, 0, stock_balance, item_rate, NOW(),
                user_id, CONCAT(demo_tag, ' | sample monthly loss transaction')
            );
        END IF;

        SELECT w.id, w.commission_type, w.commission_rate
        INTO worker_sales_id, worker_commission_type, worker_commission_rate
        FROM workers w
        WHERE w.name = 'DEMO - Ayaan Operations'
        ORDER BY w.id
        LIMIT 1;

        SELECT COUNT(*) INTO worker_sales_today
        FROM sales s
        WHERE s.worker_id = worker_sales_id
            AND s.sale_date = CURDATE()
            AND s.notes LIKE CONCAT(demo_tag, '%');

        IF worker_sales_today = 0 THEN
            SELECT id INTO customer_id
            FROM customers
            WHERE notes = demo_tag
            ORDER BY id
            LIMIT 1;

            SELECT id, retail_rate
            INTO variant_id, item_rate
            FROM product_variants
            WHERE sku = 'DEMO-PF-20261024-THERMAL-BOX'
            LIMIT 1;

            SET item_quantity = 50;
            SET item_total = ROUND(item_quantity * item_rate, 2);
            SET invoice_number = CONCAT('DEMO-SAL-WORKER-', DATE_FORMAT(CURDATE(), '%Y%m%d'));

            INSERT INTO sales (
                invoice_no, customer_id, worker_id, sale_date, subtotal, grand_total,
                paid_amount, remaining_amount, payment_method_id, notes, created_by
            )
            VALUES (
                invoice_number, customer_id, worker_sales_id, CURDATE(), item_total, item_total,
                item_total, 0, payment_method_id,
                CONCAT(demo_tag, ' | sample worker dashboard sale'), user_id
            );
            SET sale_id = LAST_INSERT_ID();

            INSERT INTO sale_items (sale_id, product_variant_id, quantity, rate, cost_rate, total)
            SELECT sale_id, id, item_quantity, item_rate, purchase_rate, item_total
            FROM product_variants
            WHERE id = variant_id;

            INSERT INTO worker_commissions (
                worker_id, sale_id, commission_date, sale_amount, commission_type,
                commission_rate, commission_amount, status, notes, created_by
            )
            VALUES (
                worker_sales_id, sale_id, CURDATE(), item_total, worker_commission_type,
                worker_commission_rate,
                CASE
                    WHEN worker_commission_type = 'percentage' THEN ROUND(item_total * worker_commission_rate / 100, 2)
                    WHEN worker_commission_type = 'quantity' THEN ROUND(item_quantity * worker_commission_rate, 2)
                    ELSE ROUND(worker_commission_rate, 2)
                END,
                'unpaid', CONCAT(demo_tag, ' | sample worker dashboard commission'), user_id
            );

            UPDATE product_variants
            SET current_stock = current_stock - item_quantity
            WHERE id = variant_id;

            SELECT current_stock INTO stock_balance
            FROM product_variants
            WHERE id = variant_id;

            INSERT INTO stock_movements (
                product_variant_id, movement_type, reference_id, reference_no,
                quantity_in, quantity_out, balance_stock, rate, movement_date,
                created_by, notes
            )
            VALUES (
                variant_id, 'sale', sale_id, invoice_number,
                0, item_quantity, stock_balance, item_rate, NOW(),
                user_id, CONCAT(demo_tag, ' | sample worker dashboard sale')
            );
        END IF;

        COMMIT;
        SELECT 'Demo worker sales and loss-chart data verified successfully.' AS status;
        LEAVE main;
    END IF;

    START TRANSACTION;

    SELECT id INTO user_id
    FROM users
    WHERE username = 'admin'
    LIMIT 1;

    SELECT id INTO payment_method_id
    FROM payment_methods
    WHERE name = 'Cash'
    LIMIT 1;

    INSERT INTO categories (name, description)
    VALUES ('DEMO - Paper & Packaging', 'Sample category for the customer presentation dataset.');
    SET category_id = LAST_INSERT_ID();

    INSERT INTO brands (name, description)
    VALUES ('DEMO - PaperFlow Select', 'Sample brand for the customer presentation dataset.');
    SET brand_id = LAST_INSERT_ID();

    INSERT INTO colors (name, color_code)
    VALUES ('DEMO - Bright White', '#F8FAFC');
    SET color_id = LAST_INSERT_ID();

    INSERT INTO customers (name, phone, email, address, notes)
    VALUES
        ('DEMO - Northstar Print House', '+92 300 555 0101', 'northstar@example.invalid', 'Lahore', demo_tag),
        ('DEMO - Crescent Stationery Co.', '+92 300 555 0102', 'crescent@example.invalid', 'Islamabad', demo_tag),
        ('DEMO - Bluebird Packaging', '+92 300 555 0103', 'bluebird@example.invalid', 'Karachi', demo_tag);
    SET customer_one_id = LAST_INSERT_ID();
    SET customer_two_id = customer_one_id + 1;
    SET customer_three_id = customer_one_id + 2;

    INSERT INTO vendors (name, company, phone, address, notes)
    VALUES
        ('DEMO - Summit Paper Traders', 'Summit Paper Traders', '+92 300 555 0201', 'Lahore', demo_tag),
        ('DEMO - Greenline Packaging Supply', 'Greenline Packaging Supply', '+92 300 555 0202', 'Faisalabad', demo_tag);
    SET vendor_one_id = LAST_INSERT_ID();
    SET vendor_two_id = vendor_one_id + 1;

    INSERT INTO workers (name, phone, joining_date, commission_type, commission_rate)
    VALUES ('DEMO - Ayaan Operations', '+92 300 555 0301', DATE_SUB(CURDATE(), INTERVAL 120 DAY), 'percentage', 2.50);
    SET worker_id = LAST_INSERT_ID();

    INSERT INTO products (category_id, brand_id, name, product_code, description)
    VALUES
        (category_id, brand_id, 'DEMO - Premium A4 Copy Paper', 'DEMO-PF-20261024-A4', 'Sample A4 office paper for the customer dashboard demonstration.'),
        (category_id, brand_id, 'DEMO - Natural Kraft Paper Roll', 'DEMO-PF-20261024-KRAFT', 'Sample kraft paper roll for packaging demonstrations.'),
        (category_id, brand_id, 'DEMO - Smooth Card Stock Ream', 'DEMO-PF-20261024-CARD', 'Sample card stock paper for printing demonstrations.'),
        (category_id, brand_id, 'DEMO - Thermal Receipt Roll Box', 'DEMO-PF-20261024-THERMAL', 'Sample thermal rolls for point-of-sale demonstrations.');

    INSERT INTO product_variants (
        product_id, color_id, unit_id, sku, purchase_rate, sale_rate,
        wholesale_rate, retail_rate, minimum_sale_rate, minimum_stock,
        opening_stock, current_stock
    )
    SELECT p.id, color_id, u.id, 'DEMO-PF-20261024-A4-SHEET', 0.85, 1.20, 1.05, 1.20, 0.95, 180, 900, 900
    FROM products p JOIN units u ON u.short_name = 'sheet'
    WHERE p.product_code = 'DEMO-PF-20261024-A4'
    LIMIT 1;

    INSERT INTO product_variants (
        product_id, color_id, unit_id, sku, purchase_rate, sale_rate,
        wholesale_rate, retail_rate, minimum_sale_rate, minimum_stock,
        opening_stock, current_stock
    )
    SELECT p.id, color_id, u.id, 'DEMO-PF-20261024-KRAFT-ROLL', 92, 145, 128, 145, 115, 35, 900, 900
    FROM products p JOIN units u ON u.short_name = 'roll'
    WHERE p.product_code = 'DEMO-PF-20261024-KRAFT'
    LIMIT 1;

    INSERT INTO product_variants (
        product_id, color_id, unit_id, sku, purchase_rate, sale_rate,
        wholesale_rate, retail_rate, minimum_sale_rate, minimum_stock,
        opening_stock, current_stock
    )
    SELECT p.id, color_id, u.id, 'DEMO-PF-20261024-CARD-REAM', 1180, 1590, 1425, 1590, 1320, 30, 900, 900
    FROM products p JOIN units u ON u.short_name = 'ream'
    WHERE p.product_code = 'DEMO-PF-20261024-CARD'
    LIMIT 1;

    INSERT INTO product_variants (
        product_id, color_id, unit_id, sku, purchase_rate, sale_rate,
        wholesale_rate, retail_rate, minimum_sale_rate, minimum_stock,
        opening_stock, current_stock
    )
    SELECT p.id, color_id, u.id, 'DEMO-PF-20261024-THERMAL-BOX', 2240, 2980, 2690, 2980, 2520, 24, 900, 900
    FROM products p JOIN units u ON u.short_name = 'box'
    WHERE p.product_code = 'DEMO-PF-20261024-THERMAL'
    LIMIT 1;

    INSERT INTO stock_movements (
        product_variant_id, movement_type, quantity_in, quantity_out,
        balance_stock, rate, movement_date, created_by, notes
    )
    SELECT id, 'opening', opening_stock, 0, opening_stock, purchase_rate,
           TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 30 DAY), '09:00:00'),
           user_id, CONCAT(demo_tag, ' | opening stock sample')
    FROM product_variants
    WHERE sku LIKE 'DEMO-PF-20261024-%';

    SET iteration = 0;
    WHILE iteration < 14 DO
        SET entry_date = DATE_SUB(CURDATE(), INTERVAL (13 - iteration) DAY);
        SET variant_sku = CASE MOD(iteration, 4)
            WHEN 0 THEN 'DEMO-PF-20261024-A4-SHEET'
            WHEN 1 THEN 'DEMO-PF-20261024-KRAFT-ROLL'
            WHEN 2 THEN 'DEMO-PF-20261024-CARD-REAM'
            ELSE 'DEMO-PF-20261024-THERMAL-BOX'
        END;

        SELECT id, retail_rate
        INTO variant_id, item_rate
        FROM product_variants
        WHERE sku = variant_sku;

        SET item_quantity = 70 + MOD(iteration * 17, 80);
        SET item_total = ROUND(item_quantity * item_rate, 2);
        SET customer_id = CASE MOD(iteration, 3)
            WHEN 0 THEN customer_one_id
            WHEN 1 THEN customer_two_id
            ELSE customer_three_id
        END;
        SET invoice_number = CONCAT('DEMO-SAL-', DATE_FORMAT(entry_date, '%Y%m%d'), '-', LPAD(iteration + 1, 2, '0'));

        INSERT INTO sales (
            invoice_no, customer_id, worker_id, sale_date, subtotal, grand_total,
            paid_amount, remaining_amount, payment_method_id, notes, created_by
        )
        VALUES (
            invoice_number, customer_id, worker_id, entry_date, item_total, item_total,
            ROUND(item_total * 0.72, 2), item_total - ROUND(item_total * 0.72, 2),
            payment_method_id, CONCAT(demo_tag, ' | sample dashboard transaction'), user_id
        );
        SET sale_id = LAST_INSERT_ID();

        INSERT INTO sale_items (sale_id, product_variant_id, quantity, rate, cost_rate, total)
        SELECT sale_id, id, item_quantity, item_rate, purchase_rate, item_total
        FROM product_variants
        WHERE id = variant_id;

        INSERT INTO worker_commissions (
            worker_id, sale_id, commission_date, sale_amount, commission_type,
            commission_rate, commission_amount, status, notes, created_by
        )
        SELECT worker_id, sale_id, entry_date, item_total, commission_type,
               commission_rate,
               CASE
                   WHEN commission_type = 'percentage' THEN ROUND(item_total * commission_rate / 100, 2)
                   WHEN commission_type = 'quantity' THEN ROUND(item_quantity * commission_rate, 2)
                   ELSE ROUND(commission_rate, 2)
               END,
               'unpaid', CONCAT(demo_tag, ' | sample dashboard commission'), user_id
        FROM workers
        WHERE id = worker_id;

        UPDATE product_variants
        SET current_stock = current_stock - item_quantity
        WHERE id = variant_id;

        SELECT current_stock INTO stock_balance
        FROM product_variants
        WHERE id = variant_id;

        INSERT INTO stock_movements (
            product_variant_id, movement_type, reference_id, reference_no,
            quantity_in, quantity_out, balance_stock, rate, movement_date,
            created_by, notes
        )
        VALUES (
            variant_id, 'sale', sale_id, invoice_number, 0, item_quantity,
            stock_balance, item_rate, TIMESTAMP(entry_date, '15:00:00'),
            user_id, CONCAT(demo_tag, ' | sample dashboard transaction')
        );

        IF MOD(iteration, 2) = 0 THEN
            SET variant_sku = CASE MOD(iteration + 1, 4)
                WHEN 0 THEN 'DEMO-PF-20261024-A4-SHEET'
                WHEN 1 THEN 'DEMO-PF-20261024-KRAFT-ROLL'
                WHEN 2 THEN 'DEMO-PF-20261024-CARD-REAM'
                ELSE 'DEMO-PF-20261024-THERMAL-BOX'
            END;

            SELECT id, purchase_rate
            INTO variant_id, item_rate
            FROM product_variants
            WHERE sku = variant_sku;

            SET item_quantity = 210 + MOD(iteration * 19, 80);
            SET item_total = ROUND(item_quantity * item_rate, 2);
            SET vendor_id = IF(MOD(iteration, 4) = 0, vendor_one_id, vendor_two_id);
            SET invoice_number = CONCAT('DEMO-PUR-', DATE_FORMAT(entry_date, '%Y%m%d'), '-', LPAD(iteration + 1, 2, '0'));

            INSERT INTO purchases (
                invoice_no, vendor_id, purchase_date, subtotal, grand_total,
                paid_amount, remaining_amount, payment_method_id, notes, created_by
            )
            VALUES (
                invoice_number, vendor_id, entry_date, item_total, item_total,
                ROUND(item_total * 0.65, 2), item_total - ROUND(item_total * 0.65, 2),
                payment_method_id, CONCAT(demo_tag, ' | sample dashboard transaction'), user_id
            );
            SET purchase_id = LAST_INSERT_ID();

            INSERT INTO purchase_items (purchase_id, product_variant_id, quantity, rate, total)
            VALUES (purchase_id, variant_id, item_quantity, item_rate, item_total);

            UPDATE product_variants
            SET current_stock = current_stock + item_quantity
            WHERE id = variant_id;

            SELECT current_stock INTO stock_balance
            FROM product_variants
            WHERE id = variant_id;

            INSERT INTO stock_movements (
                product_variant_id, movement_type, reference_id, reference_no,
                quantity_in, quantity_out, balance_stock, rate, movement_date,
                created_by, notes
            )
            VALUES (
                variant_id, 'purchase', purchase_id, invoice_number,
                item_quantity, 0, stock_balance, item_rate,
                TIMESTAMP(entry_date, '10:00:00'), user_id,
                CONCAT(demo_tag, ' | sample dashboard transaction')
            );
        END IF;

        SET iteration = iteration + 1;
    END WHILE;

    IF NOT EXISTS (
        SELECT 1
        FROM purchases
        WHERE invoice_no = 'DEMO-PUR-LOSS-20261024'
    ) THEN
        SELECT id INTO vendor_id
        FROM vendors
        WHERE notes = demo_tag
        ORDER BY id
        LIMIT 1;

        SELECT id, purchase_rate
        INTO variant_id, item_rate
        FROM product_variants
        WHERE sku = 'DEMO-PF-20261024-THERMAL-BOX'
        LIMIT 1;

        SET item_quantity = 250;
        SET item_total = ROUND(item_quantity * item_rate, 2);
        SET invoice_number = 'DEMO-PUR-LOSS-20261024';

        INSERT INTO purchases (
            invoice_no, vendor_id, purchase_date, subtotal, grand_total,
            paid_amount, remaining_amount, payment_method_id, notes, created_by
        )
        VALUES (
            invoice_number, vendor_id, CURDATE(), item_total, item_total,
            ROUND(item_total * 0.65, 2), item_total - ROUND(item_total * 0.65, 2),
            payment_method_id, CONCAT(demo_tag, ' | sample monthly loss transaction'), user_id
        );
        SET purchase_id = LAST_INSERT_ID();

        INSERT INTO purchase_items (purchase_id, product_variant_id, quantity, rate, total)
        VALUES (purchase_id, variant_id, item_quantity, item_rate, item_total);

        UPDATE product_variants
        SET current_stock = current_stock + item_quantity
        WHERE id = variant_id;

        SELECT current_stock INTO stock_balance
        FROM product_variants
        WHERE id = variant_id;

        INSERT INTO stock_movements (
            product_variant_id, movement_type, reference_id, reference_no,
            quantity_in, quantity_out, balance_stock, rate, movement_date,
            created_by, notes
        )
        VALUES (
            variant_id, 'purchase', purchase_id, invoice_number,
            item_quantity, 0, stock_balance, item_rate, NOW(),
            user_id, CONCAT(demo_tag, ' | sample monthly loss transaction')
        );
    END IF;

    COMMIT;
    SELECT 'PaperFlow demo data, including a worker sale and daily loss, added successfully.' AS status;
END$$

CALL seed_paperflow_demo_data()$$
DROP PROCEDURE seed_paperflow_demo_data$$

DELIMITER ;
