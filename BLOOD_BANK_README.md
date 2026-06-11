# Blood Bank (Simplified)

A minimal blood management module integrated with HMS. Scope limited to donors, inventory, and patient blood requests.

## 🩸 Features

- Donor Management: register donors, track last donation
- Blood Inventory: add units from donations, monitor expiry, mark used
- Blood Requests: patients request units; approve/fulfill/cancel

### Key Capabilities
- Availability by blood type
- Expiry monitoring and auto-expire
- Simple shortage check

## 📊 Database Schema

### Tables Created

#### 1. `donors`
Essential donor info: name, contact, blood type, age, last donation date, status

#### 2. `blood_inventory`
Each unit: donor_id, blood_type, collection_date, expiry_date, status, request_id

#### 3. `blood_requests`
Patient name, required blood type, units_required, reason, status

## 🚀 Installation & Setup

### 1. Database Setup
```sql
-- Run the SQL file to create all tables
mysql -u root -p hms_db < blood_bank_tables.sql
```

### 2. File Integration
Copy the following PHP files to your HMS root directory:
- `blood-bank.php` - Dashboard
- `add-donor.php` - Add donors
- `add-blood-unit.php` - Add blood units
- `blood-inventory.php` - Manage inventory
- `blood-requests.php` - Manage requests

### 3. Navigation Integration
Add the following link to your main navigation menu:
```php
<a href="blood-bank.php" class="nav-link">
    <i class="fa fa-tint"></i> Blood Bank
</a>
```

## 📋 Key SQL Queries

### Donor Management
```sql
-- Add new donor
INSERT INTO blood_donors (donor_name, donor_email, donor_phone, blood_type, ...)
VALUES ('John Doe', 'john@email.com', '+1234567890', 'O+', ...);

-- Get eligible donors
SELECT * FROM blood_donors 
WHERE status = 'Active' AND is_eligible = TRUE 
AND last_donation_date <= DATE_SUB(CURDATE(), INTERVAL 56 DAY);
```

### Inventory Management
```sql
-- Add blood unit
INSERT INTO blood_inventory (blood_type, collection_date, expiry_date, ...)
VALUES ('O+', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 42 DAY), ...);

-- Get available blood by type
SELECT * FROM blood_inventory 
WHERE blood_type = 'O+' AND status = 'Available' 
AND expiry_date > CURDATE();
```

### Request Management
```sql
-- Create blood request
INSERT INTO blood_requests (patient_name, required_blood_type, units_required, ...)
VALUES ('Patient Name', 'A+', 2, ...);

-- Get critical requests
SELECT * FROM blood_requests 
WHERE urgency_level = 'Critical' AND status = 'Pending';
```

Basic reporting limited to availability per type and shortages.

## 🎯 Usage Guide

### 1. Adding a New Donor
1. Navigate to Blood Bank → Add Donor
2. Fill in all required donor information
3. System validates age (18-65) and weight (≥50kg)
4. Donor is automatically marked as eligible

### 2. Managing Blood Inventory
1. Go to Blood Bank → Blood Inventory
2. Add new blood units with collection details
3. Monitor expiry dates and availability
4. Reserve units for specific patients when needed

### 3. Processing Blood Requests
1. Navigate to Blood Bank → Blood Requests
2. Review pending requests by urgency level
3. Approve/reject requests with notes
4. Mark requests as fulfilled when completed

### 4. Generating Reports
Out of scope for simplified module.

## 🔧 Configuration

### Blood Shelf Life
Default shelf life is 42 days (see queries: add to `blood_inventory`).

### Critical Shortage Threshold
Default threshold is 5 units. To modify:
```sql
-- Update shortage query in blood-bank.php
HAVING COUNT(*) < 5  -- Change 5 to desired threshold
```

### Donor Eligibility Rules
- Minimum age: 18 years
- Maximum age: 65 years
- Minimum weight: 50 kg
- Donation interval: 56 days minimum

## 📈 Reports Available
Only availability summary and shortage checks.

## 🛡️ Security Features

- Input validation and sanitization
- SQL injection prevention
- User authentication required
- Role-based access control
- Data integrity constraints

## 🔄 Maintenance
- Mark expired units daily
- Review pending requests
- Update donor last donation dates

## 🚨 Alerts & Notifications
Not included.

## 📞 Support

For technical support or feature requests, please contact the system administrator or refer to the main HMS documentation.

---

**Note**: This blood bank management system is designed to integrate seamlessly with your existing HMS. Ensure all database connections and user authentication are properly configured before deployment.
