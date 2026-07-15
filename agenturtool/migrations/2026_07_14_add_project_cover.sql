-- Projekt-Vorschaubild (Cover). Zeigt auf das Cover-Asset (kind='cover');
-- wird im Projekt-Modal gesetzt und als Instagram-Reel-cover_url übergeben.
ALTER TABLE projects ADD COLUMN cover_asset_id VARCHAR(64) NULL;
