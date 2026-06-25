USE yosech_db;

SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Client' AND COLUMN_NAME = 'ProfilePicture') = 0,
    'ALTER TABLE Client ADD COLUMN ProfilePicture VARCHAR(255) NULL AFTER Client_ContactNumber',
    'SELECT ''ProfilePicture already exists'' AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
