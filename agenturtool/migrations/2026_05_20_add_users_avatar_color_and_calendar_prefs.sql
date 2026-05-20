-- ============================================================================
-- Migration: add missing user columns
-- Background: Production users table is missing avatar_color and
-- calendar_prefs, but SELECT/INSERT queries reference both (PHP error 1054).
-- Idempotent via information_schema check.
-- ============================================================================

SET @db := DATABASE();

SET @has_avatar_color := (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'users' AND column_name = 'avatar_color'
);
SET @stmt := IF(@has_avatar_color = 0,
  "ALTER TABLE users ADD COLUMN avatar_color VARCHAR(7) DEFAULT '#cccccc'",
  "DO 0"
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_calendar_prefs := (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = @db AND table_name = 'users' AND column_name = 'calendar_prefs'
);
SET @stmt := IF(@has_calendar_prefs = 0,
  "ALTER TABLE users ADD COLUMN calendar_prefs JSON NULL",
  "DO 0"
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
