USE yosech_db;

CREATE TABLE Client (
    UserID          INT AUTO_INCREMENT PRIMARY KEY,
    FirstName       VARCHAR(50)  NOT NULL,
    MI              CHAR(1),
    LastName        VARCHAR(50)  NOT NULL,
    Username        VARCHAR(50)  NOT NULL UNIQUE,
    Password        VARCHAR(255) NOT NULL,
    Email           VARCHAR(100) NOT NULL UNIQUE,
    ContactNumber   VARCHAR(20)
);

CREATE TABLE Employee (
    EmployeeID      INT AUTO_INCREMENT PRIMARY KEY,
    UserID          INT          NOT NULL,
    UserType        ENUM('Admin', 'Manager', 'Employee') NOT NULL DEFAULT 'Employee',
    Username        VARCHAR(50)  NOT NULL UNIQUE,
    Password        VARCHAR(255) NOT NULL,
    Email           VARCHAR(100) NOT NULL UNIQUE,
    ContactNumber   VARCHAR(20),
    CONSTRAINT fk_employee_client FOREIGN KEY (UserID) REFERENCES Client(UserID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Equipment (
    EquipmentID         INT AUTO_INCREMENT PRIMARY KEY,
    Specification       TEXT,
    StartDate           DATE,
    EndDate             DATE,
    AvailabilityStatus  ENUM('Available', 'Rented', 'Under Maintenance') NOT NULL DEFAULT 'Available',
    NeedsOperator       TINYINT(1) NOT NULL DEFAULT 0,
    PaymentStatus       ENUM('Unpaid', 'Paid', 'Partial') NOT NULL DEFAULT 'Unpaid'
);

CREATE TABLE Application (
    ApplicationID       INT AUTO_INCREMENT PRIMARY KEY,
    UserID              INT NOT NULL,
    ApplicationType     VARCHAR(100),
    Description         TEXT,
    SubmissionDate      DATE,
    Status              ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_application_client FOREIGN KEY (UserID) REFERENCES Client(UserID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Project (
    ProjectID       INT AUTO_INCREMENT PRIMARY KEY,
    ApplicationID   INT NOT NULL,
    ProposalID      INT,
    StartDate       DATE,
    EndDate         DATE,
    ProjectStatus   ENUM('Ongoing', 'Completed', 'On Hold', 'Cancelled') NOT NULL DEFAULT 'Ongoing',
    Description     TEXT,
    PaymentStatus   ENUM('Unpaid', 'Paid', 'Partial') NOT NULL DEFAULT 'Unpaid',
    CONSTRAINT fk_project_application FOREIGN KEY (ApplicationID) REFERENCES Application(ApplicationID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Project_Update (
    UpdateID        INT AUTO_INCREMENT PRIMARY KEY,
    ProjectID       INT NOT NULL,
    EmployeeID      INT NOT NULL,
    Status          ENUM('Pending', 'Reviewed', 'Approved') NOT NULL DEFAULT 'Pending',
    Description     TEXT,
    UpdateDate      DATE,
    CONSTRAINT fk_update_project  FOREIGN KEY (ProjectID)  REFERENCES Project(ProjectID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_update_employee FOREIGN KEY (EmployeeID) REFERENCES Employee(EmployeeID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Application_Equipment (
    ApplicationID   INT NOT NULL,
    EquipmentID     INT NOT NULL,
    PRIMARY KEY (ApplicationID, EquipmentID),
    CONSTRAINT fk_ae_application FOREIGN KEY (ApplicationID) REFERENCES Application(ApplicationID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ae_equipment   FOREIGN KEY (EquipmentID)   REFERENCES Equipment(EquipmentID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

DELIMITER $$

CREATE TRIGGER trg_equipment_on_application_insert
AFTER INSERT ON Application_Equipment
FOR EACH ROW
BEGIN
    DECLARE app_status VARCHAR(20);
    SELECT Status INTO app_status FROM Application WHERE ApplicationID = NEW.ApplicationID;
    IF app_status = 'Approved' THEN
        UPDATE Equipment SET AvailabilityStatus = 'Rented' WHERE EquipmentID = NEW.EquipmentID;
    END IF;
END$$

CREATE TRIGGER trg_equipment_on_application_status
AFTER UPDATE ON Application
FOR EACH ROW
BEGIN
    IF NEW.Status = 'Rejected' AND OLD.Status != 'Rejected' THEN
        UPDATE Equipment e
        JOIN Application_Equipment ae ON ae.EquipmentID = e.EquipmentID
        SET e.AvailabilityStatus = 'Available'
        WHERE ae.ApplicationID = NEW.ApplicationID;
    END IF;
END$$

DELIMITER ;
