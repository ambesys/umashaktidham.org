CREATE TABLE families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    family_member_name VARCHAR(255) NOT NULL,
    relationship ENUM('Spouse', 'Child', 'Parent', 'Sibling', 'Other') NOT NULL,
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);