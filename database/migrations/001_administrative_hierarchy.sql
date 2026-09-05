USE majilink;

CREATE TABLE IF NOT EXISTS administrative_units (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  unit_type ENUM('county','constituency','ward','location','sub_location','village') NOT NULL,
  code VARCHAR(40) NULL,
  name VARCHAR(160) NOT NULL,
  source VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES administrative_units(id) ON DELETE CASCADE,
  UNIQUE KEY uq_admin_unit (parent_id, unit_type, name),
  INDEX idx_admin_children (parent_id, unit_type),
  INDEX idx_admin_name (unit_type, name)
);

ALTER TABLE users ADD COLUMN administrative_unit_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_users_admin_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL;
ALTER TABLE vendors ADD COLUMN administrative_unit_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_vendors_admin_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL;
ALTER TABLE delivery_requests ADD COLUMN administrative_unit_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_deliveries_admin_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL;
ALTER TABLE boreholes ADD COLUMN administrative_unit_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_boreholes_admin_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL;
ALTER TABLE outage_reports ADD COLUMN administrative_unit_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_outages_admin_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL;
ALTER TABLE outage_reports ADD COLUMN latitude DECIMAL(10,7) NULL,
  ADD COLUMN longitude DECIMAL(10,7) NULL;
ALTER TABLE delivery_requests ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
