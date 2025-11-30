-- Adaugă coloanele lipsă în tabela manifest_entries

ALTER TABLE manifest_entries
ADD COLUMN packages INT DEFAULT NULL COMMENT 'Număr colete',
ADD COLUMN summary_number VARCHAR(100) DEFAULT NULL COMMENT 'Număr sumară',
ADD COLUMN operation_type VARCHAR(1) DEFAULT 'I' COMMENT 'Tip operațiune: I=Import, E=Export';
