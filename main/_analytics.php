<?php
declare(strict_types=1);

require_once __DIR__ . '/_app.php';
require_login();

function analytics_table_exists(PDO $pdo, string $table): bool
{
    $allowed = [
        'facilities',
        'equipment',
        'facility_condition_assessments',
        'equipment_condition_assessments',
        'maintenance_records',
        'downtime_events',
        'lifecycle_events',
        'activity_log',
    ];

    if (!in_array($table, $allowed, true)) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT to_regclass(:relation) IS NOT NULL');
    $stmt->execute([':relation' => 'public.' . $table]);

    return (bool)$stmt->fetchColumn();
}

function analytics_column_exists(PDO $pdo, string $table, string $column): bool
{
    $allowed = [
        'facilities' => [
            'id', 'name', 'location', 'category', 'date_acquired',
            'condition', 'status', 'lifecycle_stage', 'useful_life_years',
            'acquisition_cost', 'replacement_cost_estimate', 'criticality',
            'warranty_expiry', 'department', 'retired_at', 'disposed_at',
        ],
        'equipment' => [
            'id', 'facility_id', 'name', 'asset_tag', 'brand', 'model',
            'category', 'location', 'date_acquired', 'condition', 'status',
            'lifecycle_stage', 'useful_life_years', 'acquisition_cost',
            'replacement_cost_estimate', 'criticality', 'warranty_expiry',
            'department', 'retired_at', 'disposed_at',
        ],
        'facility_condition_assessments' => [
            'id', 'facility_id', 'condition', 'status', 'notes',
            'assessed_by', 'assessed_at',
        ],
        'equipment_condition_assessments' => [
            'id', 'equipment_id', 'condition', 'status', 'notes',
            'assessed_by', 'assessed_at',
        ],
        'maintenance_records' => [
            'id', 'external_id', 'equipment_id', 'maintenance_type',
            'status', 'priority', 'due_at', 'started_at', 'completed_at',
            'labor_cost', 'parts_cost', 'failure_cause',
        ],
        'downtime_events' => [
            'id', 'facility_id', 'equipment_id', 'started_at',
            'ended_at', 'reason', 'notes',
        ],
        'lifecycle_events' => [
            'id', 'facility_id', 'equipment_id', 'event_type',
            'event_date', 'details',
        ],
        'activity_log' => [
            'id', 'facility_id', 'equipment_id', 'action',
            'details', 'actor', 'created_at',
        ],
    ];

    if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = :schema
              AND table_name = :table
              AND column_name = :column
        )'
    );
    $stmt->execute([
        ':schema' => 'public',
        ':table' => $table,
        ':column' => $column,
    ]);

    return (bool)$stmt->fetchColumn();
}
function analytics_date(string $value, string $fallback): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : $fallback;
}

$analyticsToday = new DateTimeImmutable('today');
$analyticsDefaultFrom = $analyticsToday->modify('-11 months')->modify('first day of this month')->format('Y-m-d');
$analyticsDefaultTo = $analyticsToday->format('Y-m-d');
$analyticsFrom = analytics_date((string)($_GET['from'] ?? ''), $analyticsDefaultFrom);
$analyticsTo = analytics_date((string)($_GET['to'] ?? ''), $analyticsDefaultTo);
if ($analyticsFrom > $analyticsTo) {
    [$analyticsFrom, $analyticsTo] = [$analyticsTo, $analyticsFrom];
}
$analyticsType = in_array((string)($_GET['type'] ?? 'all'), ['all', 'equipment', 'facility'], true) ? (string)($_GET['type'] ?? 'all') : 'all';
$analyticsCondition = in_array((string)($_GET['condition'] ?? 'all'), ['all', 'Good', 'Fair', 'Poor'], true) ? (string)($_GET['condition'] ?? 'all') : 'all';
$analyticsSearch = trim(substr((string)($_GET['q'] ?? ''), 0, 100));

function analytics_json(mixed $value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function analytics_money(float|int|null $value): string
{
    return '₱' . number_format((float)($value ?? 0), 2);
}

function analytics_date_label(?string $value): string
{
    if (!$value) return '—';
    try { return (new DateTimeImmutable($value))->format('M j, Y'); }
    catch (Throwable) { return '—'; }
}

function analytics_months(string $from, string $to): array
{
    $start = new DateTimeImmutable($from);
    $end = new DateTimeImmutable($to);
    $cursor = $start->modify('first day of this month');
    $last = $end->modify('first day of this month');
    $months = [];
    while ($cursor <= $last && count($months) < 60) {
        $months[] = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('M Y')];
        $cursor = $cursor->modify('+1 month');
    }
    return $months;
}

