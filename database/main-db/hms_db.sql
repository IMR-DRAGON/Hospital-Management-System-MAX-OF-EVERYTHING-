-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2025 at 10:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `associates`
--

CREATE TABLE `associates` (
  `associate_id` int(11) NOT NULL,
  `associate_code` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `associate_type` enum('Patient Coordinator','Medical Assistant','Nurse Coordinator','Case Manager','Patient Advocate','Clinical Coordinator') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `performance_rating` decimal(3,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','On Leave','Suspended','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `associates`
--

INSERT INTO `associates` (`associate_id`, `associate_code`, `first_name`, `last_name`, `email`, `phone`, `position`, `associate_type`, `department`, `hire_date`, `performance_rating`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ASC-001', 'Sarah', 'Johnson', 'sarah.johnson@hospital.com', '+1234567890', 'Senior Coordinator', 'Patient Coordinator', 'Cardiology', '2024-01-15', 0.00, 'Active', '2025-10-17 18:28:58', '2025-10-17 18:28:58'),
(2, 'ASC-002', 'Michael', 'Chen', 'michael.chen@hospital.com', '+1234567891', 'Medical Assistant', 'Medical Assistant', 'Emergency', '2024-02-01', 0.00, 'Active', '2025-10-17 18:28:58', '2025-10-17 18:28:58'),
(3, 'ASC-003', 'Emily', 'Davis', 'emily.davis@hospital.com', '+1234567892', 'Nurse Coordinator', 'Nurse Coordinator', 'Pediatrics', '2024-01-20', 0.00, 'Active', '2025-10-17 18:28:58', '2025-10-17 18:28:58');

-- --------------------------------------------------------

--
-- Table structure for table `associate_assignments`
--

CREATE TABLE `associate_assignments` (
  `assignment_id` int(11) NOT NULL,
  `associate_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `assignment_type` varchar(100) DEFAULT NULL,
  `priority_level` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `assignment_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `assignment_status` enum('Active','Completed','Cancelled','On Hold','Transferred') DEFAULT 'Active',
  `satisfaction_rating` decimal(3,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_inventory`
--

CREATE TABLE `blood_inventory` (
  `unit_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `collection_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('Available','Used','Expired') DEFAULT 'Available',
  `request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_inventory`
--

INSERT INTO `blood_inventory` (`unit_id`, `donor_id`, `blood_type`, `collection_date`, `expiry_date`, `status`, `request_id`) VALUES
(1, 1, 'O+', '2024-12-20', '2025-02-20', 'Available', NULL),
(2, 2, 'A-', '2024-12-22', '2025-02-22', 'Available', NULL),
(3, 3, 'B+', '2024-12-25', '2025-02-25', 'Available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE `blood_requests` (
  `request_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `required_blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_required` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Fulfilled','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`request_id`, `patient_name`, `required_blood_type`, `units_required`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Alice Brown', 'O+', 2, 'Surgery', 'Pending', '2025-10-16 14:37:13', '2025-10-16 14:37:13'),
(2, 'Charlie Wilson', 'A-', 1, 'Accident', 'Fulfilled', '2025-10-16 14:37:13', '2025-10-16 14:37:13');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `conversation_id` int(11) NOT NULL,
  `conversation_uuid` varchar(36) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `associate_id` int(11) DEFAULT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `conversation_type` enum('Patient-Doctor','Patient-Associate','Patient-Donor','Doctor-Associate') NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Closed','Archived') DEFAULT 'Active',
  `is_active` tinyint(1) DEFAULT 1,
  `is_archived` tinyint(1) DEFAULT 0,
  `is_encrypted` tinyint(1) DEFAULT 1,
  `encryption_key` varchar(255) DEFAULT NULL,
  `privacy_level` enum('Private') DEFAULT 'Private',
  `allow_file_sharing` tinyint(1) DEFAULT 0,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`conversation_id`, `conversation_uuid`, `patient_id`, `doctor_id`, `associate_id`, `donor_id`, `conversation_type`, `title`, `description`, `status`, `is_active`, `is_archived`, `is_encrypted`, `encryption_key`, `privacy_level`, `allow_file_sharing`, `last_message_at`, `last_activity_at`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'c2bfa889-d6dd-cf7a-f362-e803c1ba5da0', 3, 14, NULL, NULL, 'Patient-Doctor', NULL, NULL, 'Active', 1, 0, 1, NULL, 'Private', 0, NULL, NULL, '2025-10-19 19:10:59', '2025-10-19 19:10:59', 'Test Script', NULL),
(2, 'cf1e36a4-7e93-1137-9bf3-f0815633e894', 6, 14, NULL, NULL, '', NULL, NULL, 'Active', 1, 0, 1, NULL, 'Private', 0, NULL, NULL, '2025-10-19 19:46:15', '2025-10-19 20:13:46', 'Patient', NULL),
(3, '08bb0d26-2082-74a1-38f7-87b1c29f5116', 6, NULL, 3, NULL, '', NULL, NULL, 'Active', 1, 0, 1, NULL, 'Private', 0, NULL, NULL, '2025-10-19 19:46:22', '2025-10-19 20:00:44', 'Patient', NULL),
(4, '5ba71785-53f3-3655-a4ff-cb28357e275a', 6, NULL, 2, NULL, '', NULL, NULL, 'Active', 1, 0, 1, NULL, 'Private', 0, NULL, NULL, '2025-10-19 19:46:29', '2025-10-19 20:00:28', 'Patient', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_files`
--

CREATE TABLE `chat_files` (
  `file_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_category` enum('Medical_Record','Prescription','Lab_Report','X_Ray','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('Patient','Doctor','Associate','Donor') NOT NULL,
  `message_text` text DEFAULT NULL,
  `message_type` enum('Text','File','Prescription','Medical_Record') DEFAULT 'Text',
  `is_edited` tinyint(1) DEFAULT 0,
  `edited_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `conversation_id`, `sender_id`, `sender_type`, `message_text`, `message_type`, `is_edited`, `edited_at`, `is_read`, `read_at`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Patient', 'Hello Doctor, this is a test message.', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 19:10:59', '2025-10-19 19:10:59'),
(2, 4, 6, 'Patient', 'hello mr ching chong', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:00:28', '2025-10-19 20:00:28'),
(3, 3, 6, 'Patient', 'hello beautiful', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:00:44', '2025-10-19 20:00:44'),
(4, 2, 6, 'Patient', 'mr alam pachay tor kolom', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:00:57', '2025-10-19 20:00:57'),
(5, 2, 14, 'Doctor', 'shundori tmi ki single?', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:01:27', '2025-10-19 20:01:27'),
(6, 2, 14, 'Doctor', 'shundori tmi onk qt', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:02:26', '2025-10-19 20:02:26'),
(7, 2, 14, 'Doctor', 'hello', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:11:05', '2025-10-19 20:11:05'),
(8, 2, 6, 'Patient', 'please sir, im married', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:13:39', '2025-10-19 20:13:39'),
(9, 2, 6, 'Patient', 'dont disturb me', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:13:43', '2025-10-19 20:13:43'),
(10, 2, 6, 'Patient', 'thank you', 'Text', 0, NULL, 0, NULL, 0, NULL, '2025-10-19 20:13:46', '2025-10-19 20:13:46');

-- --------------------------------------------------------

--
-- Table structure for table `chat_participants`
--

CREATE TABLE `chat_participants` (
  `participant_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('Patient','Doctor','Associate','Donor') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_participants`
--

INSERT INTO `chat_participants` (`participant_id`, `conversation_id`, `user_id`, `user_type`, `is_active`, `joined_at`) VALUES
(1, 1, 3, 'Patient', 1, '2025-10-19 19:10:59'),
(2, 1, 14, 'Doctor', 1, '2025-10-19 19:10:59'),
(3, 2, 6, 'Patient', 1, '2025-10-19 19:46:15'),
(4, 2, 14, 'Doctor', 1, '2025-10-19 19:46:15'),
(5, 3, 6, 'Patient', 1, '2025-10-19 19:46:22'),
(6, 3, 3, 'Associate', 1, '2025-10-19 19:46:22'),
(7, 4, 6, 'Patient', 1, '2025-10-19 19:46:29'),
(8, 4, 2, 'Associate', 1, '2025-10-19 19:46:29');

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `donor_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `age` int(11) NOT NULL,
  `last_donation_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `total_donations` int(11) DEFAULT 0,
  `donor_code` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donors`
--

INSERT INTO `donors` (`donor_id`, `name`, `email`, `phone`, `blood_type`, `age`, `last_donation_date`, `status`, `created_at`, `password`, `address`, `gender`, `date_of_birth`, `total_donations`, `donor_code`, `emergency_contact`, `emergency_phone`) VALUES
(1, 'John Doe', 'john@example.com', '1234567890', 'O+', 25, NULL, 'Active', '2025-10-16 14:35:52', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(2, 'Jane Smith', 'jane@example.com', '0987654321', 'A-', 30, NULL, 'Active', '2025-10-16 14:35:52', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(3, 'Bob Johnson', 'bob@example.com', '1122334455', 'B+', 28, NULL, 'Active', '2025-10-16 14:35:52', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(4, 'Atique Bin Mahmud', '123atiq@gay.com', '017123467845', 'O+', 65, NULL, 'Active', '2025-10-16 14:40:46', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(5, 'Sultan mahmud', 'sultan221@gmail.com', '01712946784', 'B+', 35, NULL, 'Active', '2025-10-18 19:26:52', 'sultan221', 'Dhanmondi', 'Male', '2025-10-10', 0, 'DON-3361', 'Atique Bin Mahmud', '0187564845'),
(6, 'Sultan mahmud', 'mim221@gmail.com', '01712946784', 'B+', 35, NULL, 'Active', '2025-10-18 19:33:56', 'faria221', 'Dhanmondi', 'Male', '2025-10-10', 0, 'DON-4997', 'Atique Bin Mahmud', '0187564845'),
(7, 'Sultan mahmud', 'maria221@gmail.com', '01712946784', 'B+', 35, NULL, 'Active', '2025-10-18 19:39:37', 'faria221', 'uganda', 'Male', '2025-10-10', 0, 'DON-2640', 'atikur rahman', '0187564845'),
(8, 'kariam mia', 'karim@gmail.com', '1231231', 'O+', 30, NULL, 'Active', '2025-10-19 06:13:10', 'rahat221', 'file-text-osdasd', 'Male', '1993-06-15', 0, 'DON-0270', 'asd', 'asdas'),
(9, 'kariam mia', 'faria221@gmail.com', '1231231', 'B+', 30, NULL, 'Active', '2025-10-19 07:27:13', 'faria221', '', 'Female', '1993-06-15', 0, 'DON-0096', 'asd', 'asdas');

-- --------------------------------------------------------

--
-- Table structure for table `file_categories`
--

CREATE TABLE `file_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_description` text DEFAULT NULL,
  `allowed_extensions` text DEFAULT NULL,
  `max_file_size` int(11) DEFAULT 10485760,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_categories`
--

INSERT INTO `file_categories` (`category_id`, `category_name`, `category_description`, `allowed_extensions`, `max_file_size`, `is_active`, `created_at`) VALUES
(1, 'Medical Records', 'General medical records and reports', 'pdf,doc,docx,jpg,jpeg,png', 10485760, 1, '2025-10-17 23:34:35'),
(2, 'Prescriptions', 'Doctor prescriptions and medication records', 'pdf,doc,docx,jpg,jpeg,png', 5242880, 1, '2025-10-17 23:34:35'),
(3, 'Lab Reports', 'Laboratory test results and reports', 'pdf,jpg,jpeg,png', 10485760, 1, '2025-10-17 23:34:35'),
(4, 'X-Ray Images', 'X-Ray and imaging results', 'jpg,jpeg,png,dcm', 20971520, 1, '2025-10-17 23:34:35'),
(5, 'MRI/CT Scans', 'MRI and CT scan results', 'jpg,jpeg,png,dcm', 52428800, 1, '2025-10-17 23:34:35'),
(6, 'Blood Tests', 'Blood test results and reports', 'pdf,jpg,jpeg,png', 10485760, 1, '2025-10-17 23:34:35'),
(7, 'Other Documents', 'Other medical documents and files', 'pdf,doc,docx,jpg,jpeg,png,txt', 10485760, 1, '2025-10-17 23:34:35');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_amenities`
--

CREATE TABLE `hospital_amenities` (
  `amenity_id` int(11) NOT NULL,
  `amenity_name` varchar(200) NOT NULL,
  `amenity_type` enum('Medical Equipment','Furniture','Electronics','Maintenance','Cleaning','Food Service','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('Active','Inactive','Under Maintenance','Disposed') DEFAULT 'Active',
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `maintenance_schedule` varchar(100) DEFAULT NULL,
  `is_critical` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `batch_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `quantity_remaining` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `received_date` date DEFAULT curdate(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`category_id`, `category_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Medications', 'Prescription and over-the-counter medications', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(2, 'Medical Supplies', 'Bandages, syringes, gloves, and other medical supplies', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(3, 'Surgical Equipment', 'Surgical instruments and equipment', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(4, 'Diagnostic Equipment', 'Medical diagnostic devices and equipment', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(5, 'Furniture', 'Hospital furniture and fixtures', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(6, 'Electronics', 'Electronic devices and equipment', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(7, 'Cleaning Supplies', 'Cleaning and sanitization products', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(8, 'Food Service', 'Food and beverage items', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(9, 'Maintenance', 'Maintenance tools and supplies', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(10, 'Emergency Equipment', 'Emergency and safety equipment', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'pieces',
  `current_stock` int(11) DEFAULT 0,
  `minimum_stock_level` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 0,
  `maximum_stock_level` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `is_critical` tinyint(1) DEFAULT 0,
  `is_perishable` tinyint(1) DEFAULT 0,
  `shelf_life_days` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `item_code`, `item_name`, `description`, `category_id`, `unit_of_measure`, `current_stock`, `minimum_stock_level`, `reorder_level`, `maximum_stock_level`, `unit_cost`, `selling_price`, `is_critical`, `is_perishable`, `shelf_life_days`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '123', 'injection', '', 4, 'pieces', 500, 100, 500, 0, 20.00, 0.00, 1, 0, 10, 1, 221, '2025-10-17 22:12:20', '2025-10-17 22:12:20'),
(3, '564', 'gauge', '', 7, 'boxes', 500, 200, 450, 0, 50.00, 0.00, 1, 0, 30, 1, 221, '2025-10-19 06:21:59', '2025-10-19 06:21:59'),
(6, '906', 'saline', '', 2, 'boxes', 1000, 200, 120, 0, 500.00, 0.00, 1, 0, 30, 1, 221, '2025-10-19 06:23:54', '2025-10-19 06:23:54');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_notifications`
--

CREATE TABLE `inventory_notifications` (
  `notification_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `notification_type` enum('Low Stock','Expiry Warning','Expired','Reorder Required','Critical Stock') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_reports`
--

CREATE TABLE `inventory_reports` (
  `report_id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `report_name` varchar(200) NOT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_medical_records`
--

CREATE TABLE `patient_medical_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `record_type` enum('Prescription','Lab_Report','X_Ray','MRI','CT_Scan','Blood_Test','Other') NOT NULL,
  `record_title` varchar(255) NOT NULL,
  `record_description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `record_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `prescription_text` text NOT NULL,
  `prescription_file` varchar(500) DEFAULT NULL,
  `prescribed_date` date NOT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','Received','Cancelled') DEFAULT 'Draft',
  `order_date` date DEFAULT curdate(),
  `expected_delivery` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `poi_id` int(11) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity_ordered` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `quantity_received` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `movement_type` enum('Purchase','Sale','Transfer In','Transfer Out','Adjustment','Expired','Damaged') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'MedSupply Co.', 'John Smith', 'john@medsupply.com', '555-0101', '123 Medical Ave, City', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(2, 'HealthTech Solutions', 'Sarah Johnson', 'sarah@healthtech.com', '555-0102', '456 Tech Street, City', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(3, 'Global Medical', 'Mike Wilson', 'mike@globalmedical.com', '555-0103', '789 Global Blvd, City', 1, '2025-10-17 22:06:21', '2025-10-17 22:06:21'),
(4, 'MedSupply Co.', 'John Smith', 'john@medsupply.com', '555-0101', '123 Medical Ave, City', 1, '2025-10-17 22:07:50', '2025-10-17 22:07:50'),
(5, 'HealthTech Solutions', 'Sarah Johnson', 'sarah@healthtech.com', '555-0102', '456 Tech Street, City', 1, '2025-10-17 22:07:50', '2025-10-17 22:07:50'),
(6, 'Global Medical', 'Mike Wilson', 'mike@globalmedical.com', '555-0103', '789 Global Blvd, City', 1, '2025-10-17 22:07:50', '2025-10-17 22:07:50'),
(7, 'MedSupply Co.', 'John Smith', 'john@medsupply.com', '555-0101', '123 Medical Ave, City', 1, '2025-10-17 22:09:29', '2025-10-17 22:09:29'),
(8, 'HealthTech Solutions', 'Sarah Johnson', 'sarah@healthtech.com', '555-0102', '456 Tech Street, City', 1, '2025-10-17 22:09:29', '2025-10-17 22:09:29'),
(9, 'Global Medical', 'Mike Wilson', 'mike@globalmedical.com', '555-0103', '789 Global Blvd, City', 1, '2025-10-17 22:09:29', '2025-10-17 22:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_appointment`
--

CREATE TABLE `tbl_appointment` (
  `id` int(11) NOT NULL,
  `appointment_id` varchar(250) NOT NULL,
  `patient_name` varchar(250) NOT NULL,
  `department` varchar(250) NOT NULL,
  `doctor` varchar(250) NOT NULL,
  `date` varchar(250) NOT NULL,
  `time` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `status` tinyint(4) NOT NULL COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_appointment`
--

INSERT INTO `tbl_appointment` (`id`, `appointment_id`, `patient_name`, `department`, `doctor`, `date`, `time`, `message`, `status`, `created_at`) VALUES
(2, 'APT-1', 'Atique Bin Mahmud', 'Cardiology', 'prof. md shamsul huda', '28/09/2025', '9:23 PM', 'zzz', 1, '2025-10-04 15:26:03'),
(3, 'APT-3', 'nazmul islam emon', 'Medicine', 'dctr alam mia', '02/10/2025', '6:23 PM', 'zz', 1, '2025-10-04 15:26:31'),
(4, 'APT-4', 'Faria Nusrat', 'Cardiology ', 'dctr alam mia', '2025-10-24', '12:00:00', '', 1, '2025-10-17 23:46:13'),
(5, 'APT-5', 'Faria Nusrat', 'Medicine ', 'prof. md shamsul  huda', '2025-10-30', '09:00:00', '', 1, '2025-10-18 18:57:52');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_associate`
--

CREATE TABLE `tbl_associate` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assigned_doctor_id` int(11) NOT NULL,
  `assigned_patient_id` int(11) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Associate' COMMENT 'Bridge between doctor and patient',
  `notes` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_attachment`
--

CREATE TABLE `tbl_attachment` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `associate_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_attachment`
--

INSERT INTO `tbl_attachment` (`id`, `patient_id`, `doctor_id`, `associate_id`, `file_name`, `file_path`, `file_type`, `file_size`, `description`, `uploaded_at`) VALUES
(1, 6, NULL, NULL, 'Screenshot 2025-08-13 151637.png', 'uploads/patient_attachments/6/patient_6_68f4fd021ba829.71979683.png', 'image/png', 264426, '', '2025-10-19 15:00:18'),
(2, 6, NULL, NULL, 'Screenshot 2025-08-25 152629.png', 'uploads/patient_attachments/6/patient_6_68f4fdb6ce5923.31820263.png', 'image/png', 105285, 'My last medical report ', '2025-10-19 15:03:18'),
(3, 6, NULL, NULL, 'Screenshot 2025-09-13 202641.png', 'uploads/patient_attachments/6/patient_6_68f4fdcddef2e8.67774939.png', 'image/png', 78213, 'this is my xray report ', '2025-10-19 15:03:41');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_blood_bank`
--

CREATE TABLE `tbl_blood_bank` (
  `id` int(11) NOT NULL,
  `bag_id` varchar(100) NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `collection_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` varchar(20) NOT NULL COMMENT 'Available, Reserved, Used, Expired',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_blood_request`
--

CREATE TABLE `tbl_blood_request` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `quantity` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `status` varchar(20) NOT NULL COMMENT 'Pending, Approved, Rejected, Fulfilled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reason` text DEFAULT NULL,
  `urgency_level` varchar(20) DEFAULT 'Normal',
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_chat`
--

CREATE TABLE `tbl_chat` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `sender_type` enum('patient','doctor') NOT NULL,
  `receiver_type` enum('patient','doctor') NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=Unread,1=Read',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_department`
--

CREATE TABLE `tbl_department` (
  `id` int(11) NOT NULL,
  `department_name` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `status` tinyint(4) NOT NULL COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_department`
--

INSERT INTO `tbl_department` (`id`, `department_name`, `description`, `status`, `created_at`) VALUES
(5, 'Medicine ', 'Zzz', 1, '2025-10-04 15:12:38'),
(6, 'Cardiology ', 'zzx', 1, '2025-10-04 15:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_donor`
--

CREATE TABLE `tbl_donor` (
  `id` int(11) NOT NULL,
  `donor_name` varchar(150) NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `dob` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `last_donation_date` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_employee`
--

CREATE TABLE `tbl_employee` (
  `id` int(11) NOT NULL,
  `first_name` varchar(250) NOT NULL,
  `last_name` varchar(250) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `username` varchar(250) NOT NULL,
  `emailid` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `dob` varchar(50) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `bio` text NOT NULL,
  `employee_id` varchar(250) NOT NULL,
  `joining_date` varchar(250) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `role` varchar(50) NOT NULL COMMENT '1=Admin, 2=Doctor, 3=Nurse, 4=Accountant',
  `status` tinyint(4) NOT NULL COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_employee`
--

INSERT INTO `tbl_employee` (`id`, `first_name`, `last_name`, `email`, `username`, `emailid`, `password`, `dob`, `gender`, `address`, `bio`, `employee_id`, `joining_date`, `phone`, `role`, `status`, `created_at`) VALUES
(13, 'Rahat', 'Rayan ', 'rrahat221491@bscse.uiu.ac.bd', 'rahat221', 'rrahat221491@bscse.uiu.ac.bd', 'rahat221', '14/12/1999', 'male', 'dhaka', '....', '221', '20/05/2009', 'frfr', '1', 1, '2025-10-04 15:03:54'),
(14, 'dctr alam', 'mia', 'dr.alam@hospital.com', 'alam', 'alambhai@gmail.com', 'alam', '04/10/2025', 'Male', 'uganda', 'Cardiologist - Specializes in heart diseases and cardiovascular treatments', '111', '04/10/2025', '0909090909', '2', 1, '2025-10-17 23:45:02'),
(15, 'prof. md shamsul ', 'huda', 'prof.huda@hospital.com', 'huda', 'huda@gmail.com', 'huda', '04/10/2025', 'Male', 'uganda', 'Neurologist - Expert in brain and nervous system disorders', '222', '04/10/2025', '0909090909', '2', 1, '2025-10-17 23:45:11'),
(16, 'Sumon ', 'Mia', NULL, 'Sumon', 'sumonMia@gmail.com', 'rahat221', '20/08/2025', 'Male', 'uganda', '....', '330', '23/08/2025', '0187564811', '2', 1, '2025-10-18 19:52:04'),
(17, 'prof. Atikur  ', 'Rahman', NULL, 'atikur', 'Atikur@gmail.com', 'atikur221', '04/10/1996', 'Male', 'Dhaka', 'Medicine specialist', '561', '23/08/2025', '0174376856', '2', 1, '2025-10-19 10:09:31');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_inventory`
--

CREATE TABLE `tbl_inventory` (
  `id` int(11) NOT NULL,
  `item_code` varchar(100) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL COMMENT 'Medicine, Equipment, Consumable',
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL COMMENT 'pcs, box, ml, strip etc.',
  `reorder_level` int(11) NOT NULL DEFAULT 5 COMMENT 'Minimum stock before alert',
  `expiry_date` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_patient`
--

CREATE TABLE `tbl_patient` (
  `id` int(11) NOT NULL,
  `first_name` varchar(250) NOT NULL,
  `last_name` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `password` varchar(250) DEFAULT NULL,
  `dob` varchar(250) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `patient_type` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(10) NOT NULL,
  `status` tinyint(4) NOT NULL COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_patient`
--

INSERT INTO `tbl_patient` (`id`, `first_name`, `last_name`, `email`, `password`, `dob`, `gender`, `patient_type`, `address`, `phone`, `status`, `created_at`) VALUES
(3, 'nazmul islam ', 'emon ', 'emon@gmail.com', NULL, '04/10/2002', 'Female', 'InPatient', 'uganda', '0909090909', 1, '2025-10-04 15:09:22'),
(4, 'Atique Bin', 'Mahmud', 'mahmud@gmail.com', NULL, '04/10/2020', 'Female', 'OutPatient', 'uganda', '0909090909', 1, '2025-10-04 15:10:14'),
(5, 'elham ', 'mahmud', 'rahat221@gmail.com', 'rahat221', '04/10/2020', 'Male', 'InPatient', 'uganda', '0909090909', 1, '2025-10-16 14:49:49'),
(6, 'Faria', 'Nusrat', 'faria221@gmail.com', 'faria221', '04/10/2002', 'Female', 'InPatient', 'uganda', '0187564839', 1, '2025-10-17 19:16:20');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role`
--

CREATE TABLE `tbl_role` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `role` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_schedule`
--

CREATE TABLE `tbl_schedule` (
  `id` int(11) NOT NULL,
  `doctor_name` varchar(250) NOT NULL,
  `available_days` text NOT NULL,
  `start_time` varchar(250) NOT NULL,
  `end_time` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `status` tinyint(4) NOT NULL COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_schedule`
--

INSERT INTO `tbl_schedule` (`id`, `doctor_name`, `available_days`, `start_time`, `end_time`, `message`, `status`, `created_at`) VALUES
(2, 'dctr alam mia', 'Sunday, Monday', '9:10 AM', '11:10 PM', 'hello! ami doctor ', 1, '2025-10-04 15:11:26'),
(3, 'prof. md shamsul huda', 'Wednesday, Thursday', '10:11 AM', '6:11 PM', 'hello ! ami professor amare dekhan ', 1, '2025-10-04 15:12:03');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_week`
--

CREATE TABLE `tbl_week` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `associates`
--
ALTER TABLE `associates`
  ADD PRIMARY KEY (`associate_id`),
  ADD UNIQUE KEY `associate_code` (`associate_code`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `associate_assignments`
--
ALTER TABLE `associate_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `associate_id` (`associate_id`);

--
-- Indexes for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  ADD PRIMARY KEY (`unit_id`),
  ADD KEY `idx_bi_donor` (`donor_id`),
  ADD KEY `idx_bi_request` (`request_id`);

--
-- Indexes for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `conversation_uuid` (`conversation_uuid`);

--
-- Indexes for table `chat_files`
--
ALTER TABLE `chat_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_chat_files_message` (`message_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `chat_participants`
--
ALTER TABLE `chat_participants`
  ADD PRIMARY KEY (`participant_id`),
  ADD UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`,`user_type`);

--
-- Indexes for table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`donor_id`),
  ADD UNIQUE KEY `donor_code` (`donor_code`);

--
-- Indexes for table `file_categories`
--
ALTER TABLE `file_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `hospital_amenities`
--
ALTER TABLE `hospital_amenities`
  ADD PRIMARY KEY (`amenity_id`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `idx_inventory_batches_item` (`item_id`),
  ADD KEY `idx_inventory_batches_expiry` (`expiry_date`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `idx_inventory_items_category` (`category_id`),
  ADD KEY `idx_inventory_items_active` (`is_active`);

--
-- Indexes for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_item` (`item_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `patient_medical_records`
--
ALTER TABLE `patient_medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `idx_medical_records_patient` (`patient_id`),
  ADD KEY `idx_medical_records_doctor` (`doctor_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `idx_prescriptions_patient` (`patient_id`),
  ADD KEY `idx_prescriptions_doctor` (`doctor_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD UNIQUE KEY `po_number` (`po_number`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`poi_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `idx_stock_movements_item` (`item_id`),
  ADD KEY `idx_stock_movements_date` (`movement_date`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `tbl_appointment`
--
ALTER TABLE `tbl_appointment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_associate`
--
ALTER TABLE `tbl_associate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `assigned_doctor_id` (`assigned_doctor_id`),
  ADD KEY `assigned_patient_id` (`assigned_patient_id`);

--
-- Indexes for table `tbl_attachment`
--
ALTER TABLE `tbl_attachment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `associate_id` (`associate_id`);

--
-- Indexes for table `tbl_blood_bank`
--
ALTER TABLE `tbl_blood_bank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_blood_request`
--
ALTER TABLE `tbl_blood_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `tbl_chat`
--
ALTER TABLE `tbl_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `tbl_department`
--
ALTER TABLE `tbl_department`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_donor`
--
ALTER TABLE `tbl_donor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `tbl_inventory`
--
ALTER TABLE `tbl_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `tbl_patient`
--
ALTER TABLE `tbl_patient`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_role`
--
ALTER TABLE `tbl_role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_schedule`
--
ALTER TABLE `tbl_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_week`
--
ALTER TABLE `tbl_week`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `associates`
--
ALTER TABLE `associates`
  MODIFY `associate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `associate_assignments`
--
ALTER TABLE `associate_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blood_requests`
--
ALTER TABLE `blood_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chat_files`
--
ALTER TABLE `chat_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `chat_participants`
--
ALTER TABLE `chat_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `donors`
--
ALTER TABLE `donors`
  MODIFY `donor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `file_categories`
--
ALTER TABLE `file_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `hospital_amenities`
--
ALTER TABLE `hospital_amenities`
  MODIFY `amenity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_medical_records`
--
ALTER TABLE `patient_medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `poi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_appointment`
--
ALTER TABLE `tbl_appointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_associate`
--
ALTER TABLE `tbl_associate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_attachment`
--
ALTER TABLE `tbl_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_blood_bank`
--
ALTER TABLE `tbl_blood_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_blood_request`
--
ALTER TABLE `tbl_blood_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_chat`
--
ALTER TABLE `tbl_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_department`
--
ALTER TABLE `tbl_department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_donor`
--
ALTER TABLE `tbl_donor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_employee`
--
ALTER TABLE `tbl_employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_inventory`
--
ALTER TABLE `tbl_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_patient`
--
ALTER TABLE `tbl_patient`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_role`
--
ALTER TABLE `tbl_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_schedule`
--
ALTER TABLE `tbl_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_week`
--
ALTER TABLE `tbl_week`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `associate_assignments`
--
ALTER TABLE `associate_assignments`
  ADD CONSTRAINT `associate_assignments_ibfk_1` FOREIGN KEY (`associate_id`) REFERENCES `associates` (`associate_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_associate`
--
ALTER TABLE `tbl_associate`
  ADD CONSTRAINT `tbl_associate_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `tbl_employee` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_associate_ibfk_2` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `tbl_employee` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_associate_ibfk_3` FOREIGN KEY (`assigned_patient_id`) REFERENCES `tbl_patient` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_attachment`
--
ALTER TABLE `tbl_attachment`
  ADD CONSTRAINT `tbl_attachment_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `tbl_patient` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_attachment_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `tbl_employee` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_attachment_ibfk_3` FOREIGN KEY (`associate_id`) REFERENCES `tbl_associate` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_blood_request`
--
ALTER TABLE `tbl_blood_request`
  ADD CONSTRAINT `tbl_blood_request_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `tbl_patient` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_chat`
--
ALTER TABLE `tbl_chat`
  ADD CONSTRAINT `tbl_chat_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `tbl_patient` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_chat_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `tbl_employee` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
