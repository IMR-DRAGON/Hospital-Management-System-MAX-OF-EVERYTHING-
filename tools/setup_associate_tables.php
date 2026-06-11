<?php
require_once dirname(__DIR__) . '/includes/init.php';

$results = [];

// 1. Add salary column to associates
$r1 = mysqli_query($connection, "ALTER TABLE `associates` ADD COLUMN IF NOT EXISTS `salary` DECIMAL(10,2) DEFAULT 0.00 AFTER `hire_date`");
$results[] = $r1 ? "✅ Added salary column to associates" : "❌ Salary: " . mysqli_error($connection);

// 2. Create associate_tasks table
$task_sql = "CREATE TABLE IF NOT EXISTS `associate_tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `associate_id` int(11) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_description` text,
  `due_date` date NOT NULL,
  `due_time` time DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Overdue') DEFAULT 'Pending',
  `completion_percentage` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`),
  KEY `associate_id` (`associate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$r2 = mysqli_query($connection, $task_sql);
$results[] = $r2 ? "✅ Created associate_tasks table" : "❌ Tasks table: " . mysqli_error($connection);

echo implode("<br>", $results);
?>
