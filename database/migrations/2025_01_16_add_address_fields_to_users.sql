-- Add address fields to users table for main user profile
ALTER TABLE users 
ADD COLUMN street_address VARCHAR(255) NULL AFTER last_name,
ADD COLUMN address_line_2 VARCHAR(255) NULL AFTER street_address,
ADD COLUMN zipcode VARCHAR(10) NULL AFTER address_line_2,
ADD COLUMN city VARCHAR(100) NULL AFTER zipcode,
ADD COLUMN state VARCHAR(50) NULL AFTER city,
ADD COLUMN country VARCHAR(100) DEFAULT 'USA' AFTER state;
