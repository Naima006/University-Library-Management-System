-- University Library Management System
-- Fresh database setup for MySQL / phpMyAdmin
-- Database: university_library
--
-- WARNING: This script drops existing ULMS tables before recreating them.
-- Use it only for a fresh/reset database.

USE university_library;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS book_issues;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_categories_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE books (
    book_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    isbn VARCHAR(13) NOT NULL,
    published_year SMALLINT UNSIGNED DEFAULT NULL,
    total_copies INT UNSIGNED NOT NULL DEFAULT 0,
    available_copies INT UNSIGNED NOT NULL DEFAULT 0,
    category_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (book_id),
    UNIQUE KEY uq_books_isbn (isbn),
    KEY idx_books_category (category_id),
    KEY idx_books_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE members (
    member_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (member_id),
    UNIQUE KEY uq_members_student_id (student_id),
    KEY idx_members_active_deleted (is_active, is_deleted),
    KEY idx_members_name (first_name, last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE book_issues (
    issue_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    book_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    issued_by INT UNSIGNED NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (issue_id),
    KEY idx_issues_book (book_id),
    KEY idx_issues_member (member_id),
    KEY idx_issues_issued_by (issued_by),
    KEY idx_issues_status (status),
    KEY idx_issues_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    log_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) DEFAULT NULL,
    record_id INT UNSIGNED DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY idx_logs_user (user_id),
    KEY idx_logs_action (action),
    KEY idx_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial administrator account.
-- Email: admin@ulms.com
-- Password: Admin@123
-- Change/reset this password after the first login.
INSERT INTO users
    (full_name, email, password, phone, role, is_active)
VALUES
    (
        'System Administrator',
        'admin@ulms.com',
        '$2y$12$NmrCBf1XYZZY/S8VJqrwY.uk9urt0Nr3IMijmvLxr4rvwV/XT23Ai',
        NULL,
        'admin',
        1
    );
