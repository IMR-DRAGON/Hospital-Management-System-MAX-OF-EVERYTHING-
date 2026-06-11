-- Database: hms_db
-- A simple table to manage inventory items.

CREATE TABLE IF NOT EXISTS inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pre-populating with a few example items
INSERT INTO inventory (item_name, description, quantity) VALUES
('Sterile Gauze Pads (Box of 100)', '4x4 inch sterile gauze pads for wound dressing.', 50),
('Disposable Syringes (10ml)', 'Box of 100 10ml Luer Lock disposable syringes.', 75),
('Latex Examination Gloves (Medium)', 'Box of 100 medium-sized latex examination gloves.', 40),
('Digital Thermometer', 'Standard digital thermometer for temperature readings.', 25);

