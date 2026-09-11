-- --------------------------------------------------------
-- FarmFlow Database Schema & Sample Data for XAMPP
-- --------------------------------------------------------


-- --------------------------------------------------------
-- 1. Users Table
-- Stores credentials and language preferences
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(15) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `preferred_language` VARCHAR(10) DEFAULT 'en', -- 'en', 'hi', 'mr'
  `farm_location` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Crops Table
-- Tracks active and past crops per farmer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `crops` (
  `crop_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `crop_name` VARCHAR(100) NOT NULL,
  `field_area_acres` DECIMAL(5,2) NOT NULL,
  `sowing_date` DATE NOT NULL,
  `expected_harvest_date` DATE DEFAULT NULL,
  `status` ENUM('Growing', 'Harvested', 'Failed') DEFAULT 'Growing',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Irrigation & Fertilizer Schedules Table
-- Automates reminders for watering and field care
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedules` (
  `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
  `crop_id` INT NOT NULL,
  `task_type` ENUM('Irrigation', 'Fertilizer', 'Pesticide', 'Other') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `scheduled_date` DATE NOT NULL,
  `status` ENUM('Pending', 'Completed', 'Skipped') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`crop_id`) REFERENCES `crops`(`crop_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Farm Financials Table
-- Tracks expenses (seeds, labor, fuel) & revenues
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `financials` (
  `financial_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `crop_id` INT DEFAULT NULL, -- Nullable if general farm expense
  `type` ENUM('Expense', 'Income') NOT NULL,
  `category` VARCHAR(50) NOT NULL, -- e.g., 'Seeds', 'Labor', 'Equipment', 'Crop Sale'
  `amount` DECIMAL(10,2) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`crop_id`) REFERENCES `crops`(`crop_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Harvest Records Table
-- Yield and output history for crops
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `harvests` (
  `harvest_id` INT AUTO_INCREMENT PRIMARY KEY,
  `crop_id` INT NOT NULL,
  `quantity_kg` DECIMAL(8,2) NOT NULL,
  `quality_grade` VARCHAR(20) DEFAULT NULL, -- e.g., 'Grade A', 'Premium'
  `harvest_date` DATE NOT NULL,
  `sale_price_per_kg` DECIMAL(8,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`crop_id`) REFERENCES `crops`(`crop_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Government Schemes Table
-- Stores subsidies, insurance, and agricultural schemes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `government_schemes` (
  `scheme_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) NOT NULL, -- e.g., 'Subsidy', 'Insurance', 'Loan'
  `eligibility` TEXT NOT NULL,
  `benefits` TEXT NOT NULL,
  `official_link` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 7. Live Farming News Table
-- Stores news and market/Mandi price updates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `farming_news` (
  `news_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'Market Trends',
  `content` TEXT NOT NULL,
  `source` VARCHAR(100) DEFAULT NULL,
  `published_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Sample Data Insertion
-- --------------------------------------------------------

-- Sample User (Password: password123 hashed)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password_hash`, `preferred_language`, `farm_location`) VALUES
('Ramesh Patil', 'ramesh@farmflow.com', '9876543210', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1eK4JtZ8Nl1GfXWfNlM4Vf/R82A7a4K', 'mr', 'Kolhapur, Maharashtra');

-- Sample Crop
INSERT INTO `crops` (`user_id`, `crop_name`, `field_area_acres`, `sowing_date`, `expected_harvest_date`, `status`, `notes`) VALUES
(1, 'Sugarcane', 4.50, '2026-01-15', '2026-12-20', 'Growing', 'Field No. 2 - Drip irrigated');

-- Sample Schedule
INSERT INTO `schedules` (`crop_id`, `task_type`, `description`, `scheduled_date`, `status`) VALUES
(1, 'Irrigation', 'Drip irrigation for 3 hours', '2026-08-10', 'Pending'),
(1, 'Fertilizer', 'Apply NPK 19-19-19 dose', '2026-08-15', 'Pending');

-- Sample Government Scheme
INSERT INTO `government_schemes` (`title`, `category`, `eligibility`, `benefits`, `official_link`) VALUES
('PM-KISAN Samman Nidhi', 'Subsidy', 'Small and marginal farmers holding cultivable land', 'Financial benefit of ₹6,000 per year in 3 installments', 'https://pmkisan.gov.in'),
('PM Fasal Bima Yojana', 'Insurance', 'Farmers growing notified crops in notified areas', 'Financial support in case of crop failure due to natural calamities', 'https://pmfby.gov.in');

-- Sample News
INSERT INTO `farming_news` (`title`, `category`, `content`, `source`, `published_date`) VALUES
('Monsoon Update 2026: Good rainfall predicted for Western Maharashtra', 'Weather', 'The Meteorological department expects normal to above-normal rainfall favorable for kharif crops.', 'AgriNews', '2026-08-01');
