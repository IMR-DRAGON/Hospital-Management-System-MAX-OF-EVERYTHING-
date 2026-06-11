-- =====================================================
-- Sample Data for HMS Associate System
-- Insert sample data for testing associate functionality
-- =====================================================

-- First, ensure we have some departments (if they don't exist)
INSERT IGNORE INTO `tbl_department` (`department_name`, `description`, `status`) VALUES
('Cardiology', 'Heart and cardiovascular system care', '1'),
('Neurology', 'Brain and nervous system disorders', '1'),
('Orthopedics', 'Bone, joint, and muscle care', '1'),
('Pediatrics', 'Medical care for infants, children, and adolescents', '1'),
('Emergency Medicine', 'Emergency and urgent care services', '1');

-- Create sample employee records for associates (role = 3)
INSERT IGNORE INTO `tbl_employee` (
    `first_name`, `last_name`, `username`, `emailid`, `password`, 
    `dob`, `employee_id`, `joining_date`, `gender`, `address`, 
    `phone`, `bio`, `role`, `status`
) VALUES
('Sarah', 'Johnson', 'sarah.johnson', 'sarah.johnson@hms.com', 'password123', 
 '1985-03-15', 'EMP003', '2024-01-15', 'Female', '123 Medical Center Dr, City, State', 
 '555-0101', 'Experienced healthcare associate specializing in patient coordination', '3', '1'),

('Michael', 'Chen', 'michael.chen', 'michael.chen@hms.com', 'password123', 
 '1988-07-22', 'EMP004', '2024-02-01', 'Male', '456 Healthcare Ave, City, State', 
 '555-0102', 'Dedicated associate focused on improving patient-doctor communication', '3', '1'),

('Emily', 'Rodriguez', 'emily.rodriguez', 'emily.rodriguez@hms.com', 'password123', 
 '1990-11-08', 'EMP005', '2024-02-15', 'Female', '789 Patient Care St, City, State', 
 '555-0103', 'Compassionate associate with strong background in healthcare administration', '3', '1'),

('David', 'Kim', 'david.kim', 'david.kim@hms.com', 'password123', 
 '1987-05-12', 'EMP006', '2024-03-01', 'Male', '321 Medical Plaza, City, State', 
 '555-0104', 'Skilled associate specializing in care coordination and patient advocacy', '3', '1');

-- Create sample associate records
INSERT IGNORE INTO `tbl_associate` (
    `employee_id`, `first_name`, `last_name`, `email`, `phone`, `address`, 
    `dob`, `joining_date`, `qualification`, `specialization`, `experience_years`, 
    `bio`, `employee_ref_id`, `status`
) VALUES
('ASS001', 'Sarah', 'Johnson', 'sarah.johnson@hms.com', '555-0101', 
 '123 Medical Center Dr, City, State', '1985-03-15', '2024-01-15', 
 'Bachelor of Healthcare Administration', 'Patient Care Coordination', 8, 
 'Experienced healthcare associate with expertise in patient-doctor communication and care coordination', 
 (SELECT id FROM tbl_employee WHERE employee_id = 'EMP003'), '1'),

('ASS002', 'Michael', 'Chen', 'michael.chen@hms.com', '555-0102', 
 '456 Healthcare Ave, City, State', '1988-07-22', '2024-02-01', 
 'Master of Public Health', 'Healthcare Management', 5, 
 'Dedicated associate focused on improving healthcare delivery and patient outcomes', 
 (SELECT id FROM tbl_employee WHERE employee_id = 'EMP004'), '1'),

('ASS003', 'Emily', 'Rodriguez', 'emily.rodriguez@hms.com', '555-0103', 
 '789 Patient Care St, City, State', '1990-11-08', '2024-02-15', 
 'Bachelor of Nursing', 'Patient Advocacy', 6, 
 'Compassionate associate with strong background in patient care and healthcare administration', 
 (SELECT id FROM tbl_employee WHERE employee_id = 'EMP005'), '1'),

('ASS004', 'David', 'Kim', 'david.kim@hms.com', '555-0104', 
 '321 Medical Plaza, City, State', '1987-05-12', '2024-03-01', 
 'Master of Healthcare Administration', 'Care Coordination', 7, 
 'Skilled associate specializing in care coordination, patient advocacy, and healthcare quality improvement', 
 (SELECT id FROM tbl_employee WHERE employee_id = 'EMP006'), '1');

-- Create sample doctor records (if they don't exist)
INSERT IGNORE INTO `tbl_employee` (
    `first_name`, `last_name`, `username`, `emailid`, `password`, 
    `dob`, `employee_id`, `joining_date`, `gender`, `address`, 
    `phone`, `bio`, `role`, `status`
) VALUES
('Dr. Robert', 'Smith', 'robert.smith', 'robert.smith@hms.com', 'password123', 
 '1975-04-20', 'DOC001', '2020-01-15', 'Male', '100 Cardiology Center, City, State', 
 '555-0201', 'Board-certified cardiologist with 15 years of experience', '2', '1'),

('Dr. Lisa', 'Wang', 'lisa.wang', 'lisa.wang@hms.com', 'password123', 
 '1980-09-10', 'DOC002', '2021-03-01', 'Female', '200 Neurology Center, City, State', 
 '555-0202', 'Specialized neurologist focusing on pediatric neurology', '2', '1'),

('Dr. James', 'Brown', 'james.brown', 'james.brown@hms.com', 'password123', 
 '1978-12-05', 'DOC003', '2019-06-15', 'Male', '300 Orthopedic Center, City, State', 
 '555-0203', 'Orthopedic surgeon specializing in sports medicine', '2', '1');

-- Create sample patient records (if they don't exist)
INSERT IGNORE INTO `tbl_patient` (
    `first_name`, `last_name`, `email`, `dob`, `gender`, 
    `patient_type`, `address`, `phone`, `status`
) VALUES
('John', 'Doe', 'john.doe@email.com', '1985-06-15', 'Male', 
 'OutPatient', '123 Main St, City, State', '555-1001', '1'),

('Jane', 'Smith', 'jane.smith@email.com', '1990-03-22', 'Female', 
 'InPatient', '456 Oak Ave, City, State', '555-1002', '1'),

('Mike', 'Johnson', 'mike.johnson@email.com', '1978-11-08', 'Male', 
 'OutPatient', '789 Pine St, City, State', '555-1003', '1'),

('Sarah', 'Wilson', 'sarah.wilson@email.com', '1992-07-14', 'Female', 
 'InPatient', '321 Elm Dr, City, State', '555-1004', '1'),

('David', 'Lee', 'david.lee@email.com', '1987-02-28', 'Male', 
 'OutPatient', '654 Maple Ave, City, State', '555-1005', '1');

-- Create associate-doctor relationships
INSERT IGNORE INTO `tbl_associate_doctor_relationship` (
    `associate_id`, `doctor_id`, `department_id`, `relationship_type`, 
    `start_date`, `status`, `notes`
) VALUES
-- Sarah Johnson works with Cardiology and Neurology doctors
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'), 
 (SELECT id FROM tbl_department WHERE department_name = 'Cardiology'), 
 'Primary', '2024-01-15', '1', 'Primary associate for cardiology patients'),

-- Michael Chen works with Neurology doctors
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS002'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC002'), 
 (SELECT id FROM tbl_department WHERE department_name = 'Neurology'), 
 'Primary', '2024-02-01', '1', 'Primary associate for neurology patients'),

