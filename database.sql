CREATE DATABASE IF NOT EXISTS password_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE password_manager;

-- Vartotojų lentelė
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    encrypted_key TEXT NOT NULL
) ENGINE=InnoDB;

-- Slaptažodžių lentelė
CREATE TABLE IF NOT EXISTS passwords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    encrypted_password TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;