-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add is_suspended to Client table
-- Allows admin to suspend/disable a client account without deleting it.
-- ─────────────────────────────────────────────────────────────────────────────

USE yosech_db;

ALTER TABLE Client
    ADD COLUMN IF NOT EXISTS is_suspended TINYINT(1) NOT NULL DEFAULT 0
        AFTER Client_ContactNumber;