-- Emily Rodriguez works with Orthopedics doctors
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS003'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC003'), 
 (SELECT id FROM tbl_department WHERE department_name = 'Orthopedics'), 
 'Primary', '2024-02-15', '1', 'Primary associate for orthopedic patients'),

-- David Kim as backup for multiple departments
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS004'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'), 
 (SELECT id FROM tbl_department WHERE department_name = 'Cardiology'), 
 'Secondary', '2024-03-01', '1', 'Backup associate for cardiology'),

((SELECT id FROM tbl_associate WHERE employee_id = 'ASS004'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC002'), 
 (SELECT id FROM tbl_department WHERE department_name = 'Neurology'), 
 'Secondary', '2024-03-01', '1', 'Backup associate for neurology');

-- Create sample patient interactions
INSERT IGNORE INTO `tbl_associate_patient_interaction` (
    `interaction_id`, `associate_id`, `patient_id`, `doctor_id`, 
    `interaction_type`, `interaction_date`, `duration_minutes`, 
    `location`, `purpose`, `summary`, `status`
) VALUES
-- Sarah Johnson's interactions
('INT001', (SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), 
 (SELECT id FROM tbl_patient WHERE first_name = 'John' AND last_name = 'Doe'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'), 
 'Initial_Contact', '2024-03-15 09:00:00', 30, 'Clinic', 
 'Initial patient consultation coordination', 
 'Discussed patient concerns and scheduled follow-up with cardiologist', 'Completed'),

('INT002', (SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), 
 (SELECT id FROM tbl_patient WHERE first_name = 'John' AND last_name = 'Doe'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC001'), 
 'Follow_up', '2024-03-20 10:30:00', 20, 'Phone', 
 'Follow-up on treatment plan', 
 'Confirmed patient understanding of medication and scheduled next appointment', 'Completed'),

-- Michael Chen's interactions
('INT003', (SELECT id FROM tbl_associate WHERE employee_id = 'ASS002'), 
 (SELECT id FROM tbl_patient WHERE first_name = 'Jane' AND last_name = 'Smith'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC002'), 
 'Patient_Education', '2024-03-18 14:00:00', 45, 'Clinic', 
 'Neurological condition education', 
 'Provided detailed information about neurological condition and treatment options', 'Completed'),

-- Emily Rodriguez's interactions
('INT004', (SELECT id FROM tbl_associate WHERE employee_id = 'ASS003'), 
 (SELECT id FROM tbl_patient WHERE first_name = 'Mike' AND last_name = 'Johnson'), 
 (SELECT id FROM tbl_employee WHERE employee_id = 'DOC003'), 
 'Care_Coordination', '2024-03-22 11:00:00', 25, 'Clinic', 
 'Post-surgery care coordination', 
 'Coordinated post-surgical care plan with patient and family', 'Completed');

-- Create sample workload data
INSERT IGNORE INTO `tbl_associate_workload` (
    `associate_id`, `date`, `max_patients_per_day`, `current_patient_count`, 
    `available_slots`, `working_hours_start`, `working_hours_end`
) VALUES
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), '2024-03-15', 20, 5, 15, '09:00:00', '17:00:00'),
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), '2024-03-16', 20, 8, 12, '09:00:00', '17:00:00'),
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS002'), '2024-03-18', 18, 6, 12, '08:30:00', '16:30:00'),
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS003'), '2024-03-22', 15, 4, 11, '09:00:00', '17:00:00'),
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS004'), '2024-03-25', 22, 10, 12, '08:00:00', '18:00:00');

