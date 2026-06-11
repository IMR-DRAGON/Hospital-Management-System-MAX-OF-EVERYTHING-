
-- =============================================
-- SIMPLE INVENTORY MANAGEMENT QUERIES
-- =============================================

-- 1. View All Items in the Inventory
-- Use this to display a list of all items and their current stock.
SELECT item_id, item_name, description, quantity, last_updated
FROM inventory
ORDER BY item_name ASC;


-- 2. Add a New Item Type to the Inventory
-- Use this query when you are adding a completely new product to your stock.
INSERT INTO inventory (item_name, description, quantity)
VALUES ('IV Saline Solution (1000ml)', '1000ml bags of normal saline solution for intravenous use.', 30);


-- 3. Add Stock to an Existing Item (INCREASE Quantity)
-- Run this query when you receive a new shipment of an existing item.
UPDATE inventory
SET quantity = quantity + 10  -- The number of items being added
WHERE item_id = 1;            -- The ID of the item you are restocking


-- 4. Remove Stock from an Existing Item (DECREASE Quantity)
-- Run this query when an item is used or removed from the chamber.
UPDATE inventory
SET quantity = quantity - 5   -- The number of items being removed
WHERE item_id = 2 AND quantity >= 5; -- The ID of the item and a check to prevent stock from going negative


-- 5. Delete an Item Type Completely
-- Use this only if you want to permanently remove an item from your inventory list.
DELETE FROM inventory
WHERE item_id = 3;
