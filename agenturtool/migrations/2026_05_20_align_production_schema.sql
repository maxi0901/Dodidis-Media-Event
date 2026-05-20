-- ============================================================================
-- Produktions-DB-Alignment: Fehlende Tabellen + Spalten-Umbenennungen
-- Idempotent – kann mehrfach ausgeführt werden (IF NOT EXISTS / IF EXISTS)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. customer_checklists – Checkliste als separate Tabelle (nicht auf customers)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customer_checklists (
  customer_id      VARCHAR(64) NOT NULL,
  contract_signed  TINYINT(1)  NOT NULL DEFAULT 0,
  deposit_received TINYINT(1)  NOT NULL DEFAULT 0,
  kickoff_done     TINYINT(1)  NOT NULL DEFAULT 0,
  social_access    TINYINT(1)  NOT NULL DEFAULT 0,
  first_shoot      TINYINT(1)  NOT NULL DEFAULT 0,
  PRIMARY KEY (customer_id),
  CONSTRAINT fk_cc_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bestehende Checklist-Daten aus customers migrieren (falls Spalten noch existieren)
INSERT IGNORE INTO customer_checklists
    (customer_id, contract_signed, deposit_received, kickoff_done, social_access, first_shoot)
SELECT id,
       COALESCE(contract_signed, 0),
       COALESCE(deposit_received, 0),
       COALESCE(kickoff_done, 0),
       COALESCE(social_access, 0),
       COALESCE(first_shoot, 0)
  FROM customers
 WHERE EXISTS (
    SELECT 1 FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = 'customers'
       AND column_name = 'contract_signed'
 );

-- ----------------------------------------------------------------------------
-- 2. todo_seen – Umbenennung von todo_seen_by → todo_seen
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS todo_seen (
  todo_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(64) NOT NULL,
  seen_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (todo_id, user_id),
  CONSTRAINT fk_tsn_todo FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
  CONSTRAINT fk_tsn_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daten aus todo_seen_by übernehmen (falls Tabelle noch existiert)
INSERT IGNORE INTO todo_seen (todo_id, user_id, seen_at)
SELECT todo_id, user_id,
       COALESCE(seen_at, CURRENT_TIMESTAMP)
  FROM todo_seen_by
 WHERE EXISTS (
    SELECT 1 FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name = 'todo_seen_by'
 );

-- ----------------------------------------------------------------------------
-- 3. todos.created_by → todos.created_by_id
-- ----------------------------------------------------------------------------
-- Neue Spalte anlegen (falls noch nicht vorhanden)
ALTER TABLE todos
  ADD COLUMN IF NOT EXISTS created_by_id VARCHAR(64) NULL
    COMMENT 'FK zu users.id – wer hat das Todo angelegt',
  ADD CONSTRAINT IF NOT EXISTS fk_t_created_by_id
    FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL;

-- Daten übertragen (falls created_by noch existiert)
UPDATE todos t
   SET t.created_by_id = t.created_by
 WHERE t.created_by_id IS NULL
   AND t.created_by IS NOT NULL
   AND EXISTS (
       SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'todos'
          AND column_name = 'created_by'
   );

-- ----------------------------------------------------------------------------
-- 4. customers.package → customers.package_name
-- ----------------------------------------------------------------------------
ALTER TABLE customers
  ADD COLUMN IF NOT EXISTS package_name VARCHAR(190) NULL
    COMMENT 'Paket-/Planname (ehemals: package)';

-- Daten übertragen (falls package noch existiert)
UPDATE customers
   SET package_name = package
 WHERE package_name IS NULL
   AND package IS NOT NULL
   AND EXISTS (
       SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'customers'
          AND column_name = 'package'
   );
