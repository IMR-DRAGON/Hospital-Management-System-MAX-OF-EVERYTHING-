-- =====================================================
-- HMS Associate Tables Creation Script
-- Creates tables for Associates who bridge doctors and patients
-- =====================================================

-- 1. Update tbl_role to include Associate role
-- First, check if Associate role already exists and add if not
INSERT IGNORE INTO `tbl_role` (`title`, `role`) VALUES ('Associate', 3);

-- 2. Create tbl_associate table for associates who bridge doctors and patients
CREATE TABLE IF NOT EXISTS `tbl_associate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL UNIQUE,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `joining_date` date NOT NULL,
  `qualification` varchar(255),
  `specialization` varchar(255),
  `experience_years` int(3) DEFAULT 0,
  `bio` text,
  `profile_image` varchar(255),
  `employee_ref_id` int(11) NOT NULL, -- Reference to tbl_employee
  `status` enum('1','0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `employee_ref_id` (`employee_ref_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`employee_ref_id`) REFERENCES `tbl_employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Associates who bridge doctors and patients';

-- 3. Create tbl_associate_doctor_relationship table
-- This table manages which associates work with which doctors
CREATE TABLE IF NOT EXISTS `tbl_associate_doctor_relationship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `associate_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `department_id` int(11),
  `relationship_type` enum('Primary','Secondary','Backup') DEFAULT 'Primary',
  `start_date` date NOT NULL,
  `end_date` date NULL,
  `status` enum('1','0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_associate_doctor` (`associate_id`, `doctor_id`, `start_date`),
  KEY `associate_id` (`associate_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `department_id` (`department_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`associate_id`) REFERENCES `tbl_associate`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `tbl_employee`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `tbl_department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Relationships between associates and doctors';

-- 4. Create tbl_associate_patient_interaction table
-- This table tracks interactions between associates and patients
CREATE TABLE IF NOT EXISTS `tbl_associate_patient_interaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `interaction_id` varchar(50) NOT NULL UNIQUE,
  `associate_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11),
  `appointment_id` int(11),
  `interaction_type` enum('Initial_Contact','Follow_up','Consultation_Support','Patient_Education','Care_Coordination','Discharge_Planning','Other') NOT NULL,
  `interaction_date` datetime NOT NULL,
  `duration_minutes` int(4),
  `location` enum('Clinic','Hospital','Phone','Video_Call','Home_Visit','Other') DEFAULT 'Clinic',
  `purpose` varchar(255),
  `summary` text,
  `patient_feedback` text,
  `follow_up_required` enum('Yes','No') DEFAULT 'No',
  `follow_up_date` date NULL,
  `status` enum('Completed','Scheduled','Cancelled','Rescheduled') DEFAULT 'Completed',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interaction_id` (`interaction_id`),
  KEY `associate_id` (`associate_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `interaction_date` (`interaction_date`),
  KEY `status` (`status`),
  FOREIGN KEY (`associate_id`) REFERENCES `tbl_associate`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `tbl_patient`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `tbl_employee`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`appointment_id`) REFERENCES `tbl_appointment`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Patient interactions managed by associates';

-- 5. Create tbl_associate_workload table
-- This table tracks workload and capacity of associates
CREATE TABLE IF NOT EXISTS `tbl_associate_workload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `associate_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `max_patients_per_day` int(3) DEFAULT 20,
  `current_patient_count` int(3) DEFAULT 0,
  `available_slots` int(3),
  `working_hours_start` time DEFAULT '09:00:00',
  `working_hours_end` time DEFAULT '17:00:00',
  `break_start` time DEFAULT '12:00:00',
  `break_end` time DEFAULT '13:00:00',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_associate_date` (`associate_id`, `date`),
  KEY `associate_id` (`associate_id`),
  KEY `date` (`date`),
  FOREIGN KEY (`associate_id`) REFERENCES `tbl_associate`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily workload tracking for associates';

-- 6. Create tbl_associate_permissions table
-- This table manages specific permissions for associates
CREATE TABLE IF NOT EXISTS `tbl_associate_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `associate_id` int(11) NOT NULL,
  `permission_type` enum('View_Patient_Records','Update_Patient_Info','Schedule_Appointments','Access_Medical_History','Patient_Communication','Report_Generation','Emergency_Access') NOT NULL,
  `permission_level` enum('Read','Write','Full') DEFAULT 'Read',
  `department_scope` varchar(255) DEFAULT 'All' COMMENT 'Specific departments or All',
  `granted_by` int(11),
  `granted_date` date NOT NULL,
  `expiry_date` date NULL,
  `status` enum('1','0') DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `associate_id` (`associate_id`),
  KEY `permission_type` (`permission_type`),
  KEY `status` (`status`),
  KEY `granted_by` (`granted_by`),
  FOREIGN KEY (`associate_id`) REFERENCES `tbl_associate`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `tbl_employee`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Permissions and access control for associates';

-- =====================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- =====================================================

-- Additional indexes for better query performance
CREATE INDEX IF NOT EXISTS `idx_associate_status_created` ON `tbl_associate` (`status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_interaction_type_date` ON `tbl_associate_patient_interaction` (`interaction_type`, `interaction_date`);
CREATE INDEX IF NOT EXISTS `idx_workload_date_status` ON `tbl_associate_workload` (`date`, `associate_id`);

-- =====================================================
-- TRIGGERS FOR AUTOMATIC WORKLOAD CALCULATION
-- =====================================================

DELIMITER $$

-- Trigger to update workload when interactions are added
CREATE TRIGGER IF NOT EXISTS `update_associate_workload_on_interaction_insert`
AFTER INSERT ON `tbl_associate_patient_interaction`
FOR EACH ROW
BEGIN
    INSERT INTO `tbl_associate_workload` (`associate_id`, `date`, `current_patient_count`)
    VALUES (NEW.associate_id, DATE(NEW.interaction_date), 1)
    ON DUPLICATE KEY UPDATE 
    `current_patient_count` = `current_patient_count` + 1,
    `available_slots` = `max_patients_per_day` - (`current_patient_count` + 1);
END$$

-- Trigger to update workload when interactions are deleted
CREATE TRIGGER IF NOT EXISTS `update_associate_workload_on_interaction_delete`
AFTER DELETE ON `tbl_associate_patient_interaction`
FOR EACH ROW
BEGIN
    UPDATE `tbl_associate_workload` 
    SET `current_patient_count` = GREATEST(0, `current_patient_count` - 1),
        `available_slots` = `max_patients_per_day` - GREATEST(0, `current_patient_count` - 1)
    WHERE `associate_id` = OLD.associate_id 
    AND `date` = DATE(OLD.interaction_date);
END$$

DELIMITER ;

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for associate summary with doctor relationships
CREATE OR REPLACE VIEW `view_associate_summary` AS
SELECT 
    a.id,
    a.employee_id,
    CONCAT(a.first_name, ' ', a.last_name) as full_name,
    a.email,
    a.phone,
    a.qualification,
    a.specialization,
    a.experience_years,
    a.status,
    COUNT(DISTINCT adr.doctor_id) as assigned_doctors,
    COUNT(DISTINCT api.patient_id) as total_patients_served,
    COUNT(DISTINCT CASE WHEN api.interaction_date >= CURDATE() - INTERVAL 30 DAY THEN api.patient_id END) as recent_patients
FROM `tbl_associate` a
LEFT JOIN `tbl_associate_doctor_relationship` adr ON a.id = adr.associate_id AND adr.status = '1'
LEFT JOIN `tbl_associate_patient_interaction` api ON a.id = api.associate_id
WHERE a.status = '1'
GROUP BY a.id;

-- View for active associate-doctor relationships
CREATE OR REPLACE VIEW `view_associate_doctor_active` AS
SELECT 
    adr.id,
    CONCAT(a.first_name, ' ', a.last_name) as associate_name,
    a.employee_id as associate_emp_id,
    CONCAT(e.first_name, ' ', e.last_name) as doctor_name,
    e.employee_id as doctor_emp_id,
    d.department_name,
    adr.relationship_type,
    adr.start_date,
    adr.end_date,
    adr.status
FROM `tbl_associate_doctor_relationship` adr
JOIN `tbl_associate` a ON adr.associate_id = a.id
JOIN `tbl_employee` e ON adr.doctor_id = e.id
LEFT JOIN `tbl_department` d ON adr.department_id = d.id
WHERE adr.status = '1' AND a.status = '1' AND e.status = 1;

-- View for associate daily workload
CREATE OR REPLACE VIEW `view_associate_daily_workload` AS
SELECT 
    aw.id,
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
FROM `tbl_associate_workload` aw
JOIN `tbl_associate` a ON aw.associate_id = a.id
WHERE a.status = '1'
ORDER BY aw.date DESC, aw.associate_id;
