-- Resumable Upload: am vServer gepufferte Dateien, die im Hintergrund auf den
-- NAS gesichert werden. Kurzlebig — Eintrag verschwindet, sobald die Datei auf
-- dem NAS liegt (nas_assets.php räumt auf).
CREATE TABLE IF NOT EXISTS pending_uploads (
  id           VARCHAR(128) NOT NULL,
  project_id   VARCHAR(64)  NOT NULL,
  kind         VARCHAR(16)  NOT NULL,
  filename     VARCHAR(255) NOT NULL,
  size_bytes   BIGINT UNSIGNED NULL,
  uploaded_by  VARCHAR(64)  NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pu_project (project_id),
  KEY idx_pu_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
