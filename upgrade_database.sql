-- =================================================================
-- UPGRADE DATABASE - Adaugă funcționalități avansate
-- =================================================================
-- Acest script adaugă tabele noi pentru:
-- - Gestionare ani baze de date
-- - Template-uri import personalizate
-- - Tipuri containere avansate cu imagini
-- - Pavilioane nave cu steaguri
-- - Îmbunătățiri tabele existente
-- =================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =================================================================
-- 1. TABEL DATABASE_YEARS - Gestionare baze pe ani
-- =================================================================
CREATE TABLE IF NOT EXISTS `database_years` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` INT NOT NULL UNIQUE,
  `is_active` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_year` (`year`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserează anul curent ca activ
INSERT IGNORE INTO `database_years` (`year`, `is_active`) VALUES (2025, 1);

-- =================================================================
-- 2. TABEL PAVILIONS - Pavilioane nave cu steaguri
-- =================================================================
CREATE TABLE IF NOT EXISTS `pavilions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Cod pavilion (ex: PA, GR, TR)',
  `country_name` VARCHAR(200) DEFAULT NULL COMMENT 'Nume țară complet',
  `flag_image` VARCHAR(255) DEFAULT NULL COMMENT 'Cale imagine steag',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserează pavilioane inițiale
INSERT IGNORE INTO `pavilions` (`name`, `country_name`) VALUES
('PA', 'Panama'),
('GR', 'Grecia'),
('TR', 'Turcia'),
('CY', 'Cipru'),
('MT', 'Malta'),
('LR', 'Liberia'),
('MH', 'Insulele Marshall'),
('BS', 'Bahamas'),
('BM', 'Bermuda'),
('KY', 'Insulele Cayman');

-- =================================================================
-- 3. ACTUALIZARE TABEL SHIPS - Adaugă relație cu pavilion
-- =================================================================
ALTER TABLE `ships`
  ADD COLUMN IF NOT EXISTS `pavilion_id` INT DEFAULT NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `maritime_line` VARCHAR(255) DEFAULT NULL AFTER `pavilion_id`,
  ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL AFTER `maritime_line`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Adaugă foreign key pentru pavilion (dacă nu există)
ALTER TABLE `ships`
  ADD CONSTRAINT `fk_ships_pavilion`
  FOREIGN KEY IF NOT EXISTS (`pavilion_id`)
  REFERENCES `pavilions`(`id`)
  ON DELETE SET NULL;

-- =================================================================
-- 4. TABEL CONTAINER_TYPES_ADVANCED - Tipuri containere cu imagini
-- =================================================================
-- Redenumim tabelul vechi și creăm unul nou cu structură îmbunătățită
DROP TABLE IF EXISTS `container_types_old`;
RENAME TABLE `container_types` TO `container_types_old`;

CREATE TABLE `container_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `model_code` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Model container (ex: GCXU20G1, TRHU40G1)',
  `type_code` VARCHAR(50) NOT NULL COMMENT 'Tip container (ex: 20G1, 40G1, 45G1)',
  `prefix` VARCHAR(10) NOT NULL COMMENT 'Prefix (ex: GCXU, TRHU, MEDU)',
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL COMMENT 'Cale imagine container',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_model_code` (`model_code`),
  INDEX `idx_type_code` (`type_code`),
  INDEX `idx_prefix` (`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrează datele din tabelul vechi
INSERT INTO `container_types` (`model_code`, `type_code`, `prefix`, `description`, `image`)
SELECT
  CONCAT(`prefix`, `code`) as model_code,
  `code` as type_code,
  `prefix`,
  `description`,
  `image`
FROM `container_types_old`;

-- =================================================================
-- 5. TABEL IMPORT_TEMPLATES - Template-uri import personalizate
-- =================================================================
CREATE TABLE IF NOT EXISTS `import_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `file_format` ENUM('xls', 'xlsx') DEFAULT 'xlsx',
  `start_row` INT DEFAULT 2 COMMENT 'Rândul de start (după header)',
  `column_mapping` JSON NOT NULL COMMENT 'Mapare coloane Excel -> DB {"db_field": "excel_column"}',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserează template implicit
INSERT IGNORE INTO `import_templates` (`name`, `description`, `file_format`, `start_row`, `column_mapping`)
VALUES (
  'Template Standard 2025',
  'Template implicit pentru import Excel format standard',
  'xlsx',
  2,
  JSON_OBJECT(
    'container_number', 'A',
    'container_type', 'B',
    'seal_number', 'C',
    'goods_description', 'D',
    'weight', 'E',
    'shipper', 'F',
    'consignee', 'G',
    'marks_numbers', 'H'
  )
);

-- =================================================================
-- 6. ACTUALIZARE TABEL MANIFEST_ENTRIES - Adaugă câmpuri noi
-- =================================================================
ALTER TABLE `manifest_entries`
  ADD COLUMN IF NOT EXISTS `database_year_id` INT DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `current_number` INT DEFAULT 0 COMMENT 'Număr curent auto-increment' AFTER `database_year_id`,
  ADD COLUMN IF NOT EXISTS `container_type_id` INT DEFAULT NULL AFTER `container_type`,
  ADD COLUMN IF NOT EXISTS `observations` TEXT DEFAULT NULL COMMENT 'Observații' AFTER `country_code`;

-- Adaugă indexuri
ALTER TABLE `manifest_entries`
  ADD INDEX IF NOT EXISTS `idx_database_year` (`database_year_id`),
  ADD INDEX IF NOT EXISTS `idx_current_number` (`current_number`),
  ADD INDEX IF NOT EXISTS `idx_container_type_id` (`container_type_id`);

-- Adaugă foreign keys
ALTER TABLE `manifest_entries`
  ADD CONSTRAINT `fk_entries_database_year`
  FOREIGN KEY IF NOT EXISTS (`database_year_id`)
  REFERENCES `database_years`(`id`)
  ON DELETE SET NULL;

ALTER TABLE `manifest_entries`
  ADD CONSTRAINT `fk_entries_container_type`
  FOREIGN KEY IF NOT EXISTS (`container_type_id`)
  REFERENCES `container_types`(`id`)
  ON DELETE SET NULL;

-- =================================================================
-- 7. ACTUALIZARE TABEL MANIFESTS - Adaugă câmpuri noi
-- =================================================================
ALTER TABLE `manifests`
  ADD COLUMN IF NOT EXISTS `database_year_id` INT DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `permit_number` VARCHAR(100) DEFAULT NULL AFTER `manifest_number`,
  ADD COLUMN IF NOT EXISTS `operation_request` VARCHAR(100) DEFAULT NULL AFTER `permit_number`;

-- Adaugă indexuri
ALTER TABLE `manifests`
  ADD INDEX IF NOT EXISTS `idx_database_year_manifest` (`database_year_id`),
  ADD INDEX IF NOT EXISTS `idx_permit_number` (`permit_number`);

-- Adaugă foreign key
ALTER TABLE `manifests`
  ADD CONSTRAINT `fk_manifests_database_year`
  FOREIGN KEY IF NOT EXISTS (`database_year_id`)
  REFERENCES `database_years`(`id`)
  ON DELETE SET NULL;

-- =================================================================
-- 8. ACTUALIZARE TABEL USERS - Adaugă permisiuni și detalii
-- =================================================================
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(200) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `company_name` VARCHAR(200) DEFAULT NULL AFTER `full_name`,
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1 AFTER `company_name`,
  ADD COLUMN IF NOT EXISTS `is_admin` TINYINT(1) DEFAULT 0 AFTER `is_active`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `last_login`;

-- Actualizează utilizatorul admin existent
UPDATE `users` SET `is_admin` = 1, `is_active` = 1 WHERE `username` = 'admin';

-- =================================================================
-- 9. ACTUALIZARE TABEL IMPORT_LOGS - Adaugă detalii și status
-- =================================================================
ALTER TABLE `import_logs`
  ADD COLUMN IF NOT EXISTS `user_id` INT DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `database_year_id` INT DEFAULT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `template_id` INT DEFAULT NULL AFTER `database_year_id`,
  ADD COLUMN IF NOT EXISTS `status` ENUM('success', 'failed', 'partial') DEFAULT 'success' AFTER `rows_failed`;

-- Adaugă foreign keys
ALTER TABLE `import_logs`
  ADD CONSTRAINT `fk_import_logs_user`
  FOREIGN KEY IF NOT EXISTS (`user_id`)
  REFERENCES `users`(`id`)
  ON DELETE SET NULL;

ALTER TABLE `import_logs`
  ADD CONSTRAINT `fk_import_logs_database_year`
  FOREIGN KEY IF NOT EXISTS (`database_year_id`)
  REFERENCES `database_years`(`id`)
  ON DELETE SET NULL;

ALTER TABLE `import_logs`
  ADD CONSTRAINT `fk_import_logs_template`
  FOREIGN KEY IF NOT EXISTS (`template_id`)
  REFERENCES `import_templates`(`id`)
  ON DELETE SET NULL;

-- =================================================================
-- 10. SETARE VALORI DEFAULT PENTRU DATABASE_YEAR_ID
-- =================================================================
-- Setează anul activ pentru toate intrările existente care nu au database_year_id
UPDATE `manifest_entries`
SET `database_year_id` = (SELECT `id` FROM `database_years` WHERE `is_active` = 1 LIMIT 1)
WHERE `database_year_id` IS NULL;

UPDATE `manifests`
SET `database_year_id` = (SELECT `id` FROM `database_years` WHERE `is_active` = 1 LIMIT 1)
WHERE `database_year_id` IS NULL;

-- =================================================================
-- 11. GENERARE CURRENT_NUMBER PENTRU INTRĂRI EXISTENTE
-- =================================================================
-- Generează numere curente pentru toate intrările existente
SET @row_number = 0;
UPDATE `manifest_entries`
SET `current_number` = (@row_number := @row_number + 1)
ORDER BY `id` ASC;

-- =================================================================
-- FINALIZARE
-- =================================================================
-- Actualizare completă! Baza de date are acum:
-- ✓ Database Years (gestionare ani)
-- ✓ Pavilions (pavilioane cu steaguri)
-- ✓ Container Types avansate (cu imagini)
-- ✓ Import Templates (template-uri personalizate)
-- ✓ Câmpuri noi în manifest_entries (observations, current_number, relații)
-- ✓ Câmpuri noi în manifests (permit_number, operation_request)
-- ✓ Câmpuri noi în users (full_name, company_name, permisiuni)
-- ✓ Câmpuri noi în import_logs (user_id, template_id, status)
-- =================================================================
