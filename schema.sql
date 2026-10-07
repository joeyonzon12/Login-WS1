-- IMPORTANT:
-- In InfinityFree phpMyAdmin, select:
-- if0_42933422_Login_WS1
-- before importing this file.
--
-- Do NOT use CREATE DATABASE or USE.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'staff') NOT NULL DEFAULT 'user',
    gender VARCHAR(30) NULL,
    birth_date DATE NULL,
    language VARCHAR(30) NOT NULL DEFAULT 'English',
    country VARCHAR(60) NULL,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    private_account TINYINT(1) NOT NULL DEFAULT 0,
    avatar_path VARCHAR(255) NULL,
    created_at VARCHAR(25) NOT NULL,
    updated_at VARCHAR(25) NOT NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(255) NOT NULL,
    created_at VARCHAR(25) NOT NULL,

    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_activity_created_at (created_at),
    INDEX idx_activity_user_id (user_id)
) ENGINE=InnoDB;


-- Default administrator
-- Username: admin
-- Password: Admin@12345

INSERT INTO users (
    full_name,
    email,
    username,
    password_hash,
    role,
    created_at,
    updated_at
)
VALUES (
    'System Administrator',
    'admin@example.com',
    'admin',
    '$2y$10$XGb3cCFV97ZYJqyw.S0Zn.u0c0UK4leuSpWjynpbfuuhRTGsp6OAm',
    'admin',
    DATE_FORMAT(NOW(), '%Y-%m-%d %l:%i%p'),
    DATE_FORMAT(NOW(), '%Y-%m-%d %l:%i%p')
)
ON DUPLICATE KEY UPDATE
    username = username;