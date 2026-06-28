USE yosech_db;

INSERT IGNORE INTO ProjectShowcase (Title, Summary, StartDate, EndDate, Status, ImageURL)
VALUES
('Road Concreting – Barangay Punta', 'A 2-kilometer road upgrade to improve access and ensure safer travel for local residents and vehicles. This project helped reduce dust, mud, and daily wear on transportation.', '2024-02-10', '2024-04-22', 'Completed', 'assets/projects/roadConcreting.jpg'),
('Flood Barrier – Purok 7 Riverbank', 'We constructed a reinforced flood barrier system to protect homes and businesses during heavy rains. This is one of our key efforts in supporting disaster resilience for the community.', '2024-01-05', '2024-03-18', 'Completed', 'assets/projects/floodBarrier.png'),
('Multi-Purpose Building – Barangay Hall Extension', 'A fully functional space designed for barangay meetings, events, and emergency response. Built with durability and adaptability in mind.', '2024-02-12', '2024-05-05', 'Completed', 'assets/projects/multiPurposeBuilding.jpg'),
('Drainage Canal – Zone 3 Main Street', 'This project improved water flow during storms, helping prevent frequent flooding. It included excavation, canal lining, and safety barriers.', '2023-11-20', '2024-01-10', 'Completed', 'assets/projects/drainageCanal.png'),
('2-Story Commercial Building', 'A modern two-story structure built to accommodate retail and office spaces. This project showcases our capacity for vertical construction, combining structural integrity with clean design.', '2023-08-15', '2023-12-01', 'Completed', 'assets/projects/2storyBuilding.jpg'),
('Road Concreting', 'A major road improvement project aimed at enhancing transportation and accessibility in the area. Once completed, this concrete road will reduce travel time and improve road safety for both commuters and delivery vehicles.', '2025-04-15', NULL, 'Ongoing', 'assets/projects/roadConcreting.jpg'),
('Drainage System', 'Currently under construction, this drainage project is being implemented to prevent flooding and waterlogging in low-lying residential zones. The system is designed to improve runoff flow and enhance flood protection during heavy rains.', '2025-05-05', '2025-12-16', 'Completed', 'assets/projects/drainageCanal.png'),
('3-Story Commercial Building', 'A modern commercial building under development, built to accommodate shops, offices, and rental spaces. The structure is designed with energy efficiency and accessibility in mind, and will serve as a hub for growing businesses in the area.', '2025-02-10', NULL, 'Ongoing', 'assets/projects/2storyBuilding.jpg'),
('Underground Conveyor Tunnel', 'A specialized infrastructure project designed to support the efficient transport of materials across an industrial facility. The tunnel includes reinforced walls and integrated safety systems to ensure long-term durability and smooth operation.', '2025-11-20', '2026-03-20', 'Completed', 'assets/projects/tunnel.jpg');

UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/backhoe.jpg' WHERE Name = 'Backhoe';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/grader.jpg' WHERE Name = 'Grader';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/roadRoller.jpg' WHERE Name = 'Road Roller';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/payloader.jpg' WHERE Name = 'Payloader';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/transitMixer.jpg' WHERE Name = 'Transit Mixer';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/lowBedTrailer.jpg' WHERE Name = 'Low Bed Trailer Truck';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/dumptruck.jpg' WHERE Name = 'Dumptruck';

UPDATE ProjectShowcase SET ImageURL = 'assets/projects/roadConcreting.jpg' WHERE Title = 'Road Concreting – Barangay Punta';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/floodBarrier.png' WHERE Title = 'Flood Barrier – Purok 7 Riverbank';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/multiPurposeBuilding.jpg' WHERE Title = 'Multi-Purpose Building – Barangay Hall Extension';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/drainageCanal.png' WHERE Title = 'Drainage Canal – Zone 3 Main Street';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/2storyBuilding.jpg' WHERE Title = '2-Story Commercial Building';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/roadConcreting.jpg' WHERE Title = 'Road Concreting';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/drainageCanal.png' WHERE Title = 'Drainage System';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/2storyBuilding.jpg' WHERE Title = '3-Story Commercial Building';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/tunnel.jpg' WHERE Title = 'Underground Conveyor Tunnel';
