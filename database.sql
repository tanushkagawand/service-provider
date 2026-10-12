-- ============================================================================
-- LOCAL SERVICE FINDER - DATABASE SCHEMA & SAMPLE DATA
-- Designed for MySQL / MariaDB (Compatible with XAMPP / Apache / phpMyAdmin)
-- Database Name: local_service_finder
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `local_service_finder` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `local_service_finder`;

-- ----------------------------------------------------------------------------
-- 1. Table: users
-- Roles: 'customer', 'provider', 'admin'
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `providers`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'provider', 'admin') NOT NULL DEFAULT 'customer',
  `address` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2. Table: services
-- Standard local service categories
-- ----------------------------------------------------------------------------
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 3. Table: providers
-- Profiles for registered service providers
-- ----------------------------------------------------------------------------
CREATE TABLE `providers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `service_id` INT DEFAULT NULL,
  `business_name` VARCHAR(150) NOT NULL,
  `experience` VARCHAR(50) DEFAULT '2 years',
  `fee` DECIMAL(10,2) DEFAULT 350.00,
  `availability` VARCHAR(100) DEFAULT 'Mon - Sat (9:00 AM - 7:00 PM)',
  `description` TEXT DEFAULT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rating` DECIMAL(3,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4. Table: bookings
-- Service bookings by customers
-- ----------------------------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `provider_id` INT NOT NULL,
  `service_id` INT DEFAULT NULL,
  `booking_date` DATE NOT NULL,
  `booking_time` TIME NOT NULL,
  `address` TEXT NOT NULL,
  `problem_description` TEXT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 5. Table: reviews
-- Customer feedback after a service is marked completed
-- ----------------------------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `provider_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`provider_id`) REFERENCES `providers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 6. Table: contacts (Inquiries)
-- Messages submitted through the contact page
-- ----------------------------------------------------------------------------
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SEED DATA
-- Default Passwords:
-- Admin: admin@example.com / admin123
-- All other test accounts: password123
-- ============================================================================

-- 1. Insert Services
INSERT INTO `services` (`id`, `name`, `description`, `category`) VALUES
(1, 'Electrician', 'Wiring, lighting fixtures, circuit breakers, switches, and appliance installations.', 'Home Repairs'),
(2, 'Plumber', 'Leak repairs, pipe installations, drainage unclogging, and tap replacements.', 'Home Repairs'),
(3, 'Carpenter', 'Furniture assembly, wooden door repairs, custom shelves, and cabinetry work.', 'Woodwork'),
(4, 'Mechanic', 'On-site two-wheeler and car diagnostics, battery jumps, and minor engine fixes.', 'Automotive'),
(5, 'Tutor', 'Maths, Science, English, and primary-secondary school academic assistance.', 'Education'),
(6, 'Cleaner', 'Deep house cleaning, kitchen scrubbing, bathroom sanitization, and sofa washing.', 'Cleaning'),
(7, 'AC & Appliance Repair', 'Air conditioner servicing, gas refilling, refrigerator and washing machine fixes.', 'Appliance');

-- 2. Insert Users
-- Hash for 'admin123': $2y$10$3ec.OkcOIw/4YBqo5Lw6RO6JBEdqqmlBauNYtOkayriZCTqntLehG
-- Hash for 'password123': $2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `address`) VALUES
(1, 'Administrator', 'admin@example.com', '9876543210', '$2y$10$3ec.OkcOIw/4YBqo5Lw6RO6JBEdqqmlBauNYtOkayriZCTqntLehG', 'admin', 'LSF Administration Office, Central City'),
(2, 'Rahul Sharma', 'electrician@example.com', '9820112233', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', 'Shop 4, Market Complex, Sector 12, Mumbai'),
(3, 'Amit Verma', 'plumber@example.com', '9820223344', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', '14 Navjeevan Society, Andheri West, Mumbai'),
(4, 'Suresh Patel', 'carpenter@example.com', '9820334455', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', '201 Wood Crafts, Link Road, Borivali, Mumbai'),
(5, 'Anita Desai', 'tutor@example.com', '9820445566', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', 'B-12 Greenwood Apartments, Bandra, Mumbai'),
(6, 'Sunita Rao', 'cleaner@example.com', '9820556677', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', 'Flat 5, Sunshine Colony, Thane'),
(7, 'Vikram Singh', 'repair@example.com', '9820667788', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', 'G-8 Appliance Hub, Powai, Mumbai'),
(8, 'Ramesh Auto Care', 'pendingprovider@example.com', '9820778899', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'provider', 'Highway Service Station, Kurla, Mumbai'),
(9, 'John Customer', 'customer@example.com', '9811001122', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'customer', 'Flat 402, Sea View Residency, Bandra West, Mumbai'),
(10, 'Priya Mehta', 'priya@example.com', '9811334455', '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy', 'customer', '12 Rose Villa, Dadar East, Mumbai');

-- 3. Insert Providers
INSERT INTO `providers` (`id`, `user_id`, `service_id`, `business_name`, `experience`, `fee`, `availability`, `description`, `verification_status`, `rating`) VALUES
(1, 2, 1, 'Sharma Electrical Works', '6 years', 400.00, 'Mon - Sat (9:00 AM - 8:00 PM)', 'Certified local electrician specializing in safety inspections, LED lights, fan fittings, rewiring, and short circuit repairs.', 'approved', 4.80),
(2, 3, 2, 'Apex Plumbing Solutions', '8 years', 450.00, 'Daily (8:00 AM - 9:00 PM)', 'Expert plumbing assistance for kitchen sinks, bathroom pipe leakages, water tank overflow, and geyser installations.', 'approved', 4.90),
(3, 4, 3, 'Classic Carpentry Services', '5 years', 500.00, 'Mon - Fri (10:00 AM - 6:00 PM)', 'Quality woodwork, hinges repairs, custom wooden shelving, furniture assembly and lock installations.', 'approved', 4.70),
(4, 5, 5, 'Bright Future Home Tutoring', '4 years', 600.00, 'Mon - Fri (4:00 PM - 8:00 PM)', 'Dedicated private tutor for Mathematics and Science from Grade 6 to Grade 10. Patient and concept-oriented teaching.', 'approved', 5.00),
(5, 6, 6, 'Sparkle Home Cleaners', '3 years', 800.00, 'Mon - Sat (8:30 AM - 5:30 PM)', 'Thorough domestic cleaning including deep kitchen cleaning, floor buffing, bathroom sanitization and balcony washing.', 'approved', 4.50),
(6, 7, 7, 'Cool Breeze AC & Appliance Fix', '7 years', 550.00, 'Daily (9:00 AM - 7:00 PM)', 'Prompt diagnostics and repair for split AC units, window ACs, refrigerators, and automatic washing machines.', 'approved', 4.60),
(7, 8, 4, 'Ramesh Garage & Mechanic', '2 years', 350.00, 'Mon - Sat (9:00 AM - 6:00 PM)', 'Roadside breakdown assistance, bike servicing, car battery replacement and minor puncture/mechanical work.', 'pending', 0.00);

-- 4. Insert Sample Bookings
INSERT INTO `bookings` (`id`, `customer_id`, `provider_id`, `service_id`, `booking_date`, `booking_time`, `address`, `problem_description`, `status`) VALUES
(1, 9, 1, 1, '2026-10-10', '11:00:00', 'Flat 402, Sea View Residency, Bandra West, Mumbai', 'Master bedroom ceiling fan is wobbling and main switchboard sparks occasionally.', 'completed'),
(2, 9, 2, 2, '2026-10-14', '15:30:00', 'Flat 402, Sea View Residency, Bandra West, Mumbai', 'Under-sink kitchen drainage pipe is dripping water onto the floor cabinet.', 'accepted'),
(3, 10, 1, 1, '2026-10-15', '10:00:00', '12 Rose Villa, Dadar East, Mumbai', 'Need installation of two new LED tube lights in living room.', 'pending');

-- 5. Insert Sample Reviews
INSERT INTO `reviews` (`id`, `booking_id`, `customer_id`, `provider_id`, `rating`, `review`) VALUES
(1, 1, 9, 1, 5, 'Rahul arrived right on time, identified the loose wiring in the switchboard quickly, and fixed the fan mounting. Highly professional!');

-- 6. Insert Sample Contact Messages
INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`) VALUES
(1, 'Kunal Kapoor', 'kunal@example.com', 'Joining as a provider', 'Hello, I run a pest control service in Mumbai. How long does the verification process take once I register?'),
(2, 'Sneha Joshi', 'sneha@example.com', 'Website question', 'Is there any extra booking fee charged by the platform or do we pay the provider directly?');
