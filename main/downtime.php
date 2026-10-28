<?php

declare(strict_types=1);
require_once __DIR__ . '/_analytics.php';
$hasDowntime = analytics_table_exists($pdo, 'downtime_events');
$rows = [];
$queryOk = true;
if ($hasDowntime) {
    $where = [
        'd.started_at >= CAST(:date_from AS date)',
        "d.started_at < (CAST(:date_to AS date) + INTERVAL '1 day')",
    ];

    $params = [
        ':date_from' => $analyticsFrom,
        ':date_to' => $analyticsTo,
        ':duration_to' => $analyticsTo,
    ];

    if ($analyticsType === 'equipment') {
        $where[] = 'e.id IS NOT NULL';
    } elseif ($analyticsType === 'facility') {
        $where[] = 'd.facility_id IS NOT NULL';
    }

    if ($analyticsCondition !== 'all') {
        $where[] = 'COALESCE(e.condition, f.condition) = :condition';
        $params[':condition'] = $analyticsCondition;
    }

    if ($analyticsSearch !== '') {
        $where[] = "(
        COALESCE(e.name, '') ILIKE :q1
        OR COALESCE(f.name, '') ILIKE :q2
        OR COALESCE(e.location, f.location, '') ILIKE :q3
        OR COALESCE(d.reason, '') ILIKE :q4
        OR COALESCE(d.notes, '') ILIKE :q5
    )";

        foreach ([':q1', ':q2', ':q3', ':q4', ':q5'] as $key) {
            $params[$key] = '%' . $analyticsSearch . '%';
        }
    }

    $sql = "
    SELECT
        d.id,
        'DWN-' || LPAD(d.id::text, 6, '0') AS external_id,
        COALESCE(d.reason, d.notes, 'Downtime event') AS title,
        e.asset_tag AS external_asset_tag,
        COALESCE(d.equipment_id, d.facility_id) AS asset_id,
        CASE
            WHEN e.id IS NOT NULL THEN 'equipment'
            WHEN d.facility_id IS NOT NULL THEN 'facility'
            ELSE 'unknown'
        END AS asset_type,
        COALESCE(e.name, f.name, 'Unlinked asset/facility') AS asset_name,
        COALESCE(e.location, f.location, '—') AS location,
        'Unspecified'::text AS severity,
        CASE
            WHEN d.ended_at IS NULL THEN 'Ongoing'
            ELSE 'Restored'
        END AS status,
        COALESCE(d.reason, d.notes, 'Unclassified') AS cause,
        FALSE AS planned,
        d.started_at,
        d.ended_at AS restored_at,
        GREATEST(
            EXTRACT(EPOCH FROM (
                LEAST(
                    COALESCE(d.ended_at, NOW()),
                    CAST(:duration_to AS date) + INTERVAL '1 day'
                ) - d.started_at
            )) / 3600,
            0
        ) AS downtime_hours,
        CASE
            WHEN d.ended_at IS NOT NULL THEN
                GREATEST(EXTRACT(EPOCH FROM (d.ended_at - d.started_at)) / 3600, 0)
            ELSE NULL
        END AS full_restore_hours
    FROM public.downtime_events d
    LEFT JOIN public.equipment e ON e.id = d.equipment_id
    LEFT JOIN public.facilities f
        ON f.id = COALESCE(d.facility_id, e.facility_id)
    WHERE " . implode(' AND ', $where) . "
    ORDER BY d.started_at DESC
";

    $result = analytics_query_or_empty($pdo, $sql, $params);
    $rows = $result['rows'];
    $queryOk = $result['ok'];
}
$ongoing = array_filter($rows, fn($r) => empty($r['restored_at']) && !in_array(strtolower((string)$r['status']), ['restored', 'closed', 'resolved'], true));
$criticalOngoing = array_filter($ongoing, fn($r) => strtolower((string)$r['severity']) === 'critical');
$totalHours = array_sum(array_map(fn($r) => (float)$r['downtime_hours'], $rows));
$restored = array_filter($rows, fn($r) => !empty($r['restored_at']) || in_array(strtolower((string)$r['status']), ['restored', 'closed', 'resolved'], true));
$restoredWithTimestamp = array_filter($rows, fn($r) => !empty($r['restored_at']));
$avgRestore = count($restoredWithTimestamp) ? array_sum(array_map(fn($r) => (float)$r['full_restore_hours'], $restoredWithTimestamp)) / count($restoredWithTimestamp) : null;
$severityLabels = ['Unspecified'];
$severityValues = [count($rows)];
$statusLabels = ['Ongoing', 'Restored'];
$statusValues = [count($ongoing), count($restored)];
$months = analytics_months($analyticsFrom, $analyticsTo);
$monthlyMap = [];
foreach ($rows as $r) {
    $key = substr((string)$r['started_at'], 0, 7);
    $monthlyMap[$key] = ($monthlyMap[$key] ?? 0) + (float)$r['downtime_hours'];
}
$monthlyLabels = array_column($months, 'label');
$monthlyValues = array_map(fn($m) => round($monthlyMap[$m['key']] ?? 0, 2), $months);
$causes = [];
foreach ($rows as $r) {
    $cause = trim((string)($r['cause'] ?? '')) ?: 'Unclassified';
    $causes[$cause] = ($causes[$cause] ?? 0) + (float)$r['downtime_hours'];
}
arsort($causes);
$topCauses = array_slice($causes, 0, 7, true);

