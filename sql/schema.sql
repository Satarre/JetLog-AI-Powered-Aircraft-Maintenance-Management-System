-- JetLog database schema
-- Import this file first, then seed.sql.

CREATE DATABASE IF NOT EXISTS jetlog
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE jetlog;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ai_summaries;
DROP TABLE IF EXISTS logbook_entries;
DROP TABLE IF EXISTS maintenance_schedule;
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS discrepancies;
DROP TABLE IF EXISTS aircraft;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'technician',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE aircraft (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration VARCHAR(20) NOT NULL UNIQUE,
    aircraft_type VARCHAR(60) NOT NULL,
    msn VARCHAR(30) NOT NULL UNIQUE,
    base VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'in_service',
    total_hours DECIMAL(10,1) NOT NULL DEFAULT 0.0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE discrepancies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aircraft_id INT UNSIGNED NOT NULL,
    reported_by INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    severity VARCHAR(10) NOT NULL DEFAULT 'medium',
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    reported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    INDEX idx_discrepancy_status (status),
    INDEX idx_discrepancy_severity (severity),
    CONSTRAINT fk_discrepancy_aircraft FOREIGN KEY (aircraft_id) REFERENCES aircraft (id),
    CONSTRAINT fk_discrepancy_user FOREIGN KEY (reported_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE work_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wo_number VARCHAR(20) NULL UNIQUE,
    aircraft_id INT UNSIGNED NOT NULL,
    discrepancy_id INT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority VARCHAR(10) NOT NULL DEFAULT 'medium',
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_work_order_status (status),
    CONSTRAINT fk_work_order_aircraft FOREIGN KEY (aircraft_id) REFERENCES aircraft (id),
    CONSTRAINT fk_work_order_discrepancy FOREIGN KEY (discrepancy_id) REFERENCES discrepancies (id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_assignee FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_author FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE maintenance_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aircraft_id INT UNSIGNED NOT NULL,
    task_code VARCHAR(30) NOT NULL,
    title VARCHAR(150) NOT NULL,
    task_type VARCHAR(20) NOT NULL DEFAULT 'inspection',
    interval_days INT UNSIGNED NOT NULL,
    last_done_date DATE NULL,
    next_due_date DATE NOT NULL,
    notes TEXT NULL,
    INDEX idx_schedule_due (next_due_date),
    CONSTRAINT fk_schedule_aircraft FOREIGN KEY (aircraft_id) REFERENCES aircraft (id)
) ENGINE=InnoDB;

CREATE TABLE logbook_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aircraft_id INT UNSIGNED NOT NULL,
    work_order_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    entry_date DATE NOT NULL,
    entry_type VARCHAR(20) NOT NULL DEFAULT 'note',
    description TEXT NOT NULL,
    performed_by VARCHAR(100) NOT NULL,
    flight_hours DECIMAL(10,1) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logbook_date (entry_date),
    CONSTRAINT fk_logbook_aircraft FOREIGN KEY (aircraft_id) REFERENCES aircraft (id),
    CONSTRAINT fk_logbook_work_order FOREIGN KEY (work_order_id) REFERENCES work_orders (id) ON DELETE SET NULL,
    CONSTRAINT fk_logbook_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE ai_summaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(20) NOT NULL,
    aircraft_id INT UNSIGNED NULL,
    summary_text TEXT NOT NULL,
    model VARCHAR(60) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_summary_scope (scope, aircraft_id),
    CONSTRAINT fk_summary_aircraft FOREIGN KEY (aircraft_id) REFERENCES aircraft (id) ON DELETE SET NULL,
    CONSTRAINT fk_summary_user FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;
