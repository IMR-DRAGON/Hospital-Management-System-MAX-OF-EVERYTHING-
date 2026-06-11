# HMS Associate System Documentation

## Overview

The HMS Associate System introduces a new role of **Associates** who serve as bridges between doctors and patients in the Hospital Management System. Associates are specialized healthcare professionals who coordinate patient care, facilitate communication, and manage patient-doctor interactions.

## System Architecture

### Core Tables

#### 1. `tbl_associate`
Main table storing associate information and credentials.

**Key Attributes:**
- `employee_id`: Unique identifier for the associate
- `first_name`, `last_name`: Associate's full name
- `email`, `phone`: Contact information
- `qualification`: Educational background
- `specialization`: Area of expertise
- `experience_years`: Years of experience
- `employee_ref_id`: Foreign key to `tbl_employee`

#### 2. `tbl_associate_doctor_relationship`
Manages relationships between associates and doctors.

**Key Attributes:**
- `associate_id`: Reference to associate
- `doctor_id`: Reference to doctor
- `department_id`: Department context
- `relationship_type`: Primary/Secondary/Backup
- `start_date`, `end_date`: Relationship duration

#### 3. `tbl_associate_patient_interaction`
Tracks all interactions between associates and patients.

**Key Attributes:**
- `interaction_id`: Unique interaction identifier
- `associate_id`: Associate involved
- `patient_id`: Patient involved
- `doctor_id`: Related doctor (if applicable)
- `interaction_type`: Type of interaction
- `interaction_date`: When interaction occurred
- `summary`: Details of the interaction

#### 4. `tbl_associate_workload`
Manages daily workload and capacity for associates.

**Key Attributes:**
- `associate_id`: Associate reference
- `date`: Specific date
- `max_patients_per_day`: Capacity limit
- `current_patient_count`: Current load
- `available_slots`: Remaining capacity

#### 5. `tbl_associate_permissions`
Controls access permissions for associates.

**Key Attributes:**
- `associate_id`: Associate reference
- `permission_type`: Type of permission
- `permission_level`: Read/Write/Full access
- `department_scope`: Department restrictions

## Setup Instructions

### 1. Database Setup

Execute the table creation script:
```bash
mysql -u root -p hms_db < create_associate_tables.sql
```

### 2. Sample Data (Optional)

Load sample data for testing:
```bash
mysql -u root -p hms_db < sample_associate_data.sql
```

### 3. Role Configuration

The system automatically adds role 3 for Associates in the `tbl_role` table.

## Usage Examples

### Creating a New Associate

```sql
-- Step 1: Create employee record
INSERT INTO tbl_employee (
    first_name, last_name, username, emailid, password, 
    dob, employee_id, joining_date, gender, address, 
    phone, bio, role, status
) VALUES (
    'Jane', 'Smith', 'jane.smith', 'jane.smith@hms.com', 'password123',
    '1985-05-20', 'EMP007', '2024-04-01', 'Female', '123 Healthcare St',
    '555-0105', 'Experienced healthcare coordinator', '3', '1'
);

-- Step 2: Create associate record
INSERT INTO tbl_associate (
    employee_id, first_name, last_name, email, phone, address,
    dob, joining_date, qualification, specialization, experience_years,
    bio, employee_ref_id, status
) VALUES (
    'ASS005', 'Jane', 'Smith', 'jane.smith@hms.com', '555-0105',
    '123 Healthcare St', '1985-05-20', '2024-04-01',
    'Master of Healthcare Administration', 'Patient Coordination', 6,
    'Experienced healthcare coordinator specializing in patient care',
    (SELECT id FROM tbl_employee WHERE employee_id = 'EMP007'), '1'
);
```

### Assigning Associate to Doctor

```sql
INSERT INTO tbl_associate_doctor_relationship (
    associate_id, doctor_id, department_id, relationship_type,
    start_date, status, notes
) VALUES (
    (SELECT id FROM tbl_associate WHERE employee_id = 'ASS005'),
    (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'),
    (SELECT id FROM tbl_department WHERE department_name = 'Cardiology'),
    'Primary', '2024-04-01', '1', 'Primary associate for cardiology patients'
);
```

### Recording Patient Interaction

```sql
INSERT INTO tbl_associate_patient_interaction (
    interaction_id, associate_id, patient_id, doctor_id,
    interaction_type, interaction_date, duration_minutes,
    location, purpose, summary, status
) VALUES (
    'INT005',
    (SELECT id FROM tbl_associate WHERE employee_id = 'ASS005'),
    (SELECT id FROM tbl_patient WHERE first_name = 'John' AND last_name = 'Doe'),
    (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'),
    'Initial_Contact', '2024-04-05 10:00:00', 30,
    'Clinic', 'Initial patient consultation',
    'Discussed patient concerns and scheduled follow-up',
    'Completed'
);
```

### Setting Up Daily Workload

```sql
INSERT INTO tbl_associate_workload (
    associate_id, date, max_patients_per_day, current_patient_count,
    available_slots, working_hours_start, working_hours_end
) VALUES (
    (SELECT id FROM tbl_associate WHERE employee_id = 'ASS005'),
    '2024-04-05', 20, 3, 17, '09:00:00', '17:00:00'
);
```

## Common Queries

### Get Associate Summary
```sql
SELECT 
    employee_id,
    CONCAT(first_name, ' ', last_name) as full_name,
    specialization,
    experience_years,
    status
FROM tbl_associate 
WHERE status = '1'
ORDER BY employee_id;
```

