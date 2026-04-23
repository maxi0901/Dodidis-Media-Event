<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/tracking.sqlite';
$errorMessage = '';

$totals = [
    'page_views' => 0,
    'calendar_clicks' => 0,
    'today_page_views' => 0,
    'today_calendar_clicks' => 0,
];
$lastSevenDays = [];

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS landingpage_tracking (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landingpage_slug TEXT NOT NULL,
            event_type TEXT NOT NULL,
            source TEXT,
            campaign TEXT,
            ip_hash TEXT NOT NULL,
            user_agent_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime("now"))
        )'
    );

    $stmtTotals = $pdo->query(
        "SELECT
            SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) AS page_views,
            SUM(CASE WHEN event_type = 'calendar_click' THEN 1 ELSE 0 END) AS calendar_clicks,
            SUM(CASE WHEN event_type = 'page_view' AND date(created_at) = date('now') THEN 1 ELSE 0 END) AS today_page_views,
            SUM(CASE WHEN event_type = 'calendar_click' AND date(created_at) = date('now') THEN 1 ELSE 0 END) AS today_calendar_clicks
         FROM landingpage_tracking
         WHERE landingpage_slug = 'gutshof-maifest-2026'"
    );

    $resultTotals = $stmtTotals->fetch() ?: [];
    $totals = array_merge($totals, array_map(static fn($value): int => (int) $value, $resultTotals));

    $stmtDays = $pdo->query(
        "SELECT date(created_at) AS day,
            SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) AS page_views,
            SUM(CASE WHEN event_type = 'calendar_click' THEN 1 ELSE 0 END) AS calendar_clicks
         FROM landingpage_tracking
         WHERE landingpage_slug = 'gutshof-maifest-2026'
           AND date(created_at) >= date('now', '-6 days')
         GROUP BY date(created_at)
         ORDER BY day DESC"
    );

    $daysRaw = $stmtDays->fetchAll();
    $indexByDay = [];
    foreach ($daysRaw as $row) {
        $indexByDay[$row['day']] = [
            'page_views' => (int) ($row['page_views'] ?? 0),
            'calendar_clicks' => (int) ($row['calendar_clicks'] ?? 0),
        ];
    }

    for ($i = 0; $i < 7; $i++) {
        $day = (new DateTimeImmutable('today -' . $i . ' days'))->format('Y-m-d');
        $dayData = $indexByDay[$day] ?? ['page_views' => 0, 'calendar_clicks' => 0];
        $dayConversion = $dayData['page_views'] > 0 ? ($dayData['calendar_clicks'] / $dayData['page_views']) * 100 : 0;

        $lastSevenDays[] = [
            'day' => $day,
            'page_views' => $dayData['page_views'],
            'calendar_clicks' => $dayData['calendar_clicks'],
            'conversion' => $dayConversion,
        ];
    }
} catch (Throwable $e) {
    error_log('Stats error: ' . $e->getMessage());
    $errorMessage = 'Statistiken sind aktuell nicht verfügbar. Bitte später erneut versuchen.';
}

$totalConversion = $totals['page_views'] > 0 ? ($totals['calendar_clicks'] / $totals['page_views']) * 100 : 0;
$todayConversion = $totals['today_page_views'] > 0 ? ($totals['today_calendar_clicks'] / $totals['today_page_views']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stats · Maifest Gutshof Kassel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="logo" aria-label="Restaurant Gutshof">
        <span class="logo-top">RESTAURANT</span>
        <span class="logo-bottom">GUTSHOF</span>
    </div>
</header>
<main class="stats-main">
    <section class="hero stats-hero">
        <p class="eyebrow">Interne Statistik</p>
        <h1>Maifest Tracking</h1>
        <p class="subheadline">Landingpage: gutshof-maifest-2026</p>
    </section>

    <?php if ($errorMessage !== ''): ?>
        <section class="card warning-card">
            <h2>Hinweis</h2>
            <p><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        </section>
    <?php endif; ?>

    <section class="kpi-grid">
        <article class="card"><h2>Seitenaufrufe gesamt</h2><p class="kpi"><?php echo $totals['page_views']; ?></p></article>
        <article class="card"><h2>Kalender Klicks</h2><p class="kpi"><?php echo $totals['calendar_clicks']; ?></p></article>
        <article class="card"><h2>Conversion Rate</h2><p class="kpi"><?php echo number_format($totalConversion, 2, ',', '.'); ?>%</p></article>
    </section>

    <section>
        <h2>Heute</h2>
        <div class="card-grid">
            <article class="card"><h3>Page Views</h3><p class="kpi"><?php echo $totals['today_page_views']; ?></p></article>
            <article class="card"><h3>Kalender Klicks</h3><p class="kpi"><?php echo $totals['today_calendar_clicks']; ?></p></article>
            <article class="card"><h3>Conversion</h3><p class="kpi"><?php echo number_format($todayConversion, 2, ',', '.'); ?>%</p></article>
        </div>
    </section>

    <section>
        <h2>Letzte 7 Tage</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Seitenaufrufe</th>
                        <th>Kalender Klicks</th>
                        <th>Conversion</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lastSevenDays as $dayRow): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dayRow['day'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $dayRow['page_views']; ?></td>
                        <td><?php echo $dayRow['calendar_clicks']; ?></td>
                        <td><?php echo number_format($dayRow['conversion'], 2, ',', '.'); ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<footer>
    <p>Wir erfassen anonymisierte Klicks zur Erfolgsmessung.</p>
</footer>
</body>
</html>
