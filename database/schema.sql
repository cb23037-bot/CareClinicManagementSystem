CREATE DATABASE IF NOT EXISTS `careclinic_app`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `careclinic_app`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL DEFAULT 'patient',
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL DEFAULT '',
  `address` TEXT NOT NULL,
  `gender` VARCHAR(20) NOT NULL DEFAULT '',
  `dob` DATE DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL DEFAULT '',
  `google_sub` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  UNIQUE KEY `uniq_users_google_sub` (`google_sub`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
