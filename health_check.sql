-- =============================================
-- LAFORMATIK — Database Health Check Script
-- Run this to verify schema integrity.
-- =============================================

USE `laformatik`;

-- 1. Verify 'emoji' column exists in categories table
SELECT
  'categories.emoji column' AS `Check`,
  IF(COUNT(*) > 0, '✅ EXISTS', '❌ MISSING — run: ALTER TABLE categories ADD emoji VARCHAR(10) NOT NULL DEFAULT "📁"') AS `Status`
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'laformatik'
  AND TABLE_NAME   = 'categories'
  AND COLUMN_NAME  = 'emoji';

-- 2. Verify Foreign Key constraint exists
SELECT
  'FK: produits → categories' AS `Check`,
  IF(COUNT(*) > 0, '✅ EXISTS', '❌ MISSING — re-run database.sql') AS `Status`
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA    = 'laformatik'
  AND TABLE_NAME      = 'produits'
  AND CONSTRAINT_NAME = 'fk_produits_categories'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

-- 3. Verify FK rules (UPDATE CASCADE, DELETE RESTRICT)
SELECT
  'FK Rules' AS `Check`,
  CONCAT(
    'UPDATE: ', UPDATE_RULE,
    ' | DELETE: ', DELETE_RULE,
    IF(UPDATE_RULE = 'CASCADE' AND DELETE_RULE = 'RESTRICT', ' ✅ Correct', ' ⚠️ Check rules')
  ) AS `Status`
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'laformatik'
  AND CONSTRAINT_NAME   = 'fk_produits_categories';

-- 4. Table row counts
SELECT 'Category Count' AS `Check`, CONCAT(COUNT(*), ' rows') AS `Status` FROM categories
UNION ALL
SELECT 'Product Count',  CONCAT(COUNT(*), ' rows') FROM produits;

-- 5. Orphan check: products referencing non-existent categories
SELECT
  'Orphan Products' AS `Check`,
  IF(COUNT(*) = 0, '✅ None', CONCAT('❌ ', COUNT(*), ' orphan(s) found!')) AS `Status`
FROM produits p
LEFT JOIN categories c ON p.id_cat = c.id_cat
WHERE c.id_cat IS NULL;

-- 6. Verify charset is utf8mb4 (for emoji support)
SELECT
  'Character Set' AS `Check`,
  CONCAT(DEFAULT_CHARACTER_SET_NAME, ' / ', DEFAULT_COLLATION_NAME,
    IF(DEFAULT_CHARACTER_SET_NAME = 'utf8mb4', ' ✅', ' ⚠️ Should be utf8mb4')) AS `Status`
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME = 'laformatik';
