-- Blood Bank Management System - SQL Queries
-- Database: hms_db

-- =============================================
-- BLOOD DONOR MANAGEMENT QUERIES
-- =============================================

-- 1. Add a new blood donor
INSERT INTO blood_donors (donor_name, donor_email, donor_phone, donor_address, donor_age, donor_gender, blood_type, donor_weight, medical_conditions, emergency_contact_name, emergency_contact_phone)
VALUES ('John Doe', 'john.doe@email.com', '+1234567890', '123 Main St, City', 28, 'Male', 'O+', 70.5, 'None', 'Jane Doe', '+1234567891');

-- 2. Get all active donors
SELECT donor_id, donor_name, donor_phone, blood_type, last_donation_date, is_eligible, status
FROM blood_donors 
WHERE status = 'Active' 
ORDER BY donor_name;

-- 3. Get donors by blood type
SELECT donor_id, donor_name, donor_phone, donor_email, last_donation_date, is_eligible
FROM blood_donors 
WHERE blood_type = 'O+' AND status = 'Active' AND is_eligible = TRUE
ORDER BY last_donation_date ASC;

-- 4. Update donor eligibility
UPDATE blood_donors 
SET is_eligible = FALSE, status = 'Suspended'
WHERE donor_id = 1;

-- 5. Get donors eligible for donation (not donated in last 56 days)
SELECT donor_id, donor_name, donor_phone, blood_type, last_donation_date
FROM blood_donors 
WHERE status = 'Active' 
  AND is_eligible = TRUE 
  AND (last_donation_date IS NULL OR last_donation_date <= DATE_SUB(CURDATE(), INTERVAL 56 DAY))
ORDER BY last_donation_date ASC;

-- =============================================
-- BLOOD INVENTORY MANAGEMENT QUERIES
-- =============================================

-- 6. Add new blood unit to inventory
INSERT INTO blood_inventory (blood_type, blood_group, collection_date, expiry_date, donor_id, unit_volume, storage_location, temperature, blood_pressure_systolic, blood_pressure_diastolic, hemoglobin_level, blood_sugar_level)
VALUES ('O+', 'O+', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 42 DAY), 1, 1.0, 'Refrigerator A-1', 4.0, 120, 80, 14.5, 90);

-- 7. Get available blood units by type
SELECT inventory_id, blood_type, collection_date, expiry_date, unit_volume, storage_location
FROM blood_inventory 
WHERE blood_type = 'O+' AND status = 'Available' AND expiry_date > CURDATE()
ORDER BY expiry_date ASC;

-- 8. Get blood inventory summary by blood type
SELECT blood_type, 
       COUNT(*) as total_units,
       SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_units,
       SUM(CASE WHEN status = 'Reserved' THEN 1 ELSE 0 END) as reserved_units,
       SUM(CASE WHEN status = 'Transfused' THEN 1 ELSE 0 END) as transfused_units,
       SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) as expired_units
FROM blood_inventory 
GROUP BY blood_type
ORDER BY blood_type;

-- 9. Get expiring blood units (within 7 days)
SELECT inventory_id, blood_type, collection_date, expiry_date, storage_location
FROM blood_inventory 
WHERE status = 'Available' 
  AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY expiry_date ASC;

-- 10. Reserve blood units for a patient
UPDATE blood_inventory 
SET status = 'Reserved', 
    reserved_for_patient_id = 123, 
    reserved_date = NOW()
WHERE inventory_id IN (1, 2, 3) AND status = 'Available';

-- 11. Mark blood units as expired
UPDATE blood_inventory 
SET status = 'Expired'
WHERE expiry_date < CURDATE() AND status = 'Available';

-- =============================================
-- BLOOD REQUEST MANAGEMENT QUERIES
-- =============================================

