-- Rescue Centre Lite diet/feed catalogue upgrade
-- Run this on installs created before the diet catalogue schema was aligned.

ALTER TABLE rescue_diet_items
    CHANGE COLUMN item_name name VARCHAR(190) NOT NULL,
    ADD COLUMN IF NOT EXISTS type VARCHAR(80) NULL AFTER name,
    ADD COLUMN IF NOT EXISTS category VARCHAR(80) NULL AFTER type,
    ADD COLUMN IF NOT EXISTS shelf_life_days INT NULL AFTER default_unit,
    ADD COLUMN IF NOT EXISTS kcal_per_g DECIMAL(10,3) NULL AFTER shelf_life_days,
    ADD COLUMN IF NOT EXISTS kcal_per_ml DECIMAL(10,3) NULL AFTER kcal_per_g,
    ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER kcal_per_ml;

ALTER TABLE rescue_diet_items
    DROP INDEX uq_diet_item,
    ADD UNIQUE KEY uq_diet_item (name),
    ADD INDEX idx_diet_type (type),
    ADD INDEX idx_diet_category (category);
