-- Migration: update image paths to new asset filenames
-- Run once against yosech_db

USE yosech_db;

-- Equipment images (use new lowercase filenames)
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/grader.png'           WHERE Name = 'Grader';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/roadroller.jpg'       WHERE Name = 'Road Roller';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/transitmixer.jpg'     WHERE Name = 'Transit Mixer';
UPDATE EquipmentOffering SET ImageURL = 'assets/equipment/lowbedtrailertruck.jpg' WHERE Name = 'Low Bed Trailer Truck';

-- Project images (use new drainagesystem.jpg for Drainage System entries)
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/drainagesystem.jpg' WHERE Title = 'Drainage System';
UPDATE ProjectShowcase SET ImageURL = 'assets/projects/drainagesystem.jpg' WHERE Title = 'Drainage Canal – Zone 3 Main Street';
