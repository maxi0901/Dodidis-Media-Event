-- ============================================================================
-- Seed: Bestandsuser mit existierenden sha256$-Hashes (aus agentur-backup-2026-05-19.json)
-- Achtung: idempotent via ON DUPLICATE KEY UPDATE
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO users (id, username, name, email, password_hash) VALUES
 ('u_raphael',  'Raphael',  'Raphael Dodidis',     'kontakt@dodidis-media.de', 'sha256$d59642bccca172e785cd7eb3df7cd5ef0943da07d999476ab4afe5f19362a53b'),
 ('u_f2j54ck',  'Leonie',   'Leonie Stella Christ', NULL,                       'sha256$261e470e0307050ae4925409616e322903f6824bfba2cfacfb61a8d8384cdf56'),
 ('u_06hdf5r',  'Timo',     'Timo Block',           'kontakt@dodidis-media.de', 'sha256$23383b44e0049f2bc3bb0ed5823581700aaa92c59a84c8b7041665e16345bec6'),
 ('u_163qsdr',  'Dennis',   'Dennis Ryhs',          NULL,                       'sha256$b56f3b8a7b87e7f880091bc76972621b62fa75039688a6585f3c9ac11b1b0891'),
 ('u_qof4x9n',  'Gokilian', 'Gokilian',             NULL,                       'sha256$cac3f56ce5bfd492abf5bc7782eb92e80e9d71b3e8290dcec084f2df8c5f5289'),
 ('u_uya5a9e',  'Maxim',    'Maxim Tokarski',       NULL,                       'sha256$b56f3b8a7b87e7f880091bc76972621b62fa75039688a6585f3c9ac11b1b0891')
ON DUPLICATE KEY UPDATE
  username      = VALUES(username),
  name          = VALUES(name),
  email         = VALUES(email),
  password_hash = VALUES(password_hash);

INSERT INTO user_roles (user_id, role) VALUES
 ('u_raphael',  'admin'),
 ('u_raphael',  'manager'),
 ('u_raphael',  'videograf'),
 ('u_raphael',  'cutter'),
 ('u_f2j54ck',  'cutter'),
 ('u_06hdf5r',  'manager'),
 ('u_06hdf5r',  'videograf'),
 ('u_06hdf5r',  'cutter'),
 ('u_163qsdr',  'cutter'),
 ('u_qof4x9n',  'cutter'),
 ('u_uya5a9e',  'mitarbeiter')
ON DUPLICATE KEY UPDATE role = VALUES(role);
