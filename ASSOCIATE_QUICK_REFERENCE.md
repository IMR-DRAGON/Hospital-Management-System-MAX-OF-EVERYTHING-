# Associate System - Quick Reference Guide

## 🚀 Quick Setup

### 1. Create Tables
```bash
mysql -u root -p hms_db < create_associate_tables.sql
```

### 2. Load Sample Data
```bash
mysql -u root -p hms_db < sample_associate_data.sql
```

## 📋 Common Operations

### Add New Associate
```sql
-- 1. Add to tbl_employee (role=3)
INSERT INTO tbl_employee (first_name, last_name, username, emailid, password, dob, employee_id, joining_date, gender, address, phone, bio, role, status) 
VALUES ('Name', 'Last', 'username', 'email@hms.com', 'password', '1985-01-01', 'EMP999', '2024-01-01', 'Male', 'Address', '555-0000', 'Bio', '3', '1');

-- 2. Add to tbl_associate
INSERT INTO tbl_associate (employee_id, first_name, last_name, email, phone, address, dob, joining_date, qualification, specialization, experience_years, bio, employee_ref_id, status) 
VALUES ('ASS999', 'Name', 'Last', 'email@hms.com', '555-0000', 'Address', '1985-01-01', '2024-01-01', 'Qualification', 'Specialization', 5, 'Bio', 
(SELECT id FROM tbl_employee WHERE employee_id = 'EMP999'), '1');
```

### Assign Associate to Doctor
```sql
INSERT INTO tbl_associate_doctor_relationship (associate_id, doctor_id, department_id, relationship_type, start_date, status, notes) 
VALUES (
    (SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'),
    (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'),
    (SELECT id FROM tbl_department WHERE department_name = 'Cardiology'),
    'Primary', '2024-01-01', '1', 'Primary associate'
);
```

### Record Patient Interaction
```sql
INSERT INTO tbl_associate_patient_interaction (interaction_id, associate_id, patient_id, doctor_id, interaction_type, interaction_date, duration_minutes, location, purpose, summary, status) 
VALUES ('INT999', 
    (SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'),
    (SELECT id FROM tbl_patient WHERE first_name = 'Patient' AND last_name = 'Name'),
    (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'),
    'Initial_Contact', NOW(), 30, 'Clinic', 'Purpose', 'Summary', 'Completed');
```

## 🔍 Useful Queries

### List All Associates
```sql
SELECT employee_id, CONCAT(first_name, ' ', last_name) as name, specialization, status 
FROM tbl_associate ORDER BY employee_id;
```

### Find Associates for Doctor
```sql
SELECT a.employee_id, CONCAT(a.first_name, ' ', a.last_name) as associate_name, adr.relationship_type 
FROM tbl_associate a 
JOIN tbl_associate_doctor_relationship adr ON a.id = adr.associate_id 
JOIN tbl_employee e ON adr.doctor_id = e.id 
WHERE e.employee_id = 'DOC001' AND adr.status = '1';
```

### Check Associate Workload Today
```sql
SELECT a.employee_id, CONCAT(a.first_name, ' ', a.last_name) as name, 
       aw.current_patient_count, aw.available_slots 
FROM tbl_associate a 
JOIN tbl_associate_workload aw ON a.id = aw.associate_id 
WHERE aw.date = CURDATE() AND a.status = '1';
```

### Get Patient Interactions for Associate
```sql
SELECT api.interaction_date, CONCAT(p.first_name, ' ', p.last_name) as patient_name, 
       api.interaction_type, api.summary 
FROM tbl_associate_patient_interaction api 
JOIN tbl_patient p ON api.patient_id = p.id 
JOIN tbl_associate a ON api.associate_id = a.id 
WHERE a.employee_id = 'ASS001' 
ORDER BY api.interaction_date DESC;
```

## 🎯 Key Tables Summary

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `tbl_associate` | Associate information | employee_id, specialization, experience_years |
| `tbl_associate_doctor_relationship` | Associate-Doctor links | associate_id, doctor_id, relationship_type |
| `tbl_associate_patient_interaction` | Patient interactions | interaction_id, associate_id, patient_id, summary |
| `tbl_associate_workload` | Daily capacity | associate_id, date, max_patients_per_day, available_slots |
| `tbl_associate_permissions` | Access control | associate_id, permission_type, permission_level |

## 🔑 Important Fields

### Interaction Types
- `Initial_Contact` - First meeting with patient
- `Follow_up` - Subsequent check-ins
- `Patient_Education` - Teaching/explaining
- `Care_Coordination` - Managing care plan
- `Discharge_Planning` - Preparing for discharge

### Relationship Types
- `Primary` - Main associate for doctor/department
- `Secondary` - Backup associate
- `Backup` - Emergency coverage

### Permission Types
- `View_Patient_Records` - Read patient data
- `Update_Patient_Info` - Modify patient information
- `Schedule_Appointments` - Book appointments
- `Patient_Communication` - Contact patients
- `Emergency_Access` - Critical access rights

## ⚠️ Important Notes

1. **Employee Record Required**: Every associate must have a corresponding `tbl_employee` record with `role = 3`
2. **Unique IDs**: `employee_id` must be unique across the system
3. **Foreign Keys**: All relationships use proper foreign key constraints
4. **Status Fields**: Use `'1'` for active, `'0'` for inactive
5. **Date Format**: Use `YYYY-MM-DD` for dates, `YYYY-MM-DD HH:MM:SS` for datetime

## 🛠️ Maintenance Commands

### Update Associate Status
```sql
UPDATE tbl_associate SET status = '0' WHERE employee_id = 'ASS001';
```

### Update Workload
```sql
UPDATE tbl_associate_workload 
SET current_patient_count = 5, available_slots = 15 
WHERE associate_id = (SELECT id FROM tbl_associate WHERE employee_id = 'ASS001') 
AND date = CURDATE();
```

### Add Permission
```sql
INSERT INTO tbl_associate_permissions (associate_id, permission_type, permission_level, department_scope, granted_by, granted_date, status) 
VALUES (
    (SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'),
    'View_Patient_Records', 'Read', 'Cardiology',
    (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1),
    CURDATE(), '1'
);
```

## 📊 Sample Data Available

The system includes sample data for:
- 4 Associates (ASS001-ASS004)
- 3 Doctors (DOC001-DOC003)
- 5 Patients
- Sample relationships and interactions
- Workload data
- Permission examples

## 🔧 Troubleshooting

### Check Data Integrity
```sql
-- Orphaned associates
SELECT COUNT(*) FROM tbl_associate a 
LEFT JOIN tbl_employee e ON a.employee_ref_id = e.id 
WHERE e.id IS NULL;

-- Missing relationships
SELECT a.employee_id FROM tbl_associate a 
LEFT JOIN tbl_associate_doctor_relationship adr ON a.id = adr.associate_id 
WHERE adr.id IS NULL AND a.status = '1';
```

### Reset Sample Data
```bash
# Drop and recreate tables
mysql -u root -p hms_db < create_associate_tables.sql
mysql -u root -p hms_db < sample_associate_data.sql
```

---

**Need Help?** Check the full documentation in `ASSOCIATE_SYSTEM_README.md`
