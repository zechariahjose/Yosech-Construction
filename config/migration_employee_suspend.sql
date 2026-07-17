-- Migration: add is_suspended to Employee table
-- Run once in phpMyAdmin

ALTER TABLE Employee
    ADD COLUMN is_suspended TINYINT(1) NOT NULL DEFAULT 0 AFTER ContactNumber;
