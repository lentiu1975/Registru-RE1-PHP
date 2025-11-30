-- Adaugă câmpuri pentru nave în manifest_entries

ALTER TABLE manifest_entries
ADD COLUMN ship_name VARCHAR(200) DEFAULT NULL COMMENT 'Nume navă',
ADD COLUMN ship_flag VARCHAR(100) DEFAULT NULL COMMENT 'Pavilion navă';
