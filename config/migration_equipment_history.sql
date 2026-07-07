-- Migration: add DateAdded tracking to EquipmentOffering
-- Run once in phpMyAdmin or MySQL CLI

ALTER TABLE EquipmentOffering
    ADD COLUMN DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    AFTER ImageURL;

-- Back-fill existing rows with a reasonable date so they show up in history
-- (uses CURRENT_TIMESTAMP at migration time, which is fine for existing seed data)
UPDATE EquipmentOffering SET DateAdded = NOW() WHERE DateAdded = '0000-00-00 00:00:00';
