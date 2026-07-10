-- ============================================================
-- Migration: Add client records for existing ProjectShowcase
-- entries so they appear in the PM Clients page.
-- Run once in phpMyAdmin.
-- ============================================================

USE yosech_db;

-- ── 1. Insert client accounts ──────────────────────────────
INSERT IGNORE INTO Client
    (Client_FirstName, Client_MI, Client_LastName, Client_Username, Client_Password, Client_Email, Client_ContactNumber)
VALUES
-- Barangay Punta road project
('Ramon',    'D', 'Villanueva',  'rvillanueva',  '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'ramon.villanueva@dipologcity.gov.ph',  '09171234001'),
-- Flood barrier – Purok 7
('Ligaya',   'M', 'Reyes',       'lreyes',       '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'ligaya.reyes@purok7.org',             '09181234002'),
-- Multi-purpose building – Barangay Hall
('Eduardo',  'P', 'Santos',      'esantos',      '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'eduardo.santos@bgyofficials.ph',      '09191234003'),
-- Drainage Canal – Zone 3
('Maricel',  'A', 'Dela Cruz',   'mdelacruz',    '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'maricel.delacruz@zndc.gov.ph',        '09201234004'),
-- 2-Story Commercial Building
('Rodrigo',  'B', 'Tan',         'rtan',         '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'rodrigo.tan@tantradingcorp.com',      '09211234005'),
-- Road Concreting (ongoing)
('Felicidad','C', 'Macaraeg',    'fmacaraeg',    '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'felicidad.macaraeg@lgu-dipolog.ph',   '09221234006'),
-- Drainage System
('Nestor',   'L', 'Gabisan',     'ngabisan',     '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'nestor.gabisan@zndc.gov.ph',          '09231234007'),
-- 3-Story Commercial Building (ongoing)
('Alicia',   'V', 'Espinosa',    'aespinosa',    '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'alicia.espinosa@espinosaproperties.ph','09241234008'),
-- Underground Conveyor Tunnel
('Benjamin', 'R', 'Florendo',    'bflorendo',    '$2y$10$examplehashAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 'benjamin.florendo@florendoindustrial.ph','09251234009');

-- ── 2. Insert Application records (Approved, New Project) ──
-- Use subqueries to get the UserID for each client inserted above.

INSERT IGNORE INTO Application
    (UserID, ApplicationType, Description, ProjectTitle, ProjectLocation,
     ProposalBudget, ProjectStartDate, ProjectEndDate, SubmissionDate, Status)
VALUES
(
    (SELECT UserID FROM Client WHERE Client_Username = 'rvillanueva' LIMIT 1),
    'New Project',
    'Client: Ramon D. Villanueva | Contact: 09171234001

Road concreting project covering 2 kilometers of Barangay Punta main road to improve access and reduce dust and mud for residents.',
    'Road Concreting – Barangay Punta', 'Barangay Punta, Dipolog City',
    3200000.00, '2024-02-10', '2024-04-22', '2024-01-20', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'lreyes' LIMIT 1),
    'New Project',
    'Client: Ligaya M. Reyes | Contact: 09181234002

Construction of a reinforced flood barrier system along Purok 7 Riverbank to protect homes and businesses during heavy rains.',
    'Flood Barrier – Purok 7 Riverbank', 'Purok 7 Riverbank, Dipolog City',
    2750000.00, '2024-01-05', '2024-03-18', '2023-12-15', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'esantos' LIMIT 1),
    'New Project',
    'Client: Eduardo P. Santos | Contact: 09191234003

Extension of the Barangay Hall to create a multi-purpose space for meetings, events, and emergency response operations.',
    'Multi-Purpose Building – Barangay Hall Extension', 'Barangay Hall, Dipolog City',
    4100000.00, '2024-02-12', '2024-05-05', '2024-01-25', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'mdelacruz' LIMIT 1),
    'New Project',
    'Client: Maricel A. Dela Cruz | Contact: 09201234004

Drainage canal improvement along Zone 3 Main Street to prevent flooding during storm events.',
    'Drainage Canal – Zone 3 Main Street', 'Zone 3, Dipolog City',
    1850000.00, '2023-11-20', '2024-01-10', '2023-11-01', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'rtan' LIMIT 1),
    'New Project',
    'Client: Rodrigo B. Tan | Contact: 09211234005

Construction of a two-story commercial building to house retail and office spaces in the commercial district.',
    '2-Story Commercial Building', 'Commercial District, Dipolog City',
    6500000.00, '2023-08-15', '2023-12-01', '2023-07-20', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'fmacaraeg' LIMIT 1),
    'New Project',
    'Client: Felicidad C. Macaraeg | Contact: 09221234006

Road concreting project to enhance transportation and accessibility in the area. Project is currently ongoing.',
    'Road Concreting', 'Dipolog City',
    3800000.00, '2025-04-15', NULL, '2025-03-28', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'ngabisan' LIMIT 1),
    'New Project',
    'Client: Nestor L. Gabisan | Contact: 09231234007

Drainage system installation to prevent flooding and waterlogging in low-lying residential zones.',
    'Drainage System', 'Residential Zone, Dipolog City',
    2200000.00, '2025-05-05', '2025-12-16', '2025-04-18', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'aespinosa' LIMIT 1),
    'New Project',
    'Client: Alicia V. Espinosa | Contact: 09241234008

Development of a three-story commercial building designed to accommodate shops, offices, and rental spaces with energy-efficient features.',
    '3-Story Commercial Building', 'Dipolog City',
    9200000.00, '2025-02-10', NULL, '2025-01-22', 'Approved'
),
(
    (SELECT UserID FROM Client WHERE Client_Username = 'bflorendo' LIMIT 1),
    'New Project',
    'Client: Benjamin R. Florendo | Contact: 09251234009

Construction of an underground conveyor tunnel for an industrial facility, with reinforced walls and integrated safety systems.',
    'Underground Conveyor Tunnel', 'Industrial Area, Dipolog City',
    12500000.00, '2025-11-20', '2026-03-20', '2025-10-30', 'Approved'
);

-- ── 3. Create Project records linked to those Applications ──

INSERT IGNORE INTO Project
    (ApplicationID, ProposalBudget, ProposalStatus, StartDate, EndDate,
     ProjectStatus, Description, ProjectPaymentStatus)
SELECT
    a.ApplicationID,
    a.ProposalBudget,
    'Approved',
    a.ProjectStartDate,
    a.ProjectEndDate,
    CASE a.ProjectTitle
        WHEN 'Road Concreting'           THEN 'Ongoing'
        WHEN '3-Story Commercial Building' THEN 'Ongoing'
        ELSE 'Completed'
    END,
    a.Description,
    CASE a.ProjectTitle
        WHEN 'Road Concreting'             THEN 'Partial'
        WHEN '3-Story Commercial Building' THEN 'Unpaid'
        WHEN 'Underground Conveyor Tunnel' THEN 'Partial'
        ELSE 'Paid'
    END
FROM Application a
JOIN Client c ON a.UserID = c.UserID
WHERE c.Client_Username IN (
    'rvillanueva','lreyes','esantos','mdelacruz','rtan',
    'fmacaraeg','ngabisan','aespinosa','bflorendo'
)
AND a.ApplicationType = 'New Project'
AND NOT EXISTS (
    SELECT 1 FROM Project p WHERE p.ApplicationID = a.ApplicationID
);

-- ── 4. Set default passwords to '123' (hashed) ─────────────
UPDATE Client
SET Client_Password = '$2y$10$EEvtfR6xip2Voq7WrB0V4uMonjINh8Iee9osydkyQtqHD7D/tXSc2'
WHERE Client_Username IN (
    'rvillanueva','lreyes','esantos','mdelacruz','rtan',
    'fmacaraeg','ngabisan','aespinosa','bflorendo'
);
-- Default login password for all these clients is: 123
