USE `careclinic_app`;

DELIMITER $$

CREATE PROCEDURE `migrate_hybrid_role_schema`()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
  ) THEN
    CREATE TABLE `users` (
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
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'admin_profiles'
  ) THEN
    CREATE TABLE `admin_profiles` (
      `user_id` INT UNSIGNED NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_id`),
      CONSTRAINT `fk_admin_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'patient_profiles'
  ) THEN
    CREATE TABLE `patient_profiles` (
      `user_id` INT UNSIGNED NOT NULL,
      `patient_code` VARCHAR(20) DEFAULT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_id`),
      UNIQUE KEY `uniq_patient_profiles_code` (`patient_code`),
      CONSTRAINT `fk_patient_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'doctor_profiles'
  ) THEN
    CREATE TABLE `doctor_profiles` (
      `user_id` INT UNSIGNED NOT NULL,
      `specialization` VARCHAR(120) NOT NULL DEFAULT '',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_id`),
      CONSTRAINT `fk_doctor_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'doctors'
  ) THEN
    CREATE TABLE `doctors` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` INT UNSIGNED DEFAULT NULL,
      `name` VARCHAR(120) NOT NULL,
      `specialization` VARCHAR(120) NOT NULL DEFAULT '',
      `email` VARCHAR(255) NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_doctors_email` (`email`),
      UNIQUE KEY `uniq_doctors_user_id` (`user_id`),
      CONSTRAINT `fk_doctors_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'doctors'
      AND COLUMN_NAME = 'user_id'
  ) THEN
    ALTER TABLE `doctors` ADD COLUMN `user_id` INT UNSIGNED NULL AFTER `id`;
  END IF;

  UPDATE `doctors` d
  LEFT JOIN `users` u ON u.id = d.user_id
  SET d.user_id = NULL
  WHERE d.user_id IS NOT NULL
    AND u.id IS NULL;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'doctors'
      AND INDEX_NAME = 'uniq_doctors_user_id'
  ) THEN
    ALTER TABLE `doctors` ADD UNIQUE KEY `uniq_doctors_user_id` (`user_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'doctors'
      AND CONSTRAINT_NAME = 'fk_doctors_user'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `doctors`
      ADD CONSTRAINT `fk_doctors_user`
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
      ON DELETE SET NULL ON UPDATE CASCADE;
  END IF;

  INSERT IGNORE INTO `admin_profiles` (`user_id`)
  SELECT `id` FROM `users` WHERE `role` = 'admin';

  INSERT IGNORE INTO `patient_profiles` (`user_id`)
  SELECT `id` FROM `users` WHERE `role` = 'patient';

  INSERT IGNORE INTO `doctor_profiles` (`user_id`, `specialization`)
  SELECT `id`, '' FROM `users` WHERE `role` = 'doctor';

  UPDATE `doctors` d
  INNER JOIN `users` u ON u.email = d.email AND u.role = 'doctor'
  SET d.user_id = u.id
  WHERE d.user_id IS NULL;

  INSERT INTO `doctor_profiles` (`user_id`, `specialization`)
  SELECT u.id, d.specialization
  FROM `doctors` d
  INNER JOIN `users` u ON u.email = d.email AND u.role = 'doctor'
  ON DUPLICATE KEY UPDATE
    `specialization` = VALUES(`specialization`);
END$$

CALL `migrate_hybrid_role_schema`();
DROP PROCEDURE `migrate_hybrid_role_schema`$$

DELIMITER ;
