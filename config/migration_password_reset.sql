-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Create password_reset_tokens table
-- Enables the forgot-password / self-service reset flow for clients.
-- Tokens expire after 1 hour and are single-use.
-- ─────────────────────────────────────────────────────────────────────────────

USE yosech_db;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_type  ENUM('Client') NOT NULL DEFAULT 'Client',
    user_id    INT NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used       TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user  (user_type, user_id)
);