### Find Associates for a Specific Doctor
```sql
SELECT 
    a.employee_id,
    CONCAT(a.first_name, ' ', a.last_name) as associate_name,
    adr.relationship_type,
    d.department_name
FROM tbl_associate a
JOIN tbl_associate_doctor_relationship adr ON a.id = adr.associate_id
JOIN tbl_employee e ON adr.doctor_id = e.id
LEFT JOIN tbl_department d ON adr.department_id = d.id
WHERE e.employee_id = 'DOC001' AND adr.status = '1'
ORDER BY adr.relationship_type;
```

### Get Patient Interactions for Associate
```sql
SELECT 
    api.interaction_id,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    api.interaction_type,
    api.interaction_date,
    api.summary
FROM tbl_associate_patient_interaction api
JOIN tbl_patient p ON api.patient_id = p.id
JOIN tbl_associate a ON api.associate_id = a.id
WHERE a.employee_id = 'ASS001'
ORDER BY api.interaction_date DESC;
```

### Check Associate Workload
```sql
SELECT 
    CONCAT(a.first_name, ' ', a.last_name) as associate_name,
    aw.date,
    aw.max_patients_per_day,
    aw.current_patient_count,
    aw.available_slots,
    CASE 
        WHEN aw.available_slots <= 0 THEN 'Overbooked'
        WHEN aw.available_slots <= 3 THEN 'Near Capacity'
        ELSE 'Available'
    END as capacity_status
FROM tbl_associate_workload aw
JOIN tbl_associate a ON aw.associate_id = a.id
WHERE aw.date >= CURDATE()
ORDER BY aw.date, a.employee_id;
```

### Get Associate Permissions
```sql
SELECT 
    CONCAT(a.first_name, ' ', a.last_name) as associate_name,
    ap.permission_type,
    ap.permission_level,
    ap.department_scope,
    ap.granted_date
FROM tbl_associate_permissions ap
JOIN tbl_associate a ON ap.associate_id = a.id
WHERE ap.status = '1'
ORDER BY a.employee_id, ap.permission_type;
```

## Views Available

### 1. `view_associate_summary`
Provides comprehensive associate information including:
- Basic associate details
- Number of assigned doctors
- Total patients served
- Recent patient interactions

### 2. `view_associate_doctor_active`
Shows active associate-doctor relationships with:
- Associate and doctor names
- Department information
- Relationship type and dates

### 3. `view_associate_daily_workload`
Displays daily workload information with:
- Associate capacity
- Current patient load
- Available slots
- Capacity status

## Business Rules

### 1. Associate Creation
- Must have corresponding employee record with role = 3
- Employee ID must be unique
- Email must be unique across the system

### 2. Doctor Relationships
- Associate can have multiple doctor relationships
- Relationships can be Primary, Secondary, or Backup
- Only one Primary relationship per department per associate

### 3. Patient Interactions
- Each interaction must have unique interaction ID
- Interaction types are predefined
- Follow-up interactions can be scheduled

### 4. Workload Management
- Daily workload is tracked per associate
- Automatic capacity calculation based on interactions
- Triggers update workload when interactions are added/removed

### 5. Permissions
- Permissions are department-scoped
- Can be granted by admin users only
- Support expiration dates for temporary access

## Integration Points

### With Existing HMS Tables
- **tbl_employee**: Associates are employees with role = 3
- **tbl_patient**: Associates interact with patients
- **tbl_employee** (doctors): Associates work with doctors
- **tbl_department**: Associates are assigned to departments
- **tbl_appointment**: Interactions can be linked to appointments

### API Endpoints (Future Implementation)
- `POST /api/associates` - Create new associate
- `GET /api/associates` - List all associates
- `GET /api/associates/{id}/patients` - Get associate's patients
- `POST /api/associates/{id}/interactions` - Record interaction
- `GET /api/associates/{id}/workload` - Get workload information

## Security Considerations

1. **Data Access**: Associates can only access patients they're assigned to
2. **Permission Control**: Granular permissions based on department and function
3. **Audit Trail**: All interactions are logged with timestamps
4. **Data Integrity**: Foreign key constraints ensure data consistency

## Performance Optimization

1. **Indexes**: Strategic indexes on frequently queried columns
2. **Views**: Pre-computed views for common queries
3. **Triggers**: Automatic workload updates
4. **Partitioning**: Consider partitioning by date for interaction tables

## Maintenance

### Regular Tasks
1. Clean up expired permissions
2. Archive old interactions (consider retention policy)
3. Update workload calculations
4. Review associate-doctor relationships

### Monitoring
1. Track associate performance metrics
2. Monitor workload distribution
3. Review patient satisfaction scores
4. Analyze interaction patterns

## Troubleshooting

### Common Issues

1. **Duplicate Employee ID**: Ensure unique employee IDs across the system
2. **Missing Permissions**: Verify associate has necessary permissions for operations
3. **Workload Not Updating**: Check triggers are enabled
4. **Relationship Conflicts**: Ensure only one Primary relationship per department

### Debug Queries

```sql
-- Check for orphaned records
SELECT COUNT(*) FROM tbl_associate a 
LEFT JOIN tbl_employee e ON a.employee_ref_id = e.id 
WHERE e.id IS NULL;

-- Verify workload calculations
SELECT 
    associate_id, 
    COUNT(*) as interaction_count,
    MAX(current_patient_count) as max_count
FROM tbl_associate_patient_interaction api
JOIN tbl_associate_workload aw ON api.associate_id = aw.associate_id 
    AND DATE(api.interaction_date) = aw.date
GROUP BY associate_id;
```

## Future Enhancements

1. **Mobile App Integration**: Associate mobile interface
2. **Real-time Notifications**: Instant updates for critical interactions
3. **Analytics Dashboard**: Performance metrics and insights
4. **AI Integration**: Smart patient matching and workload optimization
5. **Video Consultation**: Built-in video calling for remote interactions

---

This associate system provides a robust foundation for managing healthcare associates who bridge the gap between doctors and patients, improving care coordination and patient experience.
