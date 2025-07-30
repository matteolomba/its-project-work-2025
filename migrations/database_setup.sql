-- Script di setup del database gestione_cv
-- Sistema di gestione CV con eliminazione diretta (instant delete)
-- Aggiornato: gennaio 2025

CREATE DATABASE IF NOT EXISTS my_sportswim;
USE my_sportswim;

-- ===== TABELLE PRINCIPALI =====

-- Tabella utenti
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `data_nascita` DATE DEFAULT NULL,
  `indirizzo` VARCHAR(255) DEFAULT NULL,
  `citta` VARCHAR(100) DEFAULT NULL,
  `cap` VARCHAR(5) DEFAULT NULL,
  `sommario` TEXT DEFAULT NULL,
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `user_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiorna tabella utenti: aggiungi colonne mancanti se non esistono
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `nome` varchar(100) NOT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `cognome` varchar(100) NOT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email` varchar(100) NOT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `password` varchar(255) NOT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `telefono` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `data_nascita` DATE DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `indirizzo` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `citta` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `cap` VARCHAR(5) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `sommario` TEXT DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_picture` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `user_type` enum('user','admin') NOT NULL DEFAULT 'user';
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp();
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Tabella curricula
CREATE TABLE IF NOT EXISTS `curricula` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `nome_originale` VARCHAR(255),
    `nome_file` VARCHAR(255),
    `file_path` VARCHAR(255),
    `tipo` ENUM('caricato', 'generato') DEFAULT 'caricato',
    `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiorna tabella curricula
ALTER TABLE `curricula` ADD COLUMN IF NOT EXISTS `nome_originale` VARCHAR(255);
ALTER TABLE `curricula` ADD COLUMN IF NOT EXISTS `nome_file` VARCHAR(255);
ALTER TABLE `curricula` ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(255);
ALTER TABLE `curricula` ADD COLUMN IF NOT EXISTS `tipo` ENUM('caricato', 'generato') DEFAULT 'caricato';
ALTER TABLE `curricula` ADD COLUMN IF NOT EXISTS `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `curricula` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NOT NULL;

-- Tabella esperienze formative
CREATE TABLE IF NOT EXISTS `esperienze_formative` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `istituto` VARCHAR(255) NOT NULL,
    `titolo` VARCHAR(255) NOT NULL,
    `descrizione` TEXT,
    `data_inizio` DATE NOT NULL,
    `data_fine` DATE,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiorna tabella esperienze_formative
ALTER TABLE `esperienze_formative` ADD COLUMN IF NOT EXISTS `istituto` VARCHAR(255) NOT NULL;
ALTER TABLE `esperienze_formative` ADD COLUMN IF NOT EXISTS `titolo` VARCHAR(255) NOT NULL;
ALTER TABLE `esperienze_formative` ADD COLUMN IF NOT EXISTS `descrizione` TEXT;
ALTER TABLE `esperienze_formative` ADD COLUMN IF NOT EXISTS `data_inizio` DATE NOT NULL;
ALTER TABLE `esperienze_formative` ADD COLUMN IF NOT EXISTS `data_fine` DATE;
ALTER TABLE `esperienze_formative` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NOT NULL;

-- Tabella esperienze lavorative
CREATE TABLE IF NOT EXISTS `esperienze_lavorative` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `azienda` VARCHAR(255) NOT NULL,
    `posizione` VARCHAR(255) NOT NULL,
    `descrizione` TEXT,
    `data_inizio` DATE NOT NULL,
    `data_fine` DATE,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiorna tabella esperienze_lavorative
ALTER TABLE `esperienze_lavorative` ADD COLUMN IF NOT EXISTS `azienda` VARCHAR(255) NOT NULL;
ALTER TABLE `esperienze_lavorative` ADD COLUMN IF NOT EXISTS `posizione` VARCHAR(255) NOT NULL;
ALTER TABLE `esperienze_lavorative` ADD COLUMN IF NOT EXISTS `descrizione` TEXT;
ALTER TABLE `esperienze_lavorative` ADD COLUMN IF NOT EXISTS `data_inizio` DATE NOT NULL;
ALTER TABLE `esperienze_lavorative` ADD COLUMN IF NOT EXISTS `data_fine` DATE;
ALTER TABLE `esperienze_lavorative` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NOT NULL;

-- ===== TABELLE DI SISTEMA =====

-- Tabella per audit logging e sicurezza
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(50) NULL,
    `record_id` INT(11) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `details` JSON NULL,
    `status` ENUM('success', 'failed', 'warning') DEFAULT 'success',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggiorna tabella audit_logs
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `action` VARCHAR(100) NOT NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `table_name` VARCHAR(50) NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `record_id` INT(11) NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `ip_address` VARCHAR(45) NOT NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `user_agent` TEXT NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `details` JSON NULL;
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `status` ENUM('success', 'failed', 'warning') DEFAULT 'success';
ALTER TABLE `audit_logs` ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
