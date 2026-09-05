USE majilink;

ALTER TABLE users MODIFY role ENUM('resident','vendor','operator','admin','country_manager','county_manager','constituency_manager','ward_manager') NOT NULL DEFAULT 'resident';
ALTER TABLE delivery_requests ADD COLUMN payment_method ENUM('on_delivery','upfront') NOT NULL DEFAULT 'on_delivery', ADD COLUMN payment_status ENUM('pending','initiated','paid','failed','refunded') NOT NULL DEFAULT 'pending';

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
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id) ON DELETE SET NULL,
  INDEX (period_start, period_end, primary_use)
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