-- Create sample permissions
INSERT IGNORE INTO `tbl_associate_permissions` (
    `associate_id`, `permission_type`, `permission_level`, `department_scope`, 
    `granted_by`, `granted_date`, `status`
) VALUES
-- Sarah Johnson's permissions
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), 'View_Patient_Records', 'Read', 'Cardiology', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-01-15', '1'),

((SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), 'Update_Patient_Info', 'Write', 'Cardiology', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-01-15', '1'),

((SELECT id FROM tbl_associate WHERE employee_id = 'ASS001'), 'Schedule_Appointments', 'Write', 'Cardiology', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-01-15', '1'),

-- Michael Chen's permissions
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS002'), 'View_Patient_Records', 'Read', 'Neurology', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-02-01', '1'),

((SELECT id FROM tbl_associate WHERE employee_id = 'ASS002'), 'Patient_Communication', 'Write', 'Neurology', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-02-01', '1'),

-- Emily Rodriguez's permissions
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS003'), 'View_Patient_Records', 'Read', 'Orthopedics', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-02-15', '1'),

((SELECT id FROM tbl_associate WHERE employee_id = 'ASS003'), 'Care_Coordination', 'Full', 'Orthopedics', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-02-15', '1'),

-- David Kim's permissions (broader scope)
((SELECT id FROM tbl_associate WHERE employee_id = 'ASS004'), 'View_Patient_Records', 'Read', 'All', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-03-01', '1'),

((SELECT id FROM tbl_associate WHERE employee_id = 'ASS004'), 'Emergency_Access', 'Full', 'All', 
 (SELECT id FROM tbl_employee WHERE role = 1 LIMIT 1), '2024-03-01', '1');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Query to verify associate setup
SELECT 'Associate Records Created:' as info, COUNT(*) as count FROM tbl_associate;

-- Query to verify associate-doctor relationships
SELECT 'Associate-Doctor Relationships:' as info, COUNT(*) as count FROM tbl_associate_doctor_relationship;

-- Query to verify patient interactions
SELECT 'Patient Interactions:' as info, COUNT(*) as count FROM tbl_associate_patient_interaction;

-- Query to show associate summary
SELECT 
    employee_id,
    CONCAT(first_name, ' ', last_name) as associate_name,
    specialization,
    experience_years,
    status
FROM tbl_associate 
ORDER BY employee_id;

-- Query to show associate-doctor relationships
SELECT 
    a.employee_id as associate_id,
    CONCAT(a.first_name, ' ', a.last_name) as associate_name,
    CONCAT(e.first_name, ' ', e.last_name) as doctor_name,
    d.department_name,
    adr.relationship_type,
    adr.start_date
FROM tbl_associate_doctor_relationship adr
JOIN tbl_associate a ON adr.associate_id = a.id
JOIN tbl_employee e ON adr.doctor_id = e.id
LEFT JOIN tbl_department d ON adr.department_id = d.id
ORDER BY a.employee_id, e.employee_id;
