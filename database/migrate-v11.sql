-- Add blocked flag for admin user moderation
ALTER TABLE `users` ADD COLUMN `blocked` TINYINT(1) DEFAULT 0 AFTER `verified`;
