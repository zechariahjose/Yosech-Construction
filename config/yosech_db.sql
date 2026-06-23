CREATE DATABASE IF NOT EXISTS yosech_db;
USE yosech_db;

CREATE TABLE Client (
    UserID                  INT AUTO_INCREMENT PRIMARY KEY,
    Client_FirstName        VARCHAR(50)  NOT NULL,
    Client_MI               CHAR(1),
    Client_LastName         VARCHAR(50)  NOT NULL,
    Client_Username         VARCHAR(50)  NOT NULL UNIQUE,
    Client_Password         VARCHAR(255) NOT NULL,
    Client_Email            VARCHAR(100) NOT NULL UNIQUE,
    Client_ContactNumber    VARCHAR(20)
);

CREATE TABLE Employee (
    EmployeeID      INT AUTO_INCREMENT PRIMARY KEY,
    UserType        ENUM('Admin', 'Manager', 'Employee') NOT NULL DEFAULT 'Employee',
    Username        VARCHAR(50)  NOT NULL UNIQUE,
    Password        VARCHAR(255) NOT NULL,
    Email           VARCHAR(100) NOT NULL UNIQUE,
    ContactNumber   VARCHAR(20)
);

CREATE TABLE Equipment (
    EquipmentID             INT AUTO_INCREMENT PRIMARY KEY,
    Specification           TEXT,
    StartDate               DATE,
    EndDate                 DATE,
    AvailabilityStatus      ENUM('Available', 'Rented', 'Under Maintenance') NOT NULL DEFAULT 'Available',
    NeedsOperator           TINYINT(1) NOT NULL DEFAULT 0,
    EquipmentPaymentStatus  ENUM('Unpaid', 'Paid', 'Partial') NOT NULL DEFAULT 'Unpaid'
);

