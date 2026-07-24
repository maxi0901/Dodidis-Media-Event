-- Neuer Projektstatus 'gepostet': wird gesetzt, sobald ein finales Video
-- tatsächlich veröffentlicht wurde (geplantes Posting published oder Direkt-Post).
-- Das Video verlässt damit die „zu posten"-Listen und landet in der
-- Gepostet-Spalte. Reihenfolge: nach 'freigegeben', vor 'archiviert'.
ALTER TABLE projects
  MODIFY COLUMN status
  ENUM('idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','gepostet','archiviert')
  NOT NULL DEFAULT 'skript';
