-- Event Volunteer Management System / CIS 344 Fall 2026
-- Import this file in phpMyAdmin (SQL tab) or the mysql command line.
CREATE DATABASE IF NOT EXISTS event_volunteer_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_volunteer_db;

CREATE TABLE IF NOT EXISTS admins (
  admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS volunteers (
  volunteer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  phone VARCHAR(25) DEFAULT NULL,
  skills VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (first_name <> '' AND last_name <> '')
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
  event_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  event_date DATE NOT NULL,
  location VARCHAR(160) NOT NULL,
  capacity INT UNSIGNED NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (capacity > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
  assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  volunteer_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  role_name VARCHAR(80) NOT NULL,
  assignment_status ENUM('Registered','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Registered',
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_volunteer_event(volunteer_id, event_id),
  INDEX idx_assignments_event(event_id),
  CONSTRAINT fk_assignments_volunteer FOREIGN KEY (volunteer_id) REFERENCES volunteers(volunteer_id) ON DELETE CASCADE,
  CONSTRAINT fk_assignments_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB;