CREATE TABLE Application (
    ApplicationID   INT AUTO_INCREMENT PRIMARY KEY,
    UserID          INT NOT NULL,
    EquipmentID     INT NOT NULL,
    ApplicationType VARCHAR(100),
    Description     TEXT,
    SubmissionDate  DATE,
    Status          ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_application_client    FOREIGN KEY (UserID)      REFERENCES Client(UserID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_application_equipment FOREIGN KEY (EquipmentID) REFERENCES Equipment(EquipmentID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Project (
    ProjectID               INT AUTO_INCREMENT PRIMARY KEY,
    ApplicationID           INT NOT NULL,
    ProposalDate            DATE,
    ProposalBudget          DECIMAL(10,2),
    ProposalStatus          ENUM('Draft', 'Submitted', 'Approved', 'Rejected') NOT NULL DEFAULT 'Draft',
    StartDate               DATE,
    EndDate                 DATE,
    ProjectStatus           ENUM('Ongoing', 'Completed', 'On Hold', 'Cancelled') NOT NULL DEFAULT 'Ongoing',
    Description             TEXT,
    ProjectPaymentStatus    ENUM('Unpaid', 'Paid', 'Partial') NOT NULL DEFAULT 'Unpaid',
    CONSTRAINT fk_project_application FOREIGN KEY (ApplicationID) REFERENCES Application(ApplicationID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Project_Update (
    UpdateID    INT AUTO_INCREMENT PRIMARY KEY,
    ProjectID   INT NOT NULL,
    EmployeeID  INT NOT NULL,
    Status      ENUM('Pending', 'Reviewed', 'Approved') NOT NULL DEFAULT 'Pending',
    Description TEXT,
    UpdateDate  DATE,
    CONSTRAINT fk_update_project  FOREIGN KEY (ProjectID)  REFERENCES Project(ProjectID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_update_employee FOREIGN KEY (EmployeeID) REFERENCES Employee(EmployeeID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS EquipmentOffering (
    EquipmentOfferingID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Model VARCHAR(100),
    Description TEXT,
    Specs TEXT,
    HourlyRate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    DailyRate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    WeeklyRate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    MonthlyRate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    AvailabilityStatus ENUM('Available', 'Unavailable', 'Under Maintenance') NOT NULL DEFAULT 'Available',
    ImageURL VARCHAR(255),
    UNIQUE KEY uq_equipment_name (Name)
);

CREATE TABLE IF NOT EXISTS ProjectShowcase (
    ProjectShowcaseID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(150) NOT NULL,
    Summary TEXT,
    StartDate DATE,
    EndDate DATE,
    Status ENUM('Ongoing', 'Completed', 'On Hold', 'Cancelled') NOT NULL DEFAULT 'Ongoing',
    ImageURL VARCHAR(255),
    UNIQUE KEY uq_project_title (Title)
);

INSERT IGNORE INTO EquipmentOffering (Name, Model, Description, Specs, HourlyRate, DailyRate, WeeklyRate, MonthlyRate, AvailabilityStatus)
VALUES
('Backhoe', 'HEV-320', 'Versatile digging, trenching, and loading machine for excavation and site preparation.', 'Bucket capacity: 1.0 m³ · Engine power: 62 kW · Max dig depth: 4.8 m', 1800.00, 14000.00, 78000.00, 280000.00, 'Available'),
('Grader', 'GRD-150', 'Precision grading equipment for leveling road foundations and existing surfaces.', 'Blade width: 3.2 m · Engine power: 129 kW · Operating weight: 15,600 kg', 2200.00, 17000.00, 95000.00, 340000.00, 'Available'),
('Road Roller', 'ROL-120', 'Compaction roller designed for asphalt, aggregate base, and sub-base work.', 'Drum width: 2.3 m · Drum weight: 12,500 kg · Frequency range: 30–33 Hz', 1700.00, 13000.00, 72000.00, 260000.00, 'Available'),
('Payloader', 'PLY-450', 'Heavy-duty wheel loader for moving materials, grading, and site cleanup.', 'Bucket capacity: 2.5 m³ · Engine power: 110 kW · Operating weight: 18,400 kg', 1900.00, 15000.00, 84000.00, 300000.00, 'Available'),
('Transit Mixer', 'MTR-800', 'Mobile concrete mixer for reliable on-site concrete delivery and pouring.', 'Mixing capacity: 8.0 m³ · Drum volume: 450 liters · Drive type: 6x4 chassis', 1950.00, 16000.00, 90000.00, 320000.00, 'Available'),
('Low Bed Trailer Truck', 'LBT-600', 'Heavy transport trailer for oversized machinery and large construction loads.', 'Load capacity: 45 tons · Trailer length: 14 m · Axles: 4', 2400.00, 18500.00, 104000.00, 380000.00, 'Available'),
('Dumptruck', 'DMP-220', 'High-capacity dump truck for earthmoving, haulage, and site demolition debris.', 'Load capacity: 18 tons · Body volume: 16 m³ · Engine: Euro IV', 1650.00, 12500.00, 68000.00, 245000.00, 'Available');

INSERT IGNORE INTO ProjectShowcase (Title, Summary, StartDate, EndDate, Status)
VALUES
('Road Concreting – Barangay Punta', 'A 2-kilometer road upgrade to improve access and ensure safer travel for local residents and vehicles. This project helped reduce dust, mud, and daily wear on transportation.', '2024-02-10', '2024-04-22', 'Completed', 'assets/projects/roadConcreting.jpg'),
('Flood Barrier – Purok 7 Riverbank', 'We constructed a reinforced flood barrier system to protect homes and businesses during heavy rains. This is one of our key efforts in supporting disaster resilience for the community.', '2024-01-05', '2024-03-18', 'Completed', 'assets/projects/floodBarrier.png'),
('Multi-Purpose Building – Barangay Hall Extension', 'A fully functional space designed for barangay meetings, events, and emergency response. Built with durability and adaptability in mind.', '2024-02-12', '2024-05-05', 'Completed' '', 'assets/projects/multiPurposeBuilding.jpg'),
('Drainage Canal – Zone 3 Main Street', 'This project improved water flow during storms, helping prevent frequent flooding. It included excavation, canal lining, and safety barriers.', '2023-11-20', '2024-01-10', 'Completed', 'assets/projects/drainageCanal.png'),
('2-Story Commercial Building', 'A modern two-story structure built to accommodate retail and office spaces. This project showcases our capacity for vertical construction, combining structural integrity with clean design.', '2023-08-15', '2023-12-01', 'Completed', 'assets/projects/2storyBuilding.jpg'),
('Road Concreting', 'A major road improvement project aimed at enhancing transportation and accessibility in the area. Once completed, this concrete road will reduce travel time and improve road safety for both commuters and delivery vehicles.', '2025-04-15', NULL, 'Ongoing'),
('Drainage System', 'Currently under construction, this drainage project is being implemented to prevent flooding and waterlogging in low-lying residential zones. The system is designed to improve runoff flow and enhance flood protection during heavy rains.', '2025-05-05', NULL, 'Ongoing'),
('3-Story Commercial Building', 'A modern commercial building under development, built to accommodate shops, offices, and rental spaces. The structure is designed with energy efficiency and accessibility in mind, and will serve as a hub for growing businesses in the area.', '2025-02-10', NULL, 'Ongoing'),
('Underground Conveyor Tunnel', 'A specialized infrastructure project designed to support the efficient transport of materials across an industrial facility. The tunnel includes reinforced walls and integrated safety systems to ensure long-term durability and smooth operation.', '2025-11-20', NULL, 'Ongoing');

DELIMITER $$

CREATE TRIGGER trg_equipment_on_application_approved
AFTER UPDATE ON Application
FOR EACH ROW
BEGIN
    IF NEW.Status = 'Approved' AND OLD.Status != 'Approved' THEN
        UPDATE Equipment SET AvailabilityStatus = 'Rented' WHERE EquipmentID = NEW.EquipmentID;
    END IF;
END$$

CREATE TRIGGER trg_equipment_on_application_rejected
AFTER UPDATE ON Application
FOR EACH ROW
BEGIN
    IF NEW.Status = 'Rejected' AND OLD.Status != 'Rejected' THEN
        UPDATE Equipment SET AvailabilityStatus = 'Available' WHERE EquipmentID = NEW.EquipmentID;
    END IF;
END$$

DELIMITER ;
