-- Interne Rolle 'support' (Supportmitarbeiter, Superuser für Fehleranalyse).
-- WICHTIG: Diese Rolle wird NUR direkt in der Datenbank vergeben — die App-API
-- (users.php) akzeptiert sie NICHT und kann sie weder setzen noch entfernen.
-- Vergabe von Hand, z. B.:
--   INSERT INTO user_roles (user_id, role_name) VALUES ('<USER_ID>', 'support');
ALTER TABLE user_roles
  MODIFY COLUMN role_name
  ENUM('admin','manager','videograf','cutter','mitarbeiter','contract_uploader','support') NOT NULL;
