-- ============================================================
-- Agenturtool – Datenbank-Schema (MySQL 8.x)
-- Einmalig in phpMyAdmin auf der DB `k275333_dodidis-Media` ausführen.
-- ACHTUNG: alle bestehenden Tabellen mit diesen Namen werden ersetzt.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `todo_assignees`;
DROP TABLE IF EXISTS `todo_seen`;
DROP TABLE IF EXISTS `customer_checklists`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `vacations`;
DROP TABLE IF EXISTS `todos`;
DROP TABLE IF EXISTS `shoot_days`;
DROP TABLE IF EXISTS `app_config`;

SET FOREIGN_KEY_CHECKS = 1;

-- Daten-Tabellen: jeder Datensatz wird als JSON-Dokument gespeichert.
-- Der Primärschlüssel `id` entspricht der client-seitig generierten ID
-- (z. B. "u_raphael", "c_x5ixucw", "p_…").

CREATE TABLE `users` (
  `id`         VARCHAR(64) NOT NULL PRIMARY KEY,
  `data`       JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `id`         VARCHAR(64) NOT NULL PRIMARY KEY,
  `data`       JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id`         VARCHAR(64) NOT NULL PRIMARY KEY,
  `data`       JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vacations` (
  `id`         VARCHAR(64) NOT NULL PRIMARY KEY,
  `data`       JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `todos` (
  `id`         VARCHAR(64) NOT NULL PRIMARY KEY,
  `data`       JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shoot_days` (
  `id`         VARCHAR(64) NOT NULL PRIMARY KEY,
  `data`       JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Key/Value-Tabelle für globale Konfiguration (calendarConfig, activity-Log …)
CREATE TABLE `app_config` (
  `key`        VARCHAR(64) NOT NULL PRIMARY KEY,
  `value`      JSON        NOT NULL,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
