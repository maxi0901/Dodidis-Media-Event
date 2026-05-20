-- ============================================================================
-- Migration: add missing tables activity_log, project_files, app_config
-- Background: Production DB is missing these three tables that are referenced
-- throughout the backend. Every POST/PUT/DELETE crashes because log_activity()
-- tries to INSERT into activity_log; file uploads fail on project_files INSERT;
-- api.php?action=pull fails on app_config SELECT.
-- Idempotent via CREATE TABLE IF NOT EXISTS — safe to run multiple times.
-- Deploy: mysql <db> < migrations/2026_05_20_add_activity_log_project_files_app_config.sql
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- activity_log
-- Referenced in:
--   includes/helpers.php:100  log_activity(): INSERT (scope, ref_id, action, actor_id, details)
--   api/dashboard.php:26      MAX(ts) for last_change ETag
--   api/dashboard.php:47      SELECT ts, scope, ref_id, action, actor_id ORDER BY id DESC LIMIT 20
-- Indexes: idx_al_ts covers MAX(ts); PK covers ORDER BY id DESC.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
  id        BIGINT      NOT NULL AUTO_INCREMENT,
  ts        DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  scope     VARCHAR(32) NOT NULL,
  ref_id    VARCHAR(64) NULL,
  action    VARCHAR(64) NOT NULL,
  actor_id  VARCHAR(64) NULL,
  details   JSON        NULL,
  PRIMARY KEY (id),
  KEY idx_al_scope_ref (scope, ref_id),
  KEY idx_al_ts (ts),
  CONSTRAINT fk_al_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- project_files
-- Referenced in:
--   api/upload.php:83       INSERT (project_id, kind, filename, mime, size, path, uploaded_by)
--   api/projects.php:186    SELECT id, kind, filename, mime, size, path, uploaded_by, uploaded_at
--                            WHERE project_id = ? ORDER BY uploaded_at DESC
-- Index on (project_id, uploaded_at) covers the WHERE + ORDER BY in list_files().
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_files (
  id          BIGINT       NOT NULL AUTO_INCREMENT,
  project_id  VARCHAR(64)  NOT NULL,
  kind        ENUM('script','contract','correction','other','avatar') NOT NULL DEFAULT 'other',
  filename    VARCHAR(255) NOT NULL,
  mime        VARCHAR(96)  NOT NULL,
  size        INT UNSIGNED NOT NULL,
  path        VARCHAR(255) NOT NULL,
  uploaded_by VARCHAR(64)  NULL,
  uploaded_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pf_project (project_id, uploaded_at),
  CONSTRAINT fk_pf_project FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_pf_user    FOREIGN KEY (uploaded_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- app_config
-- Referenced in:
--   api.php:229    SELECT `key`, `value` FROM app_config  (pull action)
--   api.php:584    INSERT ... ON DUPLICATE KEY UPDATE `value`  (push action)
--   migrate_legacy.php:293  INSERT ... ON DUPLICATE KEY UPDATE `value`
-- PK on `key` enables the upsert pattern; updated_at auto-tracks changes.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS app_config (
  `key`      VARCHAR(64) NOT NULL,
  `value`    JSON        NOT NULL,
  updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
