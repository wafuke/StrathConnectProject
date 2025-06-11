-- 1. Create the database
CREATE DATABASE IF NOT EXISTS strathconnect;

-- 2. Use the database
USE strathconnect;

-- 3. Create the users table

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    user_type ENUM('admin', 'seller', 'buyer') NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
