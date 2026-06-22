-- Rescue Centre Lite medication catalogue upgrade
-- Run this on installs created before the medication catalogue schema was aligned.

ALTER TABLE rescue_medications
    CHANGE COLUMN med_profile_id medication_id INT AUTO_INCREMENT,
    CHANGE COLUMN medication medication_name VARCHAR(190) NOT NULL,
    ADD COLUMN IF NOT EXISTS common_name VARCHAR(190) NULL AFTER medication_name,
    ADD COLUMN IF NOT EXISTS class VARCHAR(120) NULL AFTER common_name,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER class,
    ADD COLUMN IF NOT EXISTS contraindications TEXT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS cautions TEXT NULL AFTER contraindications,
    ADD COLUMN IF NOT EXISTS dose VARCHAR(120) NULL AFTER cautions,
    ADD COLUMN IF NOT EXISTS side_effects TEXT NULL AFTER route;

ALTER TABLE rescue_stock_medication
    CHANGE COLUMN stock_item_id medication_profile_id INT AUTO_INCREMENT,
    MODIFY COLUMN medication INT NULL;

ALTER TABLE rescue_medications
    DROP INDEX uq_medication_name,
    ADD UNIQUE KEY uq_medication_name (medication_name),
    ADD INDEX idx_medication_class (class);
