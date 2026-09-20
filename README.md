# University Library Management System

A web-based Library Management System built with core PHP and MySQL to manage books, categories, members, circulation, staff accounts, reports, and system activity from a single interface.

Live Demo: 

## Overview

The University Library Management System (ULMS) is designed for day-to-day library operations. It provides separate access for administrators and staff members, allowing library records and circulation activities to be managed through a structured web interface.

The system focuses on practical CRUD operations, role-based access control, database-driven workflows, reporting, activity tracking, and responsive user interface design.

## Features

### Authentication and Access Control

- Admin and Staff login roles
- Session-based authentication
- Role-based access to system modules
- Active and inactive user account handling
- Secure password storage using PHP password hashing
- Logout activity tracking

### Admin Dashboard

- Overview of total books and active members
- Currently issued and returned book statistics
- Book availability overview
- Library circulation charts
- Recent book issue records
- Quick access to common library operations

### Staff Dashboard

- Library statistics at a glance
- Current issued and overdue book counts
- Book circulation charts
- Book availability overview
- Recent issue records
- Quick access to circulation and reporting functions

### User Management

Available to administrators:

- Create staff accounts
- Edit user information
- Reset user passwords
- Activate and deactivate accounts
- Search user accounts
- View account roles and status

### Book Management

- Add, edit, search, and delete books
- Store title, author, ISBN, publication year, and copy information
- Assign books to categories
- Track total and available copies
- Prevent deletion of books with active issue records
- Import books from CSV files
- Download and use the provided CSV import template
- Validation and error reporting for invalid import rows

### Category Management

- Add, edit, search, and delete categories
- Display the number of books assigned to each category
- Import categories from CSV files
- Prevent deletion of categories that are still assigned to books

### Member Management

- Add and edit library members
- Store student ID, name, department, email, and phone information
- Search and filter members
- Pagination for larger member lists
- Activate or deactivate member accounts
- Soft delete member records

### Book Issue and Return Management

- Search and select books and members when creating an issue record
- Track issue and due dates
- Validate issue and due dates
- Check book availability before issuing
- Automatically update available book copies
- Record book returns
- Track returned and overdue records
- Record return dates and applicable fine information
- Print issue records as a report

### Reports and Analytics

- Library statistics dashboard
- Total books, members, and circulation records
- Active, returned, and overdue issue counts
- Fine amount summary
- Recent circulation transactions
- Most borrowed books
- Printable library reports

### Activity Logs

Administrators can review system activity including:

- User logins and logouts
- Book operations
- Category operations
- Member operations
- Book issue and return activities
- User management activities
- Search and filter by user, action, and role
- Paginated activity history
- Full descriptions for longer log entries

## Technology Stack

### Backend

- PHP
- MySQL
- Apache

### Frontend

- HTML5
- CSS3
- JavaScript
- Tailwind CSS via CDN
- Font Awesome
- Chart.js
- SweetAlert2

### Development Environment

- XAMPP
- phpMyAdmin
- Visual Studio Code or another PHP-compatible editor

No Composer, Node.js, or frontend build process is required for the current version. The project uses CDN-based frontend libraries.

## Project Structure

```text
University-Library-Management-System/
│
├── admin/
│   ├── dashboard.php
│   └── users/
│       ├── activate.php
│       ├── create.php
│       ├── deactivate.php
│       ├── edit.php
│       ├── index.php
│       └── update.php
│
├── assets/
│   ├── ulms-logo.png
│   ├── favicon.ico
│   └── other favicon assets
│
├── auth/
│   ├── login.php
│   └── logout.php
│
├── books/
│   ├── create.php
│   ├── edit.php
│   ├── index.php
│   ├── save.php
│   ├── update.php
│   ├── delete.php
│   ├── import.php
│   └── import_template.csv
│
├── categories/
│   ├── create.php
│   ├── edit.php
│   ├── index.php
│   ├── save.php
│   ├── update.php
│   ├── delete.php
│   ├── import.php
│   └── import_template.csv
│
├── config/
│   ├── db.php
│   ├── auth.php
│   └── activity_log.php
│
├── issues/
│   ├── create.php
│   ├── index.php
│   ├── store.php
│   ├── return.php
│   └── delete.php
│
├── layouts/
│   └── main_layout.php
│
├── members/
│   ├── create.php
│   ├── edit.php
│   ├── index.php
│   ├── save.php
│   ├── update.php
│   ├── delete.php
│   ├── soft_delete.php
│   └── toggle_status.php
│
├── reports/
│   └── index.php
│
├── staff/
│   └── dashboard.php
│
├── activity_logs/
│   └── index.php
│
├── index.php
└── university_library_setup.sql
```

