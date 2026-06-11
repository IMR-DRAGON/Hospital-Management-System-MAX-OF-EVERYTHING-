-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add email column to tbl_employee if it doesn't exist
ALTER TABLE `tbl_employee` ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) NULL AFTER `last_name`;

-- Add index on username for faster lookups
ALTER TABLE `tbl_employee` ADD INDEX IF NOT EXISTS `idx_username` (`username`);
