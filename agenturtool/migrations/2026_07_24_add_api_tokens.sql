-- Persönliche API-Zugriffstoken (für MCP-Server / externe Automatisierung).
-- Ein Token gehört genau einem Benutzer und handelt mit dessen Rechten. Nur der
-- SHA-256-Hash wird gespeichert (Klartext wird nur einmal bei Erstellung gezeigt).
CREATE TABLE IF NOT EXISTS api_tokens (
  id            VARCHAR(40)  NOT NULL,
  user_id       VARCHAR(64)  NOT NULL,
  token_hash    CHAR(64)     NOT NULL,
  label         VARCHAR(120) NOT NULL DEFAULT '',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at  DATETIME     NULL,
  revoked_at    DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_token_hash (token_hash),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
