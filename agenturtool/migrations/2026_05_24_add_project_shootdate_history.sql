CREATE TABLE IF NOT EXISTS project_shootdate_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id VARCHAR(64) NOT NULL,
  old_shoot_date DATE NOT NULL,
  new_shoot_date DATE NOT NULL,
  changed_by VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_psh_project (project_id),
  KEY idx_psh_old_date (old_shoot_date),
  KEY idx_psh_new_date (new_shoot_date),
  CONSTRAINT fk_psh_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_psh_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
