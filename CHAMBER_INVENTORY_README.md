# Chamber Inventory (Simplified)

A minimal inventory module for chamber supplies focused on adding/removing items and keeping stock records.

## Overview
- Single inventory list for chamber items
- Track item name, description, quantity, and last updated
- Operations: view items, add new item, increase stock, decrease stock, delete item

## Database Schema
- `inventory`
  - `item_id` INT PK
  - `item_name` VARCHAR UNIQUE
  - `description` TEXT
  - `quantity` INT (stock on hand)
  - `last_updated` TIMESTAMP (auto-updated)

Create tables:
```sql
mysql -u root -p hms_db < chamber_inventory_tables.sql
```

## Key Queries
```sql
-- View all items
SELECT item_id, item_name, description, quantity, last_updated
FROM inventory
ORDER BY item_name ASC;

-- Add a new item type
INSERT INTO inventory (item_name, description, quantity)
VALUES ('IV Saline Solution (1000ml)', '1000ml bags of normal saline solution for IV use.', 30);

-- Increase stock for existing item
UPDATE inventory
SET quantity = quantity + 10
WHERE item_id = 1;

-- Decrease stock for existing item (non-negative)
UPDATE inventory
SET quantity = quantity - 5
WHERE item_id = 2 AND quantity >= 5;

-- Delete an item type
DELETE FROM inventory
WHERE item_id = 3;
```

## Usage
- Use "Add Item" to create a new inventory record
- Use "Add Stock" when receiving items; "Remove Stock" when items are used
- Keep quantities non-negative by guarding updates (`quantity >= x`)

## Notes
- No suppliers, categories, chambers, or movement logs in simplified scope
- Extend later by adding a movement log table if needed (IN/OUT records)
