-- Migration: add last_active tracking for online presence
-- Run once in phpMyAdmin or MySQL CLI

ALTER TABLE Client
    ADD COLUMN last_active DATETIME NULL DEFAULT NULL AFTER Client_ContactNumber;

ALTER TABLE Employee
    ADD COLUMN last_active DATETIME NULL DEFAULT NULL AFTER ContactNumber;
