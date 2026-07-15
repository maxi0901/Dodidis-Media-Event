-- Kommunikation: WhatsApp-Team-Posteingang (Cloud API).
CREATE TABLE IF NOT EXISTS wa_conversations (
  id              VARCHAR(64)  NOT NULL PRIMARY KEY,
  wa_id           VARCHAR(32)  NOT NULL,
  name            VARCHAR(190) NULL,
  last_message_at DATETIME     NULL,
  last_preview    VARCHAR(255) NULL,
  last_direction  ENUM('in','out') NULL,
  unread          INT NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wa_id (wa_id),
  KEY idx_wa_last (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_messages (
  id              VARCHAR(64)  NOT NULL PRIMARY KEY,
  conversation_id VARCHAR(64)  NOT NULL,
  wa_message_id   VARCHAR(128) NULL,
  direction       ENUM('in','out') NOT NULL,
  type            VARCHAR(24)  NOT NULL DEFAULT 'text',
  body            TEXT NULL,
  status          VARCHAR(24)  NULL,
  wa_timestamp    DATETIME NULL,
  sent_by         VARCHAR(64)  NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wamid (wa_message_id),
  KEY idx_wam_conv (conversation_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
