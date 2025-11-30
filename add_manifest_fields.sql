-- Adaugă câmpurile lipsă pentru format manifest complet

ALTER TABLE manifest_entries
ADD COLUMN permit_number VARCHAR(100) DEFAULT NULL COMMENT 'Număr permis',
ADD COLUMN position_number VARCHAR(50) DEFAULT NULL COMMENT 'Număr poziție',
ADD COLUMN operation_request VARCHAR(100) DEFAULT NULL COMMENT 'Cerere operațiune';
