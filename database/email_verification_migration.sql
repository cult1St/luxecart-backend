-- Frisan Email Verification System - Database Migration
-- Execute this SQL to create the email_verifications table

-- Email Verifications Table
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED,
    `email` VARCHAR(150) NOT NULL,
    `code` VARCHAR(6) NOT NULL,
    `is_verified` TINYINT(1) DEFAULT 0 COMMENT '0=pending, 1=verified, 2=expired',
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for faster queries
    INDEX `idx_email` (`email`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`),
    
    -- Foreign key constraint
    CONSTRAINT `fk_email_verifications_user_id` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add is_verified column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) DEFAULT 0 AFTER `is_active`;

-- Add google_id column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(255) UNIQUE AFTER `password`;

-- Create index on is_verified for faster queries
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_is_verified` (`is_verified`);

-- Verify the tables were created
SHOW CREATE TABLE `email_verifications`;
SHOW CREATE TABLE `users`;
