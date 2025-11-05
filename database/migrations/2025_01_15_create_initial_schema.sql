-- Uma Shakti Dham Database Schema-- Uma Shakti Dham Database SchemaUsers:

-- Consolidated initial migration for fresh installations

-- Compatible with MySQL 5.7+ and MariaDB 10.0+-- Consolidated initial migration for fresh installations- can login and edit their personal information except email



SET NAMES utf8mb4;-- Compatible with MySQL 5.7+ and MariaDB 10.0+- can change password

SET FOREIGN_KEY_CHECKS = 0;

- can enter family information

-- ===========================================

-- ROLES AND USERSSET NAMES utf8mb4;- all phone numbers  should be US phone numbers but store in  E.164 format for any internationalization if needed.

-- ===========================================

SET FOREIGN_KEY_CHECKS = 0;

-- Roles table

CREATE TABLE IF NOT EXISTS roles (- common information for logged in user can have 

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(50) NOT NULL UNIQUE,-- ===========================================    - first name

    level INT NOT NULL DEFAULT 1,

    description TEXT,-- ROLES AND USERS    - last name

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;-- ===========================================    - birth year



-- Users table (authentication and basic profile)    - gender

CREATE TABLE IF NOT EXISTS users (

    id INT AUTO_INCREMENT PRIMARY KEY,-- Roles table    - email address

    name VARCHAR(255) NOT NULL,

    email VARCHAR(255) NOT NULL UNIQUE,CREATE TABLE IF NOT EXISTS roles (    - phone number

    password_hash VARCHAR(255) NOT NULL,

    role_id INT DEFAULT 1,    id INT AUTO_INCREMENT PRIMARY KEY,    - address (street, city, state, zip code)

    email_verified_at TIMESTAMP NULL,

    remember_token VARCHAR(100) NULL,    name VARCHAR(50) NOT NULL UNIQUE,    - occupation

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,    level INT NOT NULL DEFAULT 1,    - business info

    INDEX idx_email (email),

    INDEX idx_role_id (role_id),    description TEXT,

    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP- for family



-- ===========================================) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    - first name

-- FAMILY MANAGEMENT

-- ===========================================    - last name



-- Families table-- Users table (authentication and basic profile)    - birth year

CREATE TABLE IF NOT EXISTS families (

    id INT AUTO_INCREMENT PRIMARY KEY,CREATE TABLE IF NOT EXISTS users (    - gender

    family_name VARCHAR(255),

    address_street VARCHAR(255),    id INT AUTO_INCREMENT PRIMARY KEY,    - email address

    address_city VARCHAR(100),

    address_state VARCHAR(50),    name VARCHAR(255) NOT NULL,    - phone number

    address_zip VARCHAR(20),

    created_by_user_id INT,    email VARCHAR(255) NOT NULL UNIQUE,    - relationship to logged in user (dropdown: self, spouse, child, parent, sibling, other _ please specify)

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_created_by (created_by_user_id),    password_hash VARCHAR(255) NOT NULL,    - occupation

    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    role_id INT DEFAULT 1,    - business info if any



-- Family members table (detailed family information)    email_verified_at TIMESTAMP NULL,

CREATE TABLE IF NOT EXISTS family_members (

    id INT AUTO_INCREMENT PRIMARY KEY,    remember_token VARCHAR(100) NULL,--- sectiion for upcoming events - 

    user_id INT,

    family_id INT,    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,- users can see list of upcoming events

    first_name VARCHAR(100) NOT NULL,

    last_name VARCHAR(100),    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,- see option to sposor event if any upcoming event andn they are interested

    birth_year INT,

    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT 'prefer_not_to_say',    INDEX idx_email (email),- users can register for events

    email VARCHAR(255),

    phone VARCHAR(20),    INDEX idx_role_id (role_id),- users can see their registered events

    relationship ENUM('self', 'spouse', 'child', 'parent', 'sibling', 'other') DEFAULT 'other',

    relationship_other VARCHAR(100),    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL

    occupation VARCHAR(100),

    business_info TEXT,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,## Sponsor

    INDEX idx_user_id (user_id),

    INDEX idx_family_id (family_id),-- ===========================================- can see everything that user can see

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL-- FAMILY MANAGEMENT- can see list of upcoming events, and see button for payment

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================- can see payment history of all sponsoships

-- ===========================================

-- EVENTS AND REGISTRATIONS- can have custom affiliate link to any event if custom discount offered to specific Sponsor

-- ===========================================

-- Families table- can see statistics to event they sponsored, about total registration and the one came from their affiliate links

-- Events table

CREATE TABLE IF NOT EXISTS events (CREATE TABLE IF NOT EXISTS families (

    id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(255) NOT NULL,    id INT AUTO_INCREMENT PRIMARY KEY,## Moderator/Community Member/ Board Member

    description TEXT,

    event_date DATE NOT NULL,    family_name VARCHAR(255),- can see everything that user can see

    location VARCHAR(255),

    max_capacity INT,    address_street VARCHAR(255),- can see list of upcoming events

    registration_deadline DATETIME,

    created_by_user_id INT,    address_city VARCHAR(100),- creative statics page of all users, family, kids, events, sponsoships, payments except

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event_date (event_date),    address_state VARCHAR(50),- can create new event

    INDEX idx_created_by (created_by_user_id),

    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL    address_zip VARCHAR(20),- can update site information

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    created_by_user_id INT,- manual user registration

-- Event tickets table

CREATE TABLE IF NOT EXISTS event_tickets (    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,- edit any user or their family information

    id INT AUTO_INCREMENT PRIMARY KEY,

    event_id INT NOT NULL,    INDEX idx_created_by (created_by_user_id),- send brodcast emails for any event to all users or selected users or sponsors

    name VARCHAR(100) NOT NULL,

    price DECIMAL(10,2) DEFAULT 0.00,    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL- can see payment history of all sponsoships

    is_active BOOLEAN DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;- can see statistics to event they sponsored, about total registration and the one came from their affiliate

    INDEX idx_event_id (event_id),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Family members table (detailed family information)## Admin

-- Coupons table

CREATE TABLE IF NOT EXISTS coupons (CREATE TABLE IF NOT EXISTS family_members (- can see everything that Moderator can see

    id INT AUTO_INCREMENT PRIMARY KEY,

    event_id INT,    id INT AUTO_INCREMENT PRIMARY KEY,- can manage moderators

    code VARCHAR(50) NOT NULL UNIQUE,

    discount_amount DECIMAL(10,2) DEFAULT 0.00,    user_id INT,- can manage sponsors

    is_active BOOLEAN DEFAULT 1,

    expires_at DATETIME,    family_id INT,- can manage users

    usage_limit INT,

    times_used INT DEFAULT 0,    first_name VARCHAR(100) NOT NULL,- can manage events

    one_per_user BOOLEAN DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    last_name VARCHAR(100),

    INDEX idx_event_id (event_id),

    INDEX idx_code (code),    birth_year INT,

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT 'prefer_not_to_say',



-- Event registrations table    email VARCHAR(255),

CREATE TABLE IF NOT EXISTS event_registrations (

    id INT AUTO_INCREMENT PRIMARY KEY,    phone VARCHAR(20),

    user_id INT NOT NULL,

    event_id INT NOT NULL,    relationship ENUM('self', 'spouse', 'child', 'parent', 'sibling', 'other') DEFAULT 'other',-- Consolidated initial schema for a minimal, fresh site

    event_ticket_id INT,

    coupon_id INT,    relationship_other VARCHAR(100),-- Run after creating the database and migrations table.

    guest_count INT DEFAULT 0,

    total_amount DECIMAL(10,2) DEFAULT 0.00,    occupation VARCHAR(100),

    discount_amount DECIMAL(10,2) DEFAULT 0.00,

    final_amount DECIMAL(10,2) DEFAULT 0.00,    business_info TEXT,-- Use utf8mb4 and InnoDB for modern MySQL setups

    registration_date DATE DEFAULT (CURRENT_DATE),

    checked_in BOOLEAN DEFAULT 0,    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,SET NAMES utf8mb4;

    checkin_time DATETIME,

    checked_in_by INT,    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,SET FOREIGN_KEY_CHECKS = 0;

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),    INDEX idx_user_id (user_id),

    INDEX idx_event_id (event_id),

    INDEX idx_registration_date (registration_date),    INDEX idx_family_id (family_id),-- Roles

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,CREATE TABLE IF NOT EXISTS roles (

    FOREIGN KEY (event_ticket_id) REFERENCES event_tickets(id) ON DELETE SET NULL,

    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL  id INT AUTO_INCREMENT PRIMARY KEY,

    FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  name VARCHAR(80) NOT NULL UNIQUE,



-- ===========================================  description TEXT NULL,

-- PAYMENTS AND SPONSORSHIPS

-- ===========================================-- ===========================================  level INT NOT NULL DEFAULT 11,



-- Payments table-- EVENTS AND REGISTRATIONS  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

CREATE TABLE IF NOT EXISTS payments (

    id INT AUTO_INCREMENT PRIMARY KEY,-- ===========================================) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    user_id INT,

    amount DECIMAL(10,2) NOT NULL,

    payment_type ENUM('donation', 'sponsorship', 'event_registration') NOT NULL,

    reference_id INT,-- Events table-- Users: authentication + basic profile columns (merged for simplicity)

    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',

    transaction_id VARCHAR(255),CREATE TABLE IF NOT EXISTS events (CREATE TABLE IF NOT EXISTS users (

    payment_method VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    id INT AUTO_INCREMENT PRIMARY KEY,  id INT AUTO_INCREMENT PRIMARY KEY,

    INDEX idx_user_id (user_id),

    INDEX idx_payment_type (payment_type),    title VARCHAR(255) NOT NULL,  username VARCHAR(80) NOT NULL UNIQUE,

    INDEX idx_reference_id (reference_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL    description TEXT,  email VARCHAR(150) NOT NULL UNIQUE,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    event_date DATE NOT NULL,  password VARCHAR(255) NOT NULL,

-- Sponsorships table

CREATE TABLE IF NOT EXISTS sponsorships (    location VARCHAR(255),  name VARCHAR(200) NULL,

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,    max_capacity INT,  first_name VARCHAR(120) NULL,

    event_id INT,

    amount DECIMAL(10,2) NOT NULL,    registration_deadline DATETIME,  last_name VARCHAR(120) NULL,

    payment_id INT,

    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',    created_by_user_id INT,  phone_e164 VARCHAR(32) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  role_id INT NULL,

    INDEX idx_event_id (event_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,    INDEX idx_event_date (event_date),  -- verification, session and auth helper columns

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,

    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL    INDEX idx_created_by (created_by_user_id),  email_verified_at DATETIME NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL  remember_token VARCHAR(100) NULL,

-- ===========================================

-- AUTHENTICATION AND SECURITY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  last_login_at DATETIME NULL,

-- ===========================================

  is_active TINYINT(1) DEFAULT 1,

-- Password resets table

CREATE TABLE IF NOT EXISTS password_resets (-- Event tickets table  auth_type VARCHAR(32) DEFAULT 'local',

    id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(255) NOT NULL,CREATE TABLE IF NOT EXISTS event_tickets (  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    token VARCHAR(255) NOT NULL UNIQUE,

    expires_at TIMESTAMP NOT NULL,    id INT AUTO_INCREMENT PRIMARY KEY,  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (email),    event_id INT NOT NULL,  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL

    INDEX idx_token (token)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    name VARCHAR(100) NOT NULL,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Sessions table (for database-backed sessions)    price DECIMAL(10,2) DEFAULT 0.00,

CREATE TABLE IF NOT EXISTS sessions (

    id VARCHAR(255) PRIMARY KEY,    is_active BOOLEAN DEFAULT 1,-- Families: household grouping (optional association)

    user_id INT,

    ip_address VARCHAR(45),    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CREATE TABLE IF NOT EXISTS families (

    user_agent TEXT,

    payload LONGTEXT,    INDEX idx_event_id (event_id),  id INT AUTO_INCREMENT PRIMARY KEY,

    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE  family_name VARCHAR(200) NULL,

    INDEX idx_last_activity (last_activity),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  created_by_user_id INT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  address_street VARCHAR(255) NULL,

-- OAuth providers table

CREATE TABLE IF NOT EXISTS user_providers (-- Coupons table  address_city VARCHAR(120) NULL,

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,CREATE TABLE IF NOT EXISTS coupons (  address_state VARCHAR(120) NULL,

    provider VARCHAR(50) NOT NULL,

    provider_user_id VARCHAR(255) NOT NULL,    id INT AUTO_INCREMENT PRIMARY KEY,  address_zip VARCHAR(32) NULL,

    access_token TEXT,

    refresh_token TEXT,    event_id INT,  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    expires_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    code VARCHAR(50) NOT NULL UNIQUE,  CONSTRAINT fk_families_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),    discount_amount DECIMAL(10,2) DEFAULT 0.00,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    UNIQUE KEY unique_provider_user (provider, provider_user_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE    is_active BOOLEAN DEFAULT 1,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    expires_at DATETIME,-- Family members: people associated with families, can be non-users

-- WebAuthn credentials table

CREATE TABLE IF NOT EXISTS webauthn_credentials (    usage_limit INT,CREATE TABLE IF NOT EXISTS family_members (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,    times_used INT DEFAULT 0,  id INT AUTO_INCREMENT PRIMARY KEY,

    credential_id TEXT NOT NULL,

    public_key TEXT NOT NULL,    one_per_user BOOLEAN DEFAULT 0,  family_id INT NULL,

    sign_count BIGINT DEFAULT 0,

    transports TEXT,    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  user_id INT NULL,

    name VARCHAR(100),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    INDEX idx_event_id (event_id),  first_name VARCHAR(100) NOT NULL,

    last_used_at TIMESTAMP NULL,

    INDEX idx_user_id (user_id),    INDEX idx_code (code),  last_name VARCHAR(100) NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE  birth_year SMALLINT NULL,



-- ===========================================) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  gender ENUM('male','female','other','prefer_not_say') DEFAULT 'prefer_not_say',

-- FILE UPLOADS AND MEDIA

-- ===========================================  email VARCHAR(150) NULL,



-- Uploads table (for file metadata)-- Event registrations table  phone_e164 VARCHAR(32) NULL,

CREATE TABLE IF NOT EXISTS uploads (

    id INT AUTO_INCREMENT PRIMARY KEY,CREATE TABLE IF NOT EXISTS event_registrations (  relationship ENUM('self','spouse','child','parent','sibling','brother','sister','father-in-law', 'mother-in-law', 'other') DEFAULT 'other',

    entity_type VARCHAR(50),

    entity_id INT,    id INT AUTO_INCREMENT PRIMARY KEY,  relationship_other VARCHAR(150) NULL,

    filename VARCHAR(255) NOT NULL,

    original_filename VARCHAR(255),    user_id INT NOT NULL,  occupation VARCHAR(150) NULL,

    file_path VARCHAR(500) NOT NULL,

    file_size INT,    event_id INT NOT NULL,  business_info TEXT NULL,

    mime_type VARCHAR(100),

    uploaded_by INT,    event_ticket_id INT,  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_entity (entity_type, entity_id),    coupon_id INT,  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_uploaded_by (uploaded_by),

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL    guest_count INT DEFAULT 0,  CONSTRAINT fk_fm_family FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    total_amount DECIMAL(10,2) DEFAULT 0.00,  CONSTRAINT fk_fm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

-- ===========================================

-- ACTIVITY LOGGING    discount_amount DECIMAL(10,2) DEFAULT 0.00,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================

    final_amount DECIMAL(10,2) DEFAULT 0.00,

-- Activity logs table

CREATE TABLE IF NOT EXISTS activity_logs (    registration_date DATE DEFAULT (CURRENT_DATE),-- Events

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,    checked_in BOOLEAN DEFAULT 0,CREATE TABLE IF NOT EXISTS events (

    action VARCHAR(100) NOT NULL,

    entity_type VARCHAR(50),    checkin_time DATETIME,  id INT AUTO_INCREMENT PRIMARY KEY,

    entity_id INT,

    description TEXT,    checked_in_by INT,  title VARCHAR(255) NOT NULL,

    ip_address VARCHAR(45),

    user_agent TEXT,    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  slug VARCHAR(255) NOT NULL UNIQUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),    INDEX idx_user_id (user_id),  description TEXT NULL,

    INDEX idx_entity (entity_type, entity_id),

    INDEX idx_action (action),    INDEX idx_event_id (event_id),  start_at DATETIME NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;    INDEX idx_registration_date (registration_date),  end_at DATETIME NULL,



-- ===========================================    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,  location VARCHAR(255) NULL,

-- INITIAL DATA SEEDING

-- ===========================================    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,  capacity INT NULL,



-- Insert default roles    FOREIGN KEY (event_ticket_id) REFERENCES event_tickets(id) ON DELETE SET NULL,  price DECIMAL(10,2) DEFAULT 0.00,

INSERT IGNORE INTO roles (id, name, level, description) VALUES

(1, 'user', 1, 'Regular user with basic access'),    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,  sponsorable TINYINT(1) DEFAULT 0,

(2, 'moderator', 5, 'Community moderator with elevated permissions'),

(3, 'admin', 10, 'Administrator with full system access');    FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE SET NULL  created_by_user_id INT NULL,



-- Create default admin user (password: admin123 - CHANGE THIS IN PRODUCTION!)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  -- check-in info (for future mobile/desk check-in flows)

INSERT IGNORE INTO users (id, name, email, password_hash, role_id) VALUES

(1, 'Administrator', 'admin@umashaktidham.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);  checkin_time DATETIME NULL,



SET FOREIGN_KEY_CHECKS = 1;-- ===========================================  checkin_by_user_id INT NULL,

-- PAYMENTS AND SPONSORSHIPS  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

-- ===========================================  CONSTRAINT fk_events_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments table

CREATE TABLE IF NOT EXISTS payments (-- Event registrations

    id INT AUTO_INCREMENT PRIMARY KEY,CREATE TABLE IF NOT EXISTS event_registrations (

    user_id INT,  id INT AUTO_INCREMENT PRIMARY KEY,

    amount DECIMAL(10,2) NOT NULL,  event_id INT NOT NULL,

    payment_type ENUM('donation', 'sponsorship', 'event_registration') NOT NULL,  user_id INT NULL,

    reference_id INT,  family_member_id INT NULL,

    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',  event_ticket_id INT NULL,

    transaction_id VARCHAR(255),  -- guest breakdown: allow registering multiple guests of different age groups

    payment_method VARCHAR(50),  adult_count INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  child_count INT DEFAULT 0,

    INDEX idx_user_id (user_id),  senior_count INT DEFAULT 0,

    INDEX idx_payment_type (payment_type),  total_count INT DEFAULT 0,

    INDEX idx_reference_id (reference_id),  ticket_type VARCHAR(100) NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL  paid TINYINT(1) DEFAULT 0,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,

-- Sponsorships table  CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

CREATE TABLE IF NOT EXISTS sponsorships (  CONSTRAINT fk_reg_family FOREIGN KEY (family_member_id) REFERENCES family_members(id) ON DELETE SET NULL

    id INT AUTO_INCREMENT PRIMARY KEY,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    user_id INT,

    event_id INT,-- Sponsorships (affiliates can be stored inside this table as optional fields)

    amount DECIMAL(10,2) NOT NULL,CREATE TABLE IF NOT EXISTS sponsorships (

    payment_id INT,  id INT AUTO_INCREMENT PRIMARY KEY,

    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',  sponsor_name VARCHAR(255) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  amount DECIMAL(10,2) DEFAULT 0.00,

    INDEX idx_user_id (user_id),  affiliate_url VARCHAR(2048) NULL,

    INDEX idx_event_id (event_id),  notes TEXT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,  created_by_user_id INT NULL,

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL  CONSTRAINT fk_sponsorships_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ===========================================-- Payments ledger: flexible references via reference_type/reference_id

-- AUTHENTICATION AND SECURITYCREATE TABLE IF NOT EXISTS payments (

-- ===========================================  id INT AUTO_INCREMENT PRIMARY KEY,

  payer_user_id INT NULL,

-- Password resets table  payer_family_member_id INT NULL,

CREATE TABLE IF NOT EXISTS password_resets (  amount DECIMAL(12,2) NOT NULL,

    id INT AUTO_INCREMENT PRIMARY KEY,  currency CHAR(3) NOT NULL DEFAULT 'USD',

    email VARCHAR(255) NOT NULL,  method VARCHAR(80) NULL,

    token VARCHAR(255) NOT NULL UNIQUE,  status VARCHAR(40) DEFAULT 'pending',

    expires_at TIMESTAMP NOT NULL,  transaction_id VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  reference_type VARCHAR(80) NULL,

    INDEX idx_email (email),  reference_id INT NULL,

    INDEX idx_token (token)  metadata JSON NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  gateway VARCHAR(100) NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

-- Sessions table (for database-backed sessions)  CONSTRAINT fk_pay_user FOREIGN KEY (payer_user_id) REFERENCES users(id) ON DELETE SET NULL,

CREATE TABLE IF NOT EXISTS sessions (  CONSTRAINT fk_pay_family FOREIGN KEY (payer_family_member_id) REFERENCES family_members(id) ON DELETE SET NULL

    id VARCHAR(255) PRIMARY KEY,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    user_id INT,

    ip_address VARCHAR(45),-- Auxiliary and advanced tables

    user_agent TEXT,

    payload LONGTEXT,-- Password resets

    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,CREATE TABLE IF NOT EXISTS password_resets (

    INDEX idx_user_id (user_id),  email VARCHAR(150) NOT NULL,

    INDEX idx_last_activity (last_activity),  token VARCHAR(255) NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  INDEX (email),

  INDEX (token)

-- OAuth providers table) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_providers (

    id INT AUTO_INCREMENT PRIMARY KEY,-- Sessions (server-side optional sessions table)

    user_id INT NOT NULL,CREATE TABLE IF NOT EXISTS sessions (

    provider VARCHAR(50) NOT NULL,  id VARCHAR(128) PRIMARY KEY,

    provider_user_id VARCHAR(255) NOT NULL,  user_id INT NULL,

    access_token TEXT,  ip_address VARCHAR(45) NULL,

    refresh_token TEXT,  user_agent TEXT NULL,

    expires_at TIMESTAMP NULL,  payload LONGTEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  last_activity TIMESTAMP NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  INDEX (user_id),

    INDEX idx_user_id (user_id),  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

    UNIQUE KEY unique_provider_user (provider, provider_user_id),) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;-- Uploads metadata for files (event photos/videos/docs)

CREATE TABLE IF NOT EXISTS uploads (

-- WebAuthn credentials table  id INT AUTO_INCREMENT PRIMARY KEY,

CREATE TABLE IF NOT EXISTS webauthn_credentials (  entity_type VARCHAR(80) NULL,

    id INT AUTO_INCREMENT PRIMARY KEY,  entity_id INT NULL,

    user_id INT NOT NULL,  type VARCHAR(50) NULL,

    credential_id TEXT NOT NULL,  filename VARCHAR(1024) NOT NULL,

    public_key TEXT NOT NULL,  path VARCHAR(2048) NOT NULL,

    sign_count BIGINT DEFAULT 0,  content_type VARCHAR(255) NULL,

    transports TEXT,  size INT NULL,

    name VARCHAR(100),  uploaded_by_user_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    last_used_at TIMESTAMP NULL,  INDEX (entity_type, entity_id),

    INDEX idx_user_id (user_id),  CONSTRAINT fk_uploads_user FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event ticket types

-- ===========================================CREATE TABLE IF NOT EXISTS event_tickets (

-- FILE UPLOADS AND MEDIA  id INT AUTO_INCREMENT PRIMARY KEY,

-- ===========================================  event_id INT NOT NULL,

  name VARCHAR(150) NOT NULL,

-- Uploads table (for file metadata)  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

CREATE TABLE IF NOT EXISTS uploads (  capacity INT NULL,

    id INT AUTO_INCREMENT PRIMARY KEY,  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    entity_type VARCHAR(50),  CONSTRAINT fk_ticket_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,

    entity_id INT,  INDEX (event_id)

    filename VARCHAR(255) NOT NULL,) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    original_filename VARCHAR(255),

    file_path VARCHAR(500) NOT NULL,-- Coupons / promotions

    file_size INT,CREATE TABLE IF NOT EXISTS coupons (

    mime_type VARCHAR(100),  id INT AUTO_INCREMENT PRIMARY KEY,

    uploaded_by INT,  code VARCHAR(64) NOT NULL UNIQUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  description VARCHAR(255) NULL,

    INDEX idx_entity (entity_type, entity_id),  discount_percent INT NULL,

    INDEX idx_uploaded_by (uploaded_by),  discount_amount DECIMAL(10,2) NULL,

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL  max_uses INT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;  used_count INT DEFAULT 0,

  valid_from DATETIME NULL,

-- ===========================================  valid_until DATETIME NULL,

-- ACTIVITY LOGGING  active TINYINT(1) DEFAULT 1,

-- ===========================================  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table

CREATE TABLE IF NOT EXISTS activity_logs (-- Activity / audit logs

    id BIGINT AUTO_INCREMENT PRIMARY KEY,CREATE TABLE IF NOT EXISTS activity_logs (

    user_id INT,  id BIGINT AUTO_INCREMENT PRIMARY KEY,

    action VARCHAR(100) NOT NULL,  user_id INT NULL,

    entity_type VARCHAR(50),  action VARCHAR(200) NOT NULL,

    entity_id INT,  target_type VARCHAR(80) NULL,

    description TEXT,  target_id INT NULL,

    ip_address VARCHAR(45),  ip_address VARCHAR(45) NULL,

    user_agent TEXT,  meta JSON NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),  INDEX (user_id),

    INDEX idx_entity (entity_type, entity_id),  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

    INDEX idx_action (action),) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;-- OAuth / social provider links

CREATE TABLE IF NOT EXISTS user_providers (

-- ===========================================  id INT AUTO_INCREMENT PRIMARY KEY,

-- INITIAL DATA SEEDING  user_id INT NOT NULL,

-- ===========================================  provider VARCHAR(80) NOT NULL,

  provider_user_id VARCHAR(255) NULL,

-- Insert default roles  access_token TEXT NULL,

INSERT IGNORE INTO roles (id, name, level, description) VALUES  refresh_token TEXT NULL,

(1, 'user', 1, 'Regular user with basic access'),  expires_at DATETIME NULL,

(2, 'moderator', 5, 'Community moderator with elevated permissions'),  profile JSON NULL,

(3, 'admin', 10, 'Administrator with full system access');  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX (user_id),

-- Create default admin user (password: admin123 - CHANGE THIS IN PRODUCTION!)  UNIQUE KEY ux_provider_user (provider, provider_user_id),

INSERT IGNORE INTO users (id, name, email, password_hash, role_id) VALUES  CONSTRAINT fk_user_providers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

(1, 'Administrator', 'admin@umashaktidham.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



SET FOREIGN_KEY_CHECKS = 1;-- WebAuthn / passkey credentials
CREATE TABLE IF NOT EXISTS webauthn_credentials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  credential_id VARBINARY(512) NOT NULL,
  public_key BLOB NOT NULL,
  sign_count BIGINT DEFAULT 0,
  transports VARCHAR(255) NULL,
  name VARCHAR(150) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME NULL,
  INDEX (user_id),
  UNIQUE KEY ux_user_credential (user_id, credential_id),
  CONSTRAINT fk_webauthn_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
