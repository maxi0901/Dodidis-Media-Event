-- Test-Reel: markiert einen geplanten Post als Test. Test-Reels werden per
-- Schnell-Aktion mit scheduled_at = jetzt + 24/48 h eingeplant und laufen über
-- dieselbe Publish-Kette (publish_due.php). Das Flag dient der optischen
-- Kennzeichnung im Posting-Planer und späteren Auswertungen.
ALTER TABLE content_queue ADD COLUMN is_test TINYINT(1) NOT NULL DEFAULT 0;
