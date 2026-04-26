-- =============================================
-- LAFORMATIK — IT Stock Management
-- Database Schema (MySQL)
-- =============================================

CREATE DATABASE IF NOT EXISTS `laformatik`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `laformatik`;

-- Drop existing tables for a clean install
DROP TABLE IF EXISTS `produits`;
DROP TABLE IF EXISTS `categories`;

-- =============================================
-- Table: categories (with emoji icon support)
-- =============================================
CREATE TABLE `categories` (
  `id_cat`  INT          NOT NULL AUTO_INCREMENT,
  `nom_cat` VARCHAR(50)  NOT NULL,
  `emoji`   VARCHAR(10)  NOT NULL DEFAULT '📁',
  PRIMARY KEY (`id_cat`),
  UNIQUE KEY `uq_nom_cat` (`nom_cat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: produits (products)
-- =============================================
CREATE TABLE `produits` (
  `ref_prod`    VARCHAR(20)    NOT NULL,
  `designation` VARCHAR(100)   NOT NULL,
  `description` TEXT           DEFAULT NULL,
  `marque`      VARCHAR(50)    DEFAULT NULL,
  `prix`        DECIMAL(10,2)  NOT NULL CHECK (`prix` > 0),
  `quantite`    INT            NOT NULL DEFAULT 0 CHECK (`quantite` >= 0),
  `photo`       VARCHAR(255)   DEFAULT NULL,
  `id_cat`      INT            NOT NULL,
  PRIMARY KEY (`ref_prod`),
  KEY `fk_produits_categories` (`id_cat`),
  CONSTRAINT `fk_produits_categories`
    FOREIGN KEY (`id_cat`) REFERENCES `categories` (`id_cat`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Sample Data (with emojis per category)
-- =============================================
INSERT INTO `categories` (`nom_cat`, `emoji`) VALUES
  ('Laptops',        '💻'),
  ('PC Components',  '🔧'),
  ('Peripherals',    '🖱️'),
  ('Networking',     '🌐'),
  ('Storage',        '💾'),
  ('Accessories',    '🎒');

INSERT INTO `produits` (`ref_prod`, `designation`, `description`, `marque`, `prix`, `quantite`, `id_cat`) VALUES
  ('LAP-001', 'HP ProBook 450 G10',        '15.6" FHD, Intel Core i5, 16GB RAM, 512GB SSD',                 'HP',              3250.00, 12, 1),
  ('LAP-002', 'Lenovo ThinkPad E14',        '14" FHD, AMD Ryzen 5, 8GB RAM, 256GB SSD',                     'Lenovo',          2800.00,  8, 1),
  ('LAP-003', 'Dell Latitude 5540',         '15.6" FHD, Intel Core i7, 16GB RAM, 512GB SSD',                'Dell',            4100.00,  3, 1),
  ('CMP-001', 'NVIDIA GeForce RTX 4060',    '8GB GDDR6, DLSS 3, Ray Tracing',                               'NVIDIA',          1350.00, 15, 2),
  ('CMP-002', 'AMD Ryzen 7 7700X',          '8-Core / 16-Thread, 4.5GHz base, 5.4GHz boost',                'AMD',             1100.00, 20, 2),
  ('CMP-003', 'Corsair Vengeance DDR5 32GB','2x16GB DDR5 5600MHz CL36',                                     'Corsair',          480.00,  2, 2),
  ('PER-001', 'Logitech MX Master 3S',      'Wireless ergonomic mouse, 8000 DPI sensor, USB-C',             'Logitech',         350.00, 25, 3),
  ('PER-002', 'Keychron K8 Pro',            'TKL Mechanical Keyboard, Gateron switches, RGB, Bluetooth',     'Keychron',         420.00,  0, 3),
  ('PER-003', 'Dell UltraSharp U2723QE',    '27" 4K UHD IPS Black Monitor, USB-C Hub',                      'Dell',            2200.00,  4, 3),
  ('NET-001', 'TP-Link Archer AX73',        'WiFi 6 AX5400 Router, Dual Band, 6 Antennas',                  'TP-Link',          380.00, 18, 4),
  ('NET-002', 'Ubiquiti UniFi AP AC Pro',   'Professional WiFi Access Point, PoE, 1750 Mbps',               'Ubiquiti',         520.00,  7, 4),
  ('STK-001', 'Samsung 990 PRO 2TB',        'NVMe M.2 SSD, Read 7450 MB/s, Write 6900 MB/s',               'Samsung',          750.00, 10, 5),
  ('STK-002', 'WD Red Plus 4TB',            '3.5" NAS HDD, 5400 RPM, CMR',                                  'Western Digital',  450.00,  1, 5),
  ('ACC-001', 'Targus CityLite Pro 15.6"',  'Laptop Bag, Water-resistant, Shoulder Strap',                   'Targus',           180.00, 30, 6),
  ('ACC-002', 'Anker PowerCore 20000mAh',   'USB-C Power Bank, 20W Fast Charging',                          'Anker',            150.00,  0, 6);
