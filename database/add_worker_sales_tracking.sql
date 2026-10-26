USE paper_management;

DROP PROCEDURE IF EXISTS add_worker_sales_tracking;

DELIMITER $$

CREATE PROCEDURE add_worker_sales_tracking()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'sales'
          AND COLUMN_NAME = 'worker_id'
    ) THEN
        ALTER TABLE sales
            ADD COLUMN worker_id INT NULL AFTER customer_id,
            ADD INDEX idx_sales_worker_id (worker_id),
            ADD CONSTRAINT fk_sales_worker
                FOREIGN KEY (worker_id)
                REFERENCES workers(id)
                ON DELETE SET NULL;
    END IF;
END$$

CALL add_worker_sales_tracking()$$
DROP PROCEDURE add_worker_sales_tracking$$

DELIMITER ;
