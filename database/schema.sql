-- Create Database
CREATE DATABASE IF NOT EXISTS expense_tracker;
USE expense_tracker;

-- Create Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64) NULL,
    verification_expires_at DATETIME NULL,
    verified_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add verification columns for existing databases
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_expires_at DATETIME NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL;

-- Create Expenses Table
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Budgets Table
CREATE TABLE IF NOT EXISTS budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    `limit` DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Shared Expenses Table
CREATE TABLE IF NOT EXISTS shared_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_id INT NOT NULL,
    shared_with_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Recurring Expenses Table
CREATE TABLE IF NOT EXISTS recurring_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    frequency ENUM('daily', 'weekly', 'monthly', 'yearly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Alerts Table
CREATE TABLE IF NOT EXISTS alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(10, 2),
    budget_limit DECIMAL(10, 2),
    message TEXT,
    `read` BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample user
INSERT INTO users (name, email, password, email_verified, verified_at) VALUES 
('Demo User', 'demo@example.com', '$2y$10$YlhTOWEvUOy1w7sOe0v5CO3D3V2dkKh4trmCPfPPOHQQfXMh4Aee6', 1, NOW())
ON DUPLICATE KEY UPDATE id=id;

-- Insert sample expenses
INSERT INTO expenses (user_id, amount, category, description, date) VALUES 
(1, 25.50, 'Food', 'Lunch at cafe', CURDATE()),
(1, 15.00, 'Transport', 'Uber', CURDATE()),
(1, 50.00, 'Entertainment', 'Movie tickets', CURDATE()),
(1, 60.00, 'Utilities', 'Electric bill', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 30.00, 'Food', 'Groceries', DATE_SUB(CURDATE(), INTERVAL 2 DAY));

-- Insert sample budgets
INSERT INTO budgets (user_id, category, `limit`) VALUES 
(1, 'Food', 200.00),
(1, 'Transport', 100.00),
(1, 'Entertainment', 150.00),
(1, 'Utilities', 200.00);

-- Create indexes for better performance
CREATE INDEX idx_expenses_user ON expenses(user_id);
CREATE INDEX idx_expenses_date ON expenses(date);
CREATE INDEX idx_budgets_user ON budgets(user_id);
