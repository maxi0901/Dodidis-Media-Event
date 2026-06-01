# Team-Profilbilder

Hier die echten, runden Profilbilder ablegen (Schwarz-Weiß / wird per CSS in Graustufen dargestellt).
Erwartete Dateinamen (von der „Über uns"-Sektion in `index.php` referenziert).
Die Endung darf in Groß-/Kleinschreibung beliebig sein (`.JPG`, `.jpg`, `.JPEG`,
`.jpeg`) – der Lader in `assets/site.js` probiert die Varianten automatisch durch:

- `timo-block.JPG`        – Timo Block  (aktuell hinterlegt)
- `raphael-dodidis.jpg`   – Raphael Dodidis  (aktuell hinterlegt)

Empfehlung: quadratisches Format (z. B. 440×440 px), motivzentriert (`object-position: center center`).
Solange keine Datei vorhanden ist, zeigt die Blase automatisch die Initialen (TB / RD) als Fallback.