-- 12. Create a new blood request
INSERT INTO blood_requests (patient_id, patient_name, patient_blood_type, required_blood_type, units_required, urgency_level, doctor_name, department, request_reason, required_date, created_by)
VALUES (123, 'Patient Name', 'A+', 'A+', 2, 'High', 'Dr. Smith', 'Emergency', 'Emergency surgery', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'admin');

-- 13. Get all pending blood requests
SELECT request_id, patient_name, required_blood_type, units_required, urgency_level, doctor_name, request_date, required_date
FROM blood_requests 
WHERE status = 'Pending'
ORDER BY urgency_level DESC, request_date ASC;

-- 14. Get blood requests by urgency
SELECT request_id, patient_name, required_blood_type, units_required, doctor_name, request_date
FROM blood_requests 
WHERE status = 'Pending' AND urgency_level = 'Critical'
ORDER BY request_date ASC;

-- 15. Approve a blood request
UPDATE blood_requests 
SET status = 'Approved', 
    approved_by = 'admin', 
    approved_date = NOW()
WHERE request_id = 1;

-- 16. Fulfill a blood request
UPDATE blood_requests 
SET status = 'Fulfilled', 
    fulfilled_date = NOW()
WHERE request_id = 1;

-- =============================================
-- BLOOD TRANSFUSION QUERIES
-- =============================================

-- 17. Record a blood transfusion
INSERT INTO blood_transfusions (patient_id, patient_name, inventory_id, blood_type, units_transfused, doctor_name, nurse_name, pre_transfusion_vitals, post_transfusion_vitals, created_by)
VALUES (123, 'Patient Name', 1, 'O+', 1.0, 'Dr. Smith', 'Nurse Johnson', 'BP: 120/80, HR: 72', 'BP: 125/82, HR: 75', 'admin');

-- 18. Get transfusion history for a patient
SELECT transfusion_id, blood_type, units_transfused, transfusion_date, doctor_name, status
FROM blood_transfusions 
WHERE patient_id = 123
ORDER BY transfusion_date DESC;

-- 19. Get all transfusions by date range
SELECT t.transfusion_id, t.patient_name, t.blood_type, t.units_transfused, t.transfusion_date, t.doctor_name, i.collection_date
FROM blood_transfusions t
JOIN blood_inventory i ON t.inventory_id = i.inventory_id
WHERE t.transfusion_date BETWEEN '2024-01-01' AND '2024-12-31'
ORDER BY t.transfusion_date DESC;

-- =============================================
-- BLOOD TEST MANAGEMENT QUERIES
-- =============================================

-- 20. Add blood test results
INSERT INTO blood_test_results (inventory_id, donor_id, hiv_result, hepatitis_b_result, hepatitis_c_result, syphilis_result, malaria_result, blood_group_confirmation, overall_result, tested_by)
VALUES (1, 1, 'Negative', 'Negative', 'Negative', 'Negative', 'Negative', 'O+', 'Suitable', 'Lab Tech');

-- 21. Get blood units ready for use (tested and suitable)
SELECT i.inventory_id, i.blood_type, i.collection_date, i.expiry_date, t.overall_result, t.test_date
FROM blood_inventory i
JOIN blood_test_results t ON i.inventory_id = t.inventory_id
WHERE t.overall_result = 'Suitable' AND i.status = 'Available' AND i.expiry_date > CURDATE()
ORDER BY i.expiry_date ASC;

-- 22. Get blood units that failed testing
SELECT i.inventory_id, i.blood_type, i.collection_date, t.overall_result, t.hiv_result, t.hepatitis_b_result, t.hepatitis_c_result, t.syphilis_result, t.malaria_result
FROM blood_inventory i
JOIN blood_test_results t ON i.inventory_id = t.inventory_id
WHERE t.overall_result = 'Unsuitable'
ORDER BY t.test_date DESC;

-- =============================================
-- REPORTING QUERIES
-- =============================================

