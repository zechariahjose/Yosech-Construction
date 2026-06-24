-- Safe to re-run: skips steps that are already applied.
USE yosech_db;

-- EquipmentOfferingID on Equipment
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipment' AND COLUMN_NAME = 'EquipmentOfferingID') = 0,
    'ALTER TABLE Equipment ADD COLUMN EquipmentOfferingID INT NULL AFTER EquipmentID',
    'SELECT ''EquipmentOfferingID already exists'' AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Allow project applications without equipment
ALTER TABLE Application MODIFY EquipmentID INT NULL;

-- Application form columns
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Application' AND COLUMN_NAME = 'ProjectTitle') = 0,
    'ALTER TABLE Application
        ADD COLUMN ProjectTitle VARCHAR(150) NULL AFTER Description,
        ADD COLUMN ProjectLocation VARCHAR(255) NULL AFTER ProjectTitle,
        ADD COLUMN ProposalBudget DECIMAL(10,2) NULL AFTER ProjectLocation,
        ADD COLUMN ProjectStartDate DATE NULL AFTER ProposalBudget,
        ADD COLUMN ProjectEndDate DATE NULL AFTER ProjectStartDate,
        ADD COLUMN RentalStartDate DATE NULL AFTER ProjectEndDate,
        ADD COLUMN RentalEndDate DATE NULL AFTER RentalStartDate,
        ADD COLUMN NeedsOperator TINYINT(1) NOT NULL DEFAULT 0 AFTER RentalEndDate',
    'SELECT ''Application columns already exist'' AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Foreign key (only if missing)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipment' AND CONSTRAINT_NAME = 'fk_equipment_offering') = 0,
    'ALTER TABLE Equipment ADD CONSTRAINT fk_equipment_offering FOREIGN KEY (EquipmentOfferingID) REFERENCES EquipmentOffering(EquipmentOfferingID) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''fk_equipment_offering already exists'' AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Link fleet records to catalog entries
INSERT INTO Equipment (EquipmentOfferingID, Specification, AvailabilityStatus, NeedsOperator, EquipmentPaymentStatus)
SELECT eo.EquipmentOfferingID,
       CONCAT(eo.Name, IF(eo.Model IS NOT NULL AND eo.Model != '', CONCAT(' (', eo.Model, ')'), '')),
       CASE eo.AvailabilityStatus
           WHEN 'Available' THEN 'Available'
           WHEN 'Unavailable' THEN 'Rented'
           ELSE 'Under Maintenance'
       END,
       0,
       'Unpaid'
FROM EquipmentOffering eo
WHERE NOT EXISTS (
    SELECT 1 FROM Equipment e WHERE e.EquipmentOfferingID = eo.EquipmentOfferingID
);

DROP TRIGGER IF EXISTS trg_equipment_on_application_approved;
DROP TRIGGER IF EXISTS trg_equipment_on_application_rejected;

DELIMITER $$

CREATE TRIGGER trg_equipment_on_application_approved
AFTER UPDATE ON Application
FOR EACH ROW
BEGIN
    IF NEW.Status = 'Approved' AND OLD.Status != 'Approved' AND NEW.EquipmentID IS NOT NULL THEN
        UPDATE Equipment SET AvailabilityStatus = 'Rented' WHERE EquipmentID = NEW.EquipmentID;
    END IF;
END$$

CREATE TRIGGER trg_equipment_on_application_rejected
AFTER UPDATE ON Application
FOR EACH ROW
BEGIN
    IF NEW.Status = 'Rejected' AND OLD.Status != 'Rejected' AND NEW.EquipmentID IS NOT NULL THEN
        UPDATE Equipment SET AvailabilityStatus = 'Available' WHERE EquipmentID = NEW.EquipmentID;
    END IF;
END$$

DELIMITER ;

-- Default admin account (username: admin, password: admin123)
INSERT IGNORE INTO Employee (UserType, Username, Password, Email, ContactNumber)
VALUES ('Admin', 'admin', 'admin123', 'admin@yosechconstruction.com', NULL);