## Database

The application uses a MySQL database named:

```text
university_library
```

The database contains the following main tables:

| Table | Purpose |
|---|---|
| `users` | Admin and staff accounts |
| `categories` | Book categories |
| `books` | Library book records and copy availability |
| `members` | Student and library member records |
| `book_issues` | Book issue, return, due date, and fine records |
| `activity_logs` | System activity history |

The project includes `university_library_setup.sql` for creating a fresh database structure and an initial administrator account.


## Local Setup

### 1. Install and start XAMPP

Install XAMPP with Apache and MySQL.

Start:

- Apache
- MySQL

### 2. Place the project in htdocs

Copy the project folder into:

```text
C:\xampp\htdocs\university-library-management-system
```

### 3. Create the database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a database named:

```text
university_library
```

### 4. Import the database setup

Open the `university_library` database in phpMyAdmin and run the contents of:

```text
university_library_setup.sql
```

The setup script creates the required tables and inserts the initial administrator account.

### 5. Check the database connection

The current local configuration in `config/db.php` uses:

```php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "university_library";
```

If your MySQL username, password, host, or database name is different, update `config/db.php` accordingly.

### 6. Open the application

Visit:

```text
http://localhost/university-library-management-system/
```

You will be redirected to the login page.

## Initial Admin Account

The fresh database setup creates an administrator account for local development.

```text
Email:    admin@ulms.com
Password: Admin@123
Role:     Admin
```

Change or reset the password after the first login, especially before deploying the application to a public server.

## Typical Workflow

A basic workflow for testing the system is:

1. Log in as an administrator.
2. Create book categories.
3. Add books manually or import them using CSV.
4. Add library members.
5. Create a staff account if required.
6. Issue books to active members.
7. Return issued books.
8. Review reports and circulation statistics.
9. Check activity logs as an administrator.

## CSV Import

The system supports CSV imports for books and categories.

### Book import

Use the provided template:

```text
books/import_template.csv
```

Required columns:

```text
title,author_name,isbn,published_year,total_copies,available_copies,category_name
```

### Category import

Use:

```text
categories/import_template.csv
```

Required column:

```text
category_name
```

The import process validates rows and reports skipped records with the relevant reason.

## Role Overview

| Area | Admin | Staff |
|---|:---:|:---:|
| Dashboard | Yes | Yes |
| Books | Yes | Yes |
| Categories | Yes | Yes |
| Members | Yes | Yes |
| Issue and Return | Yes | Yes |
| Reports | Yes | Yes |
| User Management | Yes | No |
| Activity Logs | Yes | No |
| Issue Record Deletion | Yes | No |
| Member Status Management | Yes | No |

## Application Design

The application follows a simple modular PHP structure. Each major library function is separated into its own module, while shared authentication, database connection, activity logging, and layout functionality are kept under the `config` and `layouts` directories.

The interface is responsive and uses a shared navigation layout for the admin and staff areas. Dashboards use database-driven statistics and charts to provide a quick overview of library activity.

## Security and Data Handling

The project includes several basic application-level security practices:

- Session-based authentication
- Role checks on protected modules
- Password hashing with PHP's `password_hash()` and verification through `password_verify()`
- Prepared statements for database operations where user input is involved
- HTML output escaping with `htmlspecialchars()` in displayed user data
- Server-side validation for important form operations
- Activity logging for important system actions
- Soft deletion for member records

For production deployment, additional hardening should be applied, including environment-based database credentials, HTTPS, stronger session configuration, CSRF protection where appropriate, restricted database permissions, secure server configuration, and removal of development credentials.

## Future Improvements

Possible future improvements include:

- Email-based password recovery
- Fine payment and payment history management
- More advanced search and filtering
- Exporting reports to PDF and Excel
- Automated email reminders for overdue books
- More detailed analytics and usage trends
- Environment-based configuration instead of storing database credentials directly in PHP files
- Automated database backups

## Team

* **Naima Rahman**
* **Zarin Chowdhury**

Developed as a team project during our Software Development Internship at IT Lab Solutions Ltd.

## License

Copyright © 2026 Naima Rahman and Zarin Chowdhury. All rights reserved.

This repository is publicly available for viewing on GitHub, but the source code may not be copied, modified, redistributed, or used in other projects without prior written permission from the author.