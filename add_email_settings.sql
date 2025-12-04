-- Creare tabel pentru setări email SMTP
CREATE TABLE IF NOT EXISTS email_settings (
    id INT PRIMARY KEY DEFAULT 1,
    smtp_host VARCHAR(255) NOT NULL DEFAULT '',
    smtp_port INT NOT NULL DEFAULT 465,
    smtp_username VARCHAR(255) NOT NULL DEFAULT '',
    smtp_password VARCHAR(255) NOT NULL DEFAULT '',
    smtp_encryption ENUM('ssl', 'tls', 'none') DEFAULT 'ssl',
    from_email VARCHAR(255) NOT NULL DEFAULT '',
    from_name VARCHAR(255) DEFAULT 'Registru RE1',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserare setări default (se pot modifica din admin)
INSERT INTO email_settings (id, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name, is_active)
VALUES (1, 'mail.lentiu.ro', 465, 'admin@lentiu.ro', '', 'ssl', 'admin@lentiu.ro', 'Registru RE1', 0)
ON DUPLICATE KEY UPDATE id = id;