analytics_page_start('Downtime Analysis', 'Measure service interruptions, identify recurring causes, and prioritize reliability improvements.');
analytics_filter_form($analyticsFrom, $analyticsTo, $analyticsType, $analyticsCondition, $analyticsSearch);
if (!$hasDowntime) analytics_notice('Downtime analytics are waiting for data integration. Run migrations/analytics_extensions.sql, then sync incident records from the owning maintenance or utilities subsystem. This report does not use fabricated incidents.', 'warning');
elseif (!$queryOk) analytics_notice('Downtime records could not be queried. Check the database schema and application logs.', 'error');
elseif (!$rows) analytics_notice('No downtime events match these filters. Confirm that source incidents have been synchronized for this period.', 'info');
echo '<section class="analytics-metrics">';
analytics_metric('Downtime hours', number_format($totalHours, 1), 'Elapsed hours for events started in the selected period', '◷');
analytics_metric('Critical ongoing', number_format(count($criticalOngoing)), 'Critical incidents not yet restored', '⚠');
analytics_metric('Ongoing incidents', number_format(count($ongoing)), 'Events still unresolved or without a restoration time', '●');
analytics_metric('Mean restore time', $avgRestore === null ? 'N/A' : number_format($avgRestore, 1) . ' h', $avgRestore === null ? 'No restored incidents with timestamps in period' : count($restoredWithTimestamp) . ' restored events with timestamps included', '↻');
echo '</section>';
if (count($criticalOngoing) > 0) analytics_notice(count($criticalOngoing) . ' critical incidents remain unresolved. Escalate through the agency incident procedure and confirm restoration timestamps in the source system.', 'error');
echo '<section class="analytics-grid">';
analytics_chart_panel('Monthly downtime hours', 'Elapsed hours by the month the incident started; ongoing incidents are counted through the selected period end.', 'downtimeTrendChart');
analytics_chart_panel('Incidents by severity', 'Count of events by source-reported severity.', 'downtimeSeverityChart');
analytics_chart_panel('Downtime by reported cause', 'Top causes ranked by total downtime hours; improve source classification for better analysis.', 'downtimeCauseChart', true);
echo '</section>';
analytics_table_start(['Incident', 'Asset / facility', 'Started', 'Restored', 'Hours in period', 'Severity', 'Status', 'Cause']);
if (!$rows) echo '<tr><td colspan="8" class="analytics-empty">No downtime records to display.</td></tr>';
foreach ($rows as $r) {
    $code = $r['external_id'] ?: ('INC-' . $r['id']);
    $status = empty($r['restored_at']) && !in_array(strtolower((string)$r['status']), ['restored', 'closed', 'resolved'], true) ? 'Ongoing' : 'Restored';
    echo '<tr><td>' . e((string)$code) . '</td><td>' . e((string)$r['asset_name']) . '<br><small>' . e((string)($r['external_asset_tag'] ?: $r['location'])) . '</small></td><td>' . e(analytics_date_label($r['started_at'] ?? null)) . '</td><td>' . e(analytics_date_label($r['restored_at'] ?? null)) . '</td><td>' . e(number_format((float)$r['downtime_hours'], 1)) . '</td><td>' . analytics_pill((string)$r['severity']) . '</td><td>' . analytics_pill($status) . '</td><td>' . e((string)($r['cause'] ?: 'Unclassified')) . '</td></tr>';
}
analytics_table_end();
?>
<script>
    const downtimeDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    boxWidth: 8
                }
            }
        }
    };
    Chart.defaults.color = '#64748b';
    new Chart(document.getElementById('downtimeTrendChart'), {
        type: 'line',
        data: {
            labels: <?= analytics_json($monthlyLabels) ?>,
            datasets: [{
                label: 'Downtime hours',
                data: <?= analytics_json($monthlyValues) ?>,
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220,38,38,.10)',
                fill: true,
                tension: .3
            }]
        },
        options: {
            ...downtimeDefaults,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    new Chart(document.getElementById('downtimeSeverityChart'), {
        type: 'bar',
        data: {
            labels: <?= analytics_json($severityLabels) ?>,
            datasets: [{
                label: 'Incidents',
                data: <?= analytics_json($severityValues) ?>,
                backgroundColor: ['#991b1b', '#ef4444', '#f59e0b', '#94a3b8'],
                borderRadius: 7
            }]
        },
        options: {
            ...downtimeDefaults,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    new Chart(document.getElementById('downtimeCauseChart'), {
        type: 'bar',
        data: {
            labels: <?= analytics_json(array_keys($topCauses)) ?>,
            datasets: [{
                label: 'Downtime hours',
                data: <?= analytics_json(array_map(fn($v) => round($v, 2), array_values($topCauses))) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 7
            }]
        },
        options: {
            ...downtimeDefaults,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
<?php analytics_page_end(); ?>