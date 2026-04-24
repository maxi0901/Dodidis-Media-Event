<?php
declare(strict_types=1);

$landingpageSlug = 'gutshof-maifest-2026';
$dataFile = __DIR__ . '/data/tracking.json';
$errorMessage = '';
$events = [];

$totals = [
    'page_views' => 0,
    'calendar_clicks' => 0,
    'today_page_views' => 0,
    'today_calendar_clicks' => 0,
];
$lastSevenDays = [];

try {
    if (!file_exists($dataFile)) {
        $events = [];
    } else {
        $handle = fopen($dataFile, 'rb');
        if ($handle === false) {
            throw new RuntimeException('tracking.json konnte nicht geöffnet werden.');
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Datei-Lock für tracking.json fehlgeschlagen.');
            }

            $content = stream_get_contents($handle);
            $decoded = json_decode($content !== false ? $content : '[]', true);
            $events = is_array($decoded) ? $decoded : [];

            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    $tz = new DateTimeZone('Europe/Berlin');
    $today = (new DateTimeImmutable('now', $tz))->format('Y-m-d');
    $daysMap = [];

    for ($i = 0; $i < 7; $i++) {
        $day = (new DateTimeImmutable('today', $tz))->modify('-' . $i . ' days')->format('Y-m-d');
        $daysMap[$day] = ['page_views' => 0, 'calendar_clicks' => 0];
    }

    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }

        $eventSlug = (string)($event['landingpage_slug'] ?? $landingpageSlug);
        $eventType = (string)($event['event_type'] ?? '');

        if ($eventSlug !== $landingpageSlug || ($eventType !== 'page_view' && $eventType !== 'calendar_click')) {
            continue;
        }

        if ($eventType === 'page_view') {
            $totals['page_views']++;
        }
        if ($eventType === 'calendar_click') {
            $totals['calendar_clicks']++;
        }

        $createdAt = (string)($event['created_at'] ?? '');
        $eventDay = '';
        if ($createdAt !== '') {
            try {
                $eventDay = (new DateTimeImmutable($createdAt))->setTimezone($tz)->format('Y-m-d');
            } catch (Throwable $dateError) {
                $eventDay = '';
            }
        }

        if ($eventDay === $today) {
            if ($eventType === 'page_view') {
                $totals['today_page_views']++;
            } elseif ($eventType === 'calendar_click') {
                $totals['today_calendar_clicks']++;
            }
        }

        if ($eventDay !== '' && array_key_exists($eventDay, $daysMap)) {
            if ($eventType === 'page_view') {
                $daysMap[$eventDay]['page_views']++;
            } elseif ($eventType === 'calendar_click') {
                $daysMap[$eventDay]['calendar_clicks']++;
            }
        }
    }

    foreach ($daysMap as $day => $counts) {
        $conversion = $counts['page_views'] > 0
            ? ($counts['calendar_clicks'] / $counts['page_views']) * 100
            : 0;

        $lastSevenDays[] = [
            'day' => $day,
            'page_views' => $counts['page_views'],
            'calendar_clicks' => $counts['calendar_clicks'],
            'conversion' => $conversion,
        ];
    }
} catch (Throwable $exception) {
    error_log('Stats error: ' . $exception->getMessage());
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
    <meta name="robots" content="noindex,nofollow">
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
