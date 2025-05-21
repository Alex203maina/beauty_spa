-- Create database
CREATE DATABASE IF NOT EXISTS spa_beauty_system;
USE spa_beauty_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('client', 'salonist', 'admin') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `category` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `duration` INT NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample services
INSERT INTO `services` (`name`, `description`, `category`, `price`, `duration`, `status`) VALUES
('Swedish Massage', 'Relaxing full body massage', 'Massage', 80.00, 60, 'active'),
('Facial Treatment', 'Deep cleansing facial with mask', 'Facial', 65.00, 45, 'active'),
('Manicure', 'Basic nail care and polish', 'Nails', 35.00, 30, 'active'),
('Pedicure', 'Foot care and polish', 'Nails', 45.00, 45, 'active'),
('Haircut', 'Basic haircut and styling', 'Hair', 40.00, 30, 'active'),
('Hair Coloring', 'Full hair color treatment', 'Hair', 90.00, 120, 'active'),
('Hot Stone Massage', 'Therapeutic massage with hot stones', 'Massage', 100.00, 90, 'active'),
('Deep Tissue Massage', 'Intensive muscle treatment', 'Massage', 90.00, 60, 'active'),
('Waxing', 'Full leg waxing', 'Hair Removal', 50.00, 45, 'active'),
('Makeup Application', 'Full face makeup', 'Makeup', 60.00, 45, 'active');

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    salonist_id INT NOT NULL,
    service_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (salonist_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Receipts table
CREATE TABLE IF NOT EXISTS receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    client_id INT NOT NULL,
    salonist_id INT NOT NULL,
    service_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (salonist_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Insert default admin user
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@spa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Note: The password hash above is for 'password' 