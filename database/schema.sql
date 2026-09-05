CREATE DATABASE IF NOT EXISTS majilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE majilink;

CREATE TABLE administrative_units (
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

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(190) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('resident','vendor','operator','admin') NOT NULL DEFAULT 'resident',
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
