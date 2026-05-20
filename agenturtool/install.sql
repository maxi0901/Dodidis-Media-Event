-- ============================================================================
-- Dodidis Media Agenturtool – normalisiertes Schema
-- MySQL 8 / InnoDB / utf8mb4
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS todo_seen;
DROP TABLE IF EXISTS todo_assignees;
DROP TABLE IF EXISTS project_files;
DROP TABLE IF EXISTS todos;
DROP TABLE IF EXISTS vacations;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS shoot_days;
DROP TABLE IF EXISTS customer_checklists;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS app_config;

-- ----------------------------------------------------------------------------
-- users
-- ----------------------------------------------------------------------------
CREATE TABLE users (
  id              VARCHAR(64)  NOT NULL,
  username        VARCHAR(64)  NOT NULL,
  name            VARCHAR(128) NOT NULL,
  email           VARCHAR(190) NULL,
  password_hash   VARCHAR(255) NOT NULL,
  avatar_color    VARCHAR(7)   DEFAULT '#cccccc',
  avatar_image    LONGTEXT     NULL,
  calendar_prefs  JSON         NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- user_roles (M:N)
-- ----------------------------------------------------------------------------
CREATE TABLE user_roles (
  user_id   VARCHAR(64) NOT NULL,
  role_name ENUM('admin','manager','videograf','cutter','mitarbeiter') NOT NULL,
  PRIMARY KEY (user_id, role_name),
  KEY idx_user_roles_role_name (role_name),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- customers
-- ----------------------------------------------------------------------------
CREATE TABLE customers (
  id                       VARCHAR(64)  NOT NULL,
  name                     VARCHAR(190) NOT NULL,
  customer_number          VARCHAR(32)  NOT NULL,
  manager_id               VARCHAR(64)  NULL,
  pin_hash                 VARCHAR(255) NULL,
  email                    VARCHAR(190) NULL,
  phone                    VARCHAR(64)  NULL,
  contact_name             VARCHAR(190) NULL,
  social_instagram         VARCHAR(190) NULL,
  social_tiktok            VARCHAR(190) NULL,
  notes                    TEXT         NULL,
  address_street           VARCHAR(190) NULL,
  address_zip              VARCHAR(16)  NULL,
  address_city             VARCHAR(96)  NULL,
  billing_same_as_address  TINYINT(1)   NOT NULL DEFAULT 1,
  billing_street           VARCHAR(190) NULL,
  billing_zip              VARCHAR(16)  NULL,
  billing_city             VARCHAR(96)  NULL,
  vat_id                   VARCHAR(64)  NULL,
  billing_email            VARCHAR(190) NULL,
  contract_start           DATE         NULL,
  package_name             VARCHAR(190) NULL,
  monthly_rate             DECIMAL(10,2) NULL,
  videos_per_month         SMALLINT     NULL,
  status                   ENUM('onboarding','active','paused') NULL,
  created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_customers_number (customer_number),
  KEY idx_customers_manager (manager_id),
  KEY idx_customers_status (status),
  CONSTRAINT fk_cust_manager FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- customer_checklists (1:1 zu customers)
-- ----------------------------------------------------------------------------
CREATE TABLE customer_checklists (
  customer_id      VARCHAR(64) NOT NULL,
  contract_signed  TINYINT(1)  NOT NULL DEFAULT 0,
  deposit_received TINYINT(1)  NOT NULL DEFAULT 0,
  kickoff_done     TINYINT(1)  NOT NULL DEFAULT 0,
  social_access    TINYINT(1)  NOT NULL DEFAULT 0,
  first_shoot      TINYINT(1)  NOT NULL DEFAULT 0,
  PRIMARY KEY (customer_id),
  CONSTRAINT fk_cc_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- shoot_days
-- ----------------------------------------------------------------------------
CREATE TABLE shoot_days (
  id            VARCHAR(64) NOT NULL,
  date          DATE NOT NULL,
  start_time    TIME NULL,
  end_time      TIME NULL,
  videograf_id  VARCHAR(64) NULL,
  customer_id   VARCHAR(64) NULL,
  note          TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sd_date (date),
  KEY idx_sd_videograf (videograf_id),
  KEY idx_sd_customer (customer_id),
  CONSTRAINT fk_sd_videograf FOREIGN KEY (videograf_id) REFERENCES users(id)     ON DELETE SET NULL,
  CONSTRAINT fk_sd_customer  FOREIGN KEY (customer_id)  REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- projects
-- ----------------------------------------------------------------------------
CREATE TABLE projects (
  id            VARCHAR(64)  NOT NULL,
  title         VARCHAR(190) NOT NULL,
  customer_id   VARCHAR(64)  NULL,
  videograf_id  VARCHAR(64)  NULL,
  cutter_id     VARCHAR(64)  NULL,
  shoot_date    DATE         NULL,
  shoot_day_id  VARCHAR(64)  NULL,
  deadline      DATE         NULL,
  posting_date  DATE         NULL,
  script        TEXT         NULL,
  status        ENUM('skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert') NOT NULL DEFAULT 'skript',
  is_internal   TINYINT(1)   NOT NULL DEFAULT 0,
  approved_at   DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_p_status (status),
  KEY idx_p_customer (customer_id),
  KEY idx_p_videograf (videograf_id),
  KEY idx_p_cutter (cutter_id),
  KEY idx_p_deadline (deadline),
  KEY idx_p_shoot_date (shoot_date),
  CONSTRAINT fk_p_customer  FOREIGN KEY (customer_id)  REFERENCES customers(id)  ON DELETE SET NULL,
  CONSTRAINT fk_p_videograf FOREIGN KEY (videograf_id) REFERENCES users(id)      ON DELETE SET NULL,
  CONSTRAINT fk_p_cutter    FOREIGN KEY (cutter_id)    REFERENCES users(id)      ON DELETE SET NULL,
  CONSTRAINT fk_p_shootday  FOREIGN KEY (shoot_day_id) REFERENCES shoot_days(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- project_files
-- ----------------------------------------------------------------------------
CREATE TABLE project_files (
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
-- todos
-- ----------------------------------------------------------------------------
CREATE TABLE todos (
  id             VARCHAR(64)  NOT NULL,
  title          VARCHAR(255) NOT NULL,
  description    TEXT         NULL,
  created_by_id  VARCHAR(64)  NULL,
  due_date       DATE         NULL,
  status         ENUM('open','done') NOT NULL DEFAULT 'open',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_t_status (status),
  KEY idx_t_due (due_date),
  CONSTRAINT fk_t_created_by_id FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE todo_assignees (
  todo_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(64) NOT NULL,
  PRIMARY KEY (todo_id, user_id),
  KEY idx_ta_user (user_id),
  CONSTRAINT fk_ta_todo FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE todo_seen (
  todo_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(64) NOT NULL,
  seen_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (todo_id, user_id),
  CONSTRAINT fk_ts_todo FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- vacations
-- ----------------------------------------------------------------------------
CREATE TABLE vacations (
  id          VARCHAR(64) NOT NULL,
  user_id     VARCHAR(64) NOT NULL,
  start_date  DATE NOT NULL,
  end_date    DATE NOT NULL,
  note        VARCHAR(255) NULL,
  approved_by VARCHAR(64) NULL,
  approved_at DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_v_user (user_id),
  KEY idx_v_range (start_date, end_date),
  CONSTRAINT fk_v_user     FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_v_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- activity_log
-- ----------------------------------------------------------------------------
CREATE TABLE activity_log (
  id        BIGINT NOT NULL AUTO_INCREMENT,
  ts        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  scope     VARCHAR(32) NOT NULL,
  ref_id    VARCHAR(64) NULL,
  action    VARCHAR(64) NOT NULL,
  actor_id  VARCHAR(64) NULL,
  details   JSON NULL,
  PRIMARY KEY (id),
  KEY idx_al_scope_ref (scope, ref_id),
  KEY idx_al_ts (ts),
  CONSTRAINT fk_al_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- app_config (Key-Value für globale Settings)
-- ----------------------------------------------------------------------------
CREATE TABLE app_config (
  `key`      VARCHAR(64) NOT NULL,
  `value`    JSON NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
