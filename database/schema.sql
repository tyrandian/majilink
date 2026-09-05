CREATE DATABASE IF NOT EXISTS majilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE majilink;

CREATE TABLE administrative_units (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  unit_type ENUM('county','sub_county','constituency','ward','location','sub_location','village') NOT NULL,
  code VARCHAR(40) NULL,
  name VARCHAR(160) NOT NULL,
  source VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES administrative_units(id) ON DELETE CASCADE,
  UNIQUE KEY uq_admin_unit (parent_id, unit_type, name),
  INDEX idx_admin_children (parent_id, unit_type),
  INDEX idx_admin_name (unit_type, name)
);

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(190) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('resident','vendor','operator','admin','country_manager','county_manager','constituency_manager','ward_manager') NOT NULL DEFAULT 'resident',
  county VARCHAR(80) NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL
);

CREATE TABLE api_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id, expires_at)
);

CREATE TABLE vendors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  business_name VARCHAR(160) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  county VARCHAR(80) NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  service_radius_km DECIMAL(6,2) NOT NULL DEFAULT 15,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','paused','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL
);

CREATE TABLE delivery_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id INT UNSIGNED NOT NULL,
  vendor_id INT UNSIGNED NULL,
  county VARCHAR(80) NOT NULL,
  location_text VARCHAR(255) NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  litres INT UNSIGNED NOT NULL,
  preferred_date DATE NOT NULL,
  notes TEXT,
  status ENUM('open','assigned','en_route','delivered','cancelled') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  payment_method ENUM('on_delivery','upfront') NOT NULL DEFAULT 'on_delivery',
  payment_status ENUM('pending','initiated','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (resident_id) REFERENCES users(id),
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL,
  INDEX (county, status)
);

CREATE TABLE boreholes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  county VARCHAR(80) NOT NULL,
  location_text VARCHAR(255) NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  status ENUM('working','limited','offline','unknown') NOT NULL DEFAULT 'unknown',
  operator_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (operator_id) REFERENCES users(id),
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL,
  INDEX (county, status)
);

CREATE TABLE outage_reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT UNSIGNED NOT NULL,
  county VARCHAR(80) NOT NULL,
  area VARCHAR(160) NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  description TEXT NOT NULL,
  status ENUM('reported','under_review','confirmed','resolved','rejected') NOT NULL DEFAULT 'reported',
  reviewed_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL,
  INDEX (county, status)
);

CREATE TABLE manager_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  scope_unit_id BIGINT UNSIGNED NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scope_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL
);

CREATE TABLE water_utilities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  county VARCHAR(80) NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(190) NULL,
  website VARCHAR(255) NULL,
  billing_cycle ENUM('weekly','monthly') NOT NULL DEFAULT 'monthly',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL
);

CREATE TABLE water_coverage (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utility_id INT UNSIGNED NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NOT NULL,
  connected_households INT UNSIGNED NOT NULL DEFAULT 0,
  total_households INT UNSIGNED NOT NULL DEFAULT 0,
  source_type VARCHAR(120) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (utility_id) REFERENCES water_utilities(id) ON DELETE CASCADE,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE CASCADE
);

CREATE TABLE water_usage (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  litres DECIMAL(12,2) NOT NULL DEFAULT 0,
  primary_use ENUM('farming','home','irrigation','business','other') NOT NULL DEFAULT 'home',
  source_type VARCHAR(120) NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  delivery_id BIGINT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  method ENUM('mpesa','cash','card','on_delivery') NOT NULL,
  status ENUM('pending','initiated','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  provider_reference VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (delivery_id) REFERENCES delivery_requests(id) ON DELETE SET NULL
);

CREATE TABLE bills (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  utility_id INT UNSIGNED NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  litres DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('draft','issued','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  due_date DATE NULL,
  sent_email_at DATETIME NULL,
  sent_whatsapp_at DATETIME NULL,
  sent_sms_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (utility_id) REFERENCES water_utilities(id) ON DELETE SET NULL
);

CREATE TABLE incidents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT UNSIGNED NOT NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  county VARCHAR(80) NOT NULL,
  category ENUM('dirty_water','poisonous_water','low_pressure','outage','leak','other') NOT NULL,
  description TEXT NOT NULL,
  status ENUM('reported','under_review','confirmed','resolved','rejected') NOT NULL DEFAULT 'reported',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id),
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_user_id INT UNSIGNED NULL,
  administrative_unit_id BIGINT UNSIGNED NULL,
  channel ENUM('sms','whatsapp','email','in_app') NOT NULL,
  subject VARCHAR(190) NULL,
  message TEXT NOT NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  scheduled_for DATETIME NULL,
  sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL
);
