-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add ProjectID to ProjectShowcase
-- Allows internal Project rows to be linked to their showcase entry reliably,
-- fixing the double-listing bug and incorrect "already published" detection.
-- ─────────────────────────────────────────────────────────────────────────────

USE yosech_db;

-- Add nullable ProjectID column (NULL = legacy/standalone showcase entry)
ALTER TABLE ProjectShowcase
    ADD COLUMN ProjectID INT NULL DEFAULT NULL
        AFTER ProjectShowcaseID,
    ADD CONSTRAINT fk_showcase_project
        FOREIGN KEY (ProjectID) REFERENCES Project(ProjectID)
        ON DELETE SET NULL ON UPDATE CASCADE;
