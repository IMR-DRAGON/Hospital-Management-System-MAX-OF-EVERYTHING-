# Associate Management (Simplified)

A minimal associate module for HMS focusing only on coordination between patients and doctors.

## Overview
- Associate types limited to:
  - `Patient Coordinator`
  - `Doctor Coordinator`
- Responsibilities: basic coordination and communication; optional weekly availability
- Out of scope: assignments, shift schedules, tasks, performance reviews, training

## Database Schema
- `associates`: core profile and employment info, associate_type, capacity, basic status
- `associate_communications`: records text notes of interactions and follow-ups
- `associate_availability`: optional weekly availability (day-of-week, hours)

## Setup
1) Database
```sql
mysql -u root -p hms_db < associate_management_tables.sql
```
2) Files
- `associates.php` (list/manage associates)
- `add-associate.php` (create associates)
- `associate-communications.php` (optional communication notes)

## Key Queries
```sql
-- Add associate (patient/doctor coordinator)
INSERT INTO associates (employee_id, associate_code, first_name, last_name, associate_type, hire_date, created_by)
VALUES (1, 'ASC-001', 'John', 'Smith', 'Patient Coordinator', '2024-01-15', 'admin');

-- Active coordinators by type
SELECT associate_id, associate_code, first_name, last_name
FROM associates
WHERE status = 'Active' AND associate_type = 'Patient Coordinator';

-- Record a communication note
INSERT INTO associate_communications (associate_id, communication_type, related_patient_id, related_doctor_id, communication_method, subject, message, created_by)
VALUES (1, 'Patient Call', 10, 3, 'Phone', 'Follow-up', 'Called patient regarding appointment prep', 'admin');

-- Weekly availability (optional)
INSERT INTO associate_availability (associate_id, day_of_week, is_available, start_time, end_time, max_patients)
VALUES (1, 'Monday', TRUE, '09:00:00', '17:00:00', 15);
```

## Usage
- Create coordinators in `add-associate.php`
- View and manage basic details in `associates.php`
- Optionally log communications in `associate-communications.php`
- Optionally maintain weekly availability in `associate_availability`

## Notes
- Only `Patient Coordinator` and `Doctor Coordinator` types are supported
- Removed: `associate_assignments`, `associate_schedules`, `associate_tasks`, `associate_performance`, `associate_training`
- Keep the module simple; avoid adding extra workflows beyond coordination and availability
