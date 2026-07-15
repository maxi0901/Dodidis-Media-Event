-- Projekt-Vorschaubild (Cover). Zeigt auf das Cover-Asset (kind='cover');
-- wird im Projekt-Modal gesetzt und als Instagram-Reel-cover_url übergeben.
ALTER TABLE projects ADD COLUMN cover_asset_id VARCHAR(64) NULL;

-- Cover-Assets brauchen den kind-Wert 'cover'.
ALTER TABLE assets MODIFY COLUMN kind ENUM('raw','final','cover') NOT NULL DEFAULT 'raw';
