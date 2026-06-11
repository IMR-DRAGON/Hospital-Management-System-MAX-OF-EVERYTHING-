-- Hospital Inventory Management System Database Tables
-- Simplified version without foreign key constraints

-- 1. Inventory Categories Table
CREATE TABLE IF NOT EXISTS inventory_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Inventory Items Table
CREATE TABLE IF NOT EXISTS inventory_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    unit_of_measure VARCHAR(20) DEFAULT 'pieces',
    current_stock INT DEFAULT 0,
    minimum_stock_level INT DEFAULT 0,
    reorder_level INT DEFAULT 0,
    maximum_stock_level INT DEFAULT 0,
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    selling_price DECIMAL(10,2) DEFAULT 0.00,
    is_critical BOOLEAN DEFAULT FALSE,
    is_perishable BOOLEAN DEFAULT FALSE,
    shelf_life_days INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Inventory Batches Table (for tracking expiry dates)
CREATE TABLE IF NOT EXISTS inventory_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    batch_number VARCHAR(50) NOT NULL,
    quantity_received INT NOT NULL,
    quantity_remaining INT NOT NULL,
    unit_cost DECIMAL(10,2),
    expiry_date DATE,
    supplier_name VARCHAR(200),
    received_date DATE DEFAULT (CURRENT_DATE),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Stock Movements Table
CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    batch_id INT NULL,
    movement_type ENUM('Purchase', 'Sale', 'Transfer In', 'Transfer Out', 'Adjustment', 'Expired', 'Damaged') NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2),
    total_cost DECIMAL(10,2),
    reference_number VARCHAR(100),
    notes TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT
);

-- 5. Hospital Amenities Table
CREATE TABLE IF NOT EXISTS hospital_amenities (
    amenity_id INT AUTO_INCREMENT PRIMARY KEY,
    amenity_name VARCHAR(200) NOT NULL,
    amenity_type ENUM('Medical Equipment', 'Furniture', 'Electronics', 'Maintenance', 'Cleaning', 'Food Service', 'Other') NOT NULL,
    description TEXT,
    location VARCHAR(200),
    status ENUM('Active', 'Inactive', 'Under Maintenance', 'Disposed') DEFAULT 'Active',
    purchase_date DATE,
    warranty_expiry DATE,
    maintenance_schedule VARCHAR(100),
    is_critical BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Inventory Notifications Table
CREATE TABLE IF NOT EXISTS inventory_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    notification_type ENUM('Low Stock', 'Expiry Warning', 'Expired', 'Reorder Required', 'Critical Stock') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL
);

-- 7. Inventory Reports Table
CREATE TABLE IF NOT EXISTS inventory_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    report_name VARCHAR(200) NOT NULL,
    report_data JSON,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Supplier Information Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. Purchase Orders Table
CREATE TABLE IF NOT EXISTS purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('Draft', 'Pending', 'Approved', 'Received', 'Cancelled') DEFAULT 'Draft',
    order_date DATE DEFAULT (CURRENT_DATE),
    expected_delivery DATE,
    received_date DATE NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 10. Purchase Order Items Table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    poi_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT,
    item_id INT,
    quantity_ordered INT NOT NULL,
    unit_cost DECIMAL(10,2),
    total_cost DECIMAL(10,2),
    quantity_received INT DEFAULT 0
);

-- Insert default categories
INSERT IGNORE INTO inventory_categories (category_name, description) VALUES
('Medications', 'Prescription and over-the-counter medications'),
('Medical Supplies', 'Bandages, syringes, gloves, and other medical supplies'),
('Surgical Equipment', 'Surgical instruments and equipment'),
('Diagnostic Equipment', 'Medical diagnostic devices and equipment'),
('Furniture', 'Hospital furniture and fixtures'),
('Electronics', 'Electronic devices and equipment'),
('Cleaning Supplies', 'Cleaning and sanitization products'),
('Food Service', 'Food and beverage items'),
('Maintenance', 'Maintenance tools and supplies'),
('Emergency Equipment', 'Emergency and safety equipment');

-- Insert sample suppliers
INSERT IGNORE INTO suppliers (supplier_name, contact_person, email, phone, address) VALUES
('MedSupply Co.', 'John Smith', 'john@medsupply.com', '555-0101', '123 Medical Ave, City'),
('HealthTech Solutions', 'Sarah Johnson', 'sarah@healthtech.com', '555-0102', '456 Tech Street, City'),
('Global Medical', 'Mike Wilson', 'mike@globalmedical.com', '555-0103', '789 Global Blvd, City');

-- Create indexes for better performance
CREATE INDEX idx_inventory_items_category ON inventory_items(category_id);
CREATE INDEX idx_inventory_items_active ON inventory_items(is_active);
CREATE INDEX idx_inventory_batches_item ON inventory_batches(item_id);
CREATE INDEX idx_inventory_batches_expiry ON inventory_batches(expiry_date);
CREATE INDEX idx_stock_movements_item ON stock_movements(item_id);
CREATE INDEX idx_stock_movements_date ON stock_movements(movement_date);
CREATE INDEX idx_notifications_item ON inventory_notifications(item_id);
CREATE INDEX idx_notifications_read ON inventory_notifications(is_read);
