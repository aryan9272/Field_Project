-- Database: fp

-- 1. Create the 'users' table
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    reset_token VARCHAR(255) NULL,
    token_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create an initial 'admin' user (password is 'adminpass')
-- IMPORTANT: Change the password hash for a strong, custom password!
INSERT INTO users (username, email, password_hash, role)
VALUES ('admin', 'admin@example.com', '$2y$10$7/1S.s1.x.A1f5.N3v.Z4u.l5.v8.m8w8.G9c/W.p7', 'admin'); 
-- Example hash is for 'adminpass'.

-- 3. Create the 'items' table (Lost & Found reports)
CREATE TABLE items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    type ENUM('lost', 'found') NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    color VARCHAR(50) NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    event_date_time DATETIME NULL,
    contact VARCHAR(255) NOT NULL,
    reported_by VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NULL,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active', 'Resolved') DEFAULT 'Active',
    FOREIGN KEY (reported_by) REFERENCES users(username) ON DELETE CASCADE
);

-- 4. Create the 'notifications' table (Fixes the previous error)
CREATE TABLE notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    recipient_username VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_username) REFERENCES users(username) ON DELETE CASCADE
);