function analytics_query_or_empty(PDO $pdo, string $sql, array $params = []): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ['ok' => true, 'rows' => $stmt->fetchAll()];
    } catch (PDOException $e) {
        error_log('LifeCycleTrack analytics query failed: ' . $e->getMessage());
        return ['ok' => false, 'rows' => []];
    }
}

function analytics_page_start(string $title, string $description): void
{
    $safeTitle = e($title);
    $safeDescription = e($description);
    echo '<!doctype html><html lang="en"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . $safeTitle . ' | LifeCycleTrack</title>';
    echo '<link rel="stylesheet" href="../src/output.css">';
    echo '<link rel="stylesheet" href="../css/sidebar.css">';
    echo '<link rel="stylesheet" href="../css/analytics.css">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
    echo '</head><body>';
    include __DIR__ . '/sidebar.php';
    echo '<main class="analytics-main"><header class="analytics-header"><div><p class="analytics-eyebrow">Reports / Decision support</p><h1>' . $safeTitle . '</h1><p class="analytics-subtitle">' . $safeDescription . '</p></div><div class="header-actions no-print"><span class="data-stamp">Generated ' . e(date('M j, Y g:i A')) . '</span><button type="button" class="analytics-button" onclick="window.print()">Print report</button></div></header>';
}

function analytics_filter_form(string $from, string $to, string $type, string $condition, string $q, bool $includeCondition = true): void
{
    echo '<form class="analytics-filters no-print" method="get">';
    echo '<label>From<input type="date" name="from" value="' . e($from) . '"></label>';
    echo '<label>To<input type="date" name="to" value="' . e($to) . '"></label>';
    echo '<label>Asset type<select name="type"><option value="all"' . ($type === 'all' ? ' selected' : '') . '>All types</option><option value="equipment"' . ($type === 'equipment' ? ' selected' : '') . '>Equipment</option><option value="facility"' . ($type === 'facility' ? ' selected' : '') . '>Facility</option></select></label>';
    if ($includeCondition) {
        echo '<label>Condition<select name="condition"><option value="all"' . ($condition === 'all' ? ' selected' : '') . '>All conditions</option><option' . ($condition === 'Good' ? ' selected' : '') . '>Good</option><option' . ($condition === 'Fair' ? ' selected' : '') . '>Fair</option><option' . ($condition === 'Poor' ? ' selected' : '') . '>Poor</option></select></label>';
    }
    echo '<label class="search-field">Search<input type="search" name="q" maxlength="100" value="' . e($q) . '" placeholder="Asset, location, category..."></label>';
    echo '<button class="analytics-button" type="submit">Apply filters</button><a class="analytics-reset" href="' . e(basename($_SERVER['PHP_SELF'])) . '">Reset</a></form>';
}

function analytics_notice(string $message, string $kind = 'info'): void
{
    echo '<strong><div class="analytics-notice ' . e($kind) . '">' . e($message) . '</div></strong>';
}

function analytics_metric(string $label, string $value, string $note, string $icon = '•'): void
{
    echo '<article class="analytics-metric"><div class="metric-top"><span>' . e($label) . '</span><span class="metric-icon" aria-hidden="true">' . e($icon) . '</span></div><div class="metric-value">' . e($value) . '</div><p>' . e($note) . '</p></article>';
}

function analytics_chart_panel(string $title, string $description, string $canvasId, bool $wide = false): void
{
    echo '<section class="analytics-panel' . ($wide ? ' wide' : '') . '"><div class="panel-heading"><h2>' . e($title) . '</h2><p>' . e($description) . '</p></div><div class="chart-wrap"><canvas id="' . e($canvasId) . '" aria-label="' . e($title) . '" role="img"></canvas></div></section>';
}

function analytics_table_start(array $headers): void
{
    echo '<section class="analytics-panel table-panel"><div class="panel-heading"><h2>Records behind the numbers</h2><p>Select a record in your asset register for full history and supporting documents.</p></div><div class="table-scroll"><table><thead><tr>';
    foreach ($headers as $header) echo '<th>' . e((string)$header) . '</th>';
    echo '</tr></thead><tbody>';
}

function analytics_table_end(): void
{
    echo '</tbody></table></div></section>';
}

function analytics_pill(string $value): string
{
    $class = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $value) ?? 'unknown');
    return '<span class="analytics-pill ' . e($class) . '">' . e($value ?: 'Unknown') . '</span>';
}

function analytics_page_end(): void
{
    echo '<footer class="analytics-footer">LifeCycleTrack · Decision-support analytics · Verify critical findings against source records before approving action.</footer></main></body></html>';
}