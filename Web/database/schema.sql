-- Rescue Centre Lite schema
-- Multi-user, single-centre local install.

CREATE TABLE IF NOT EXISTS rescue_centres (
    rescue_id INT AUTO_INCREMENT PRIMARY KEY,
    rescue_name VARCHAR(190) NOT NULL,
    centre_type VARCHAR(80) NULL,
    email VARCHAR(190) NULL,
    office_tel VARCHAR(60) NULL,
    mobile VARCHAR(60) NULL,
    `24_hour` VARCHAR(60) NULL,
    address_line_one VARCHAR(190) NULL,
    address_line_two VARCHAR(190) NULL,
    city VARCHAR(120) NULL,
    county VARCHAR(120) NULL,
    postcode VARCHAR(30) NULL,
    country_code CHAR(2) NULL,
    coordinates VARCHAR(80) NULL,
    centre_lat DECIMAL(10,7) NULL,
    centre_long DECIMAL(10,7) NULL,
    accepting_admissions VARCHAR(30) NOT NULL DEFAULT 'Yes',
    closed_message TEXT NULL,
    species_accepted TEXT NULL,
    opening_hours TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_centre_meta (
    meta_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    centre_bio TEXT NULL,
    centre_logo VARCHAR(255) NULL,
    centre_profile_image VARCHAR(255) NULL,
    centre_cover_image VARCHAR(255) NULL,
    cover_offset INT NOT NULL DEFAULT 0,
    handover_declaration_text TEXT NULL,
    custom_colour CHAR(7) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_centre_meta_centre (centre_id),
    CONSTRAINT fk_centre_meta_centre FOREIGN KEY (centre_id) REFERENCES rescue_centres(rescue_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'Member',
    rescue_role INT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    activation_code VARCHAR(100) NOT NULL DEFAULT 'activated',
    approved TINYINT(1) NOT NULL DEFAULT 1,
    dark_mode TINYINT(1) NOT NULL DEFAULT 0,
    remember_me_code VARCHAR(255) NULL,
    last_seen DATETIME NULL,
    registered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_accounts_username (username),
    UNIQUE KEY uq_accounts_email (email),
    INDEX idx_accounts_centre (centre_id),
    CONSTRAINT fk_accounts_centre FOREIGN KEY (centre_id) REFERENCES rescue_centres(rescue_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rescue_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    centre_id INT NOT NULL,
    role_name VARCHAR(120) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rescue_role (centre_id, role_name),
    CONSTRAINT fk_rescue_roles_centre FOREIGN KEY (centre_id) REFERENCES rescue_centres(rescue_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lite_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