-- 23. Blood bank inventory report
SELECT 
    blood_type,
    COUNT(*) as total_units,
    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'Reserved' THEN 1 ELSE 0 END) as reserved,
    SUM(CASE WHEN status = 'Transfused' THEN 1 ELSE 0 END) as transfused,
    SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN status = 'Discarded' THEN 1 ELSE 0 END) as discarded
FROM blood_inventory 
GROUP BY blood_type
ORDER BY blood_type;

-- 24. Monthly blood collection report
SELECT 
    DATE_FORMAT(collection_date, '%Y-%m') as month,
    blood_type,
    COUNT(*) as units_collected,
    SUM(unit_volume) as total_volume
FROM blood_inventory 
WHERE collection_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(collection_date, '%Y-%m'), blood_type
ORDER BY month DESC, blood_type;

-- 25. Donor statistics report
SELECT 
    blood_type,
    COUNT(*) as total_donors,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_donors,
    SUM(CASE WHEN is_eligible = TRUE THEN 1 ELSE 0 END) as eligible_donors,
    AVG(donor_age) as avg_age
FROM blood_donors 
GROUP BY blood_type
ORDER BY blood_type;

-- 26. Blood usage report by department
SELECT 
    br.department,
    br.required_blood_type,
    SUM(br.units_required) as total_units_requested,
    COUNT(*) as total_requests,
    SUM(CASE WHEN br.status = 'Fulfilled' THEN br.units_required ELSE 0 END) as units_fulfilled
FROM blood_requests br
WHERE br.request_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
GROUP BY br.department, br.required_blood_type
ORDER BY br.department, br.required_blood_type;

-- 27. Critical blood shortage alert
SELECT blood_type, COUNT(*) as available_units
FROM blood_inventory 
WHERE status = 'Available' AND expiry_date > CURDATE()
GROUP BY blood_type
HAVING COUNT(*) < 5
ORDER BY available_units ASC;

-- 28. Expired blood units report
SELECT 
    blood_type,
    COUNT(*) as expired_units,
    SUM(unit_volume) as total_volume_expired,
    AVG(DATEDIFF(expiry_date, collection_date)) as avg_shelf_life_days
FROM blood_inventory 
WHERE status = 'Expired'
GROUP BY blood_type
ORDER BY expired_units DESC;

-- =============================================
-- SEARCH AND FILTER QUERIES
-- =============================================

-- 29. Search donors by name or phone
SELECT donor_id, donor_name, donor_phone, blood_type, last_donation_date, is_eligible
FROM blood_donors 
WHERE (donor_name LIKE '%John%' OR donor_phone LIKE '%123%') 
  AND status = 'Active'
ORDER BY donor_name;

-- 30. Find compatible blood types for a patient
-- For a patient with blood type A+, compatible types are A+, A-, O+, O-
SELECT DISTINCT blood_type, COUNT(*) as available_units
FROM blood_inventory 
WHERE blood_type IN ('A+', 'A-', 'O+', 'O-') 
  AND status = 'Available' 
  AND expiry_date > CURDATE()
GROUP BY blood_type
ORDER BY available_units DESC;

-- 31. Get blood bank staff on duty
SELECT staff_id, staff_name, designation, shift_timing
FROM blood_bank_staff 
WHERE is_active = TRUE
ORDER BY designation, staff_name;

-- =============================================
-- MAINTENANCE QUERIES
-- =============================================

-- 32. Clean up old expired blood records (older than 1 year)
DELETE FROM blood_inventory 
WHERE status = 'Expired' 
  AND expiry_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);

-- 33. Update donor eligibility based on last donation
UPDATE blood_donors 
SET is_eligible = TRUE
WHERE last_donation_date <= DATE_SUB(CURDATE(), INTERVAL 56 DAY) 
  AND status = 'Active';

-- 34. Archive completed blood requests (older than 2 years)
UPDATE blood_requests 
SET status = 'Archived'
WHERE status = 'Fulfilled' 
  AND fulfilled_date < DATE_SUB(CURDATE(), INTERVAL 2 YEAR);
