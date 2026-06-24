-- Run this migration on existing yosech_db databases to support the updated application form.
USE yosech_db;

ALTER TABLE Equipment
    ADD COLUMN EquipmentOfferingID INT NULL AFTER EquipmentID;

ALTER TABLE Application
    MODIFY EquipmentID INT NULL;

ALTER TABLE Application
    ADD COLUMN ProjectTitle VARCHAR(150) NULL AFTER Description,
    ADD COLUMN ProjectLocation VARCHAR(255) NULL AFTER ProjectTitle,
    ADD COLUMN ProposalBudget DECIMAL(10,2) NULL AFTER ProjectLocation,
    ADD COLUMN ProjectStartDate DATE NULL AFTER ProposalBudget,
    ADD COLUMN ProjectEndDate DATE NULL AFTER ProjectStartDate,
    ADD COLUMN RentalStartDate DATE NULL AFTER ProjectEndDate,
    ADD COLUMN RentalEndDate DATE NULL AFTER RentalStartDate,
    ADD COLUMN NeedsOperator TINYINT(1) NOT NULL DEFAULT 0 AFTER RentalEndDate;

ALTER TABLE Equipment
    ADD CONSTRAINT fk_equipment_offering FOREIGN KEY (EquipmentOfferingID) REFERENCES EquipmentOffering(EquipmentOfferingID)
        ON DELETE SET NULL ON UPDATE CASCADE;

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
