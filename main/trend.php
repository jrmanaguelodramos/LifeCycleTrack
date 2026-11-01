<?php

declare(strict_types=1);
require_once __DIR__ . '/_analytics.php';

$months = analytics_months($analyticsFrom, $analyticsTo);

$assetCte = "WITH assets AS (
    SELECT
        f.id, f.name, 'facility'::text AS asset_type, f.location,
        f.condition, f.date_acquired, f.category,
        NULL::text AS brand, NULL::text AS model,
        ('FAC-' || LPAD(f.id::text, 6, '0')) AS asset_tag
    FROM public.facilities f

    UNION ALL

    SELECT
        e.id, e.name, 'equipment'::text AS asset_type, e.location,
        e.condition, e.date_acquired, e.category, e.brand, e.model,
        COALESCE(e.asset_tag, 'EQP-' || LPAD(e.id::text, 6, '0')) AS asset_tag
    FROM public.equipment e
)";

$where = ['a.date_acquired BETWEEN :date_from AND :date_to'];
$params = [':date_from' => $analyticsFrom, ':date_to' => $analyticsTo];

if ($analyticsType !== 'all') {
    $where[] = 'a.asset_type = :asset_type';
    $params[':asset_type'] = $analyticsType;
}
if ($analyticsCondition !== 'all') {
    $where[] = 'a.condition = :condition';
    $params[':condition'] = $analyticsCondition;
}
if ($analyticsSearch !== '') {
    $where[] = "(a.name ILIKE :q1 OR a.location ILIKE :q2
        OR COALESCE(a.category, '') ILIKE :q3
        OR COALESCE(a.brand, '') ILIKE :q4
        OR COALESCE(a.model, '') ILIKE :q5)";
    foreach ([':q1', ':q2', ':q3', ':q4', ':q5'] as $key) {
        $params[$key] = '%' . $analyticsSearch . '%';
    }
}
$whereSql = implode(' AND ', $where);

$assetsResult = analytics_query_or_empty(
    $pdo,
    "$assetCte
     SELECT a.id, a.name, a.asset_type, a.location, a.condition,
            a.date_acquired, a.category, a.asset_tag
     FROM assets a
     WHERE $whereSql
     ORDER BY a.date_acquired DESC NULLS LAST, a.id DESC",
    $params
);
$assets = $assetsResult['rows'];

$monthlyResult = analytics_query_or_empty(
    $pdo,
    "$assetCte
     SELECT to_char(date_trunc('month', a.date_acquired), 'YYYY-MM') AS month_key,
            COUNT(*)::int AS total
     FROM assets a
     WHERE $whereSql
     GROUP BY 1
     ORDER BY 1",
    $params
);

$monthlyMap = [];
foreach ($monthlyResult['rows'] as $row) {
    $monthlyMap[$row['month_key']] = (int)$row['total'];
}
$monthlyLabels = array_column($months, 'label');
$monthlyValues = array_map(fn($m) => $monthlyMap[$m['key']] ?? 0, $months);

$assessmentParams = [':date_from' => $analyticsFrom, ':date_to' => $analyticsTo];
if ($analyticsType !== 'all') {
    $assessmentParams[':asset_type'] = $analyticsType;
}
if ($analyticsCondition !== 'all') {
    $assessmentParams[':condition'] = $analyticsCondition;
}
if ($analyticsSearch !== '') {
    foreach ([':q1', ':q2', ':q3'] as $key) {
        $assessmentParams[$key] = '%' . $analyticsSearch . '%';
    }
}

$assessmentResult = analytics_query_or_empty(
    $pdo,
    "$assetCte,
     assessments AS (
        SELECT id, facility_id AS asset_id, 'facility'::text AS asset_type,
               condition, assessed_at
        FROM public.facility_condition_assessments

        UNION ALL

        SELECT id, equipment_id AS asset_id, 'equipment'::text AS asset_type,
               condition, assessed_at
        FROM public.equipment_condition_assessments
     )
     SELECT to_char(date_trunc('month', ca.assessed_at), 'YYYY-MM') AS month_key,
            ca.condition, COUNT(*)::int AS total
     FROM assessments ca
     JOIN assets a ON a.id = ca.asset_id AND a.asset_type = ca.asset_type
     WHERE ca.assessed_at >= CAST(:date_from AS date)
       AND ca.assessed_at < (CAST(:date_to AS date) + INTERVAL '1 day')"
        . ($analyticsType !== 'all' ? ' AND a.asset_type = :asset_type' : '')
        . ($analyticsCondition !== 'all' ? ' AND a.condition = :condition' : '')
        . ($analyticsSearch !== ''
            ? " AND (a.name ILIKE :q1 OR a.location ILIKE :q2
                    OR COALESCE(a.category, '') ILIKE :q3)"
            : '')
        . ' GROUP BY 1, 2 ORDER BY 1',
    $assessmentParams
);

$assessmentMap = [];
foreach ($assessmentResult['rows'] as $row) {
    $assessmentMap[$row['month_key']][(string)$row['condition']] = (int)$row['total'];
}

$conditionTrendDatasets = [];
foreach ([['Good', '#10b981'], ['Fair', '#f59e0b'], ['Poor', '#ef4444']] as [$conditionName, $color]) {
    $conditionTrendDatasets[] = [
        'label' => $conditionName,
        'data' => array_map(
            fn($m) => $assessmentMap[$m['key']][$conditionName] ?? 0,
            $months
        ),
        'borderColor' => $color,
        'backgroundColor' => $color,
        'tension' => .25,
    ];
}

$deteriorationParams = [':date_from' => $analyticsFrom, ':date_to' => $analyticsTo];
$deteriorationFilters = [];

if ($analyticsType !== 'all') {
    $deteriorationFilters[] = 'a.asset_type = :asset_type';
    $deteriorationParams[':asset_type'] = $analyticsType;
}
if ($analyticsCondition !== 'all') {
    $deteriorationFilters[] = 'a.condition = :condition';
    $deteriorationParams[':condition'] = $analyticsCondition;
}
if ($analyticsSearch !== '') {
    $deteriorationFilters[] =
        "(a.name ILIKE :q1 OR a.location ILIKE :q2 OR COALESCE(a.category, '') ILIKE :q3)";
    foreach ([':q1', ':q2', ':q3'] as $key) {
        $deteriorationParams[$key] = '%' . $analyticsSearch . '%';
    }
}
$deteriorationFilterSql = $deteriorationFilters
    ? ' AND ' . implode(' AND ', $deteriorationFilters)
    : '';

$deteriorationResult = analytics_query_or_empty(
    $pdo,
    "$assetCte,
     assessments AS (
        SELECT id, facility_id AS asset_id, 'facility'::text AS asset_type,
               condition, assessed_at
        FROM public.facility_condition_assessments

        UNION ALL

        SELECT id, equipment_id AS asset_id, 'equipment'::text AS asset_type,
               condition, assessed_at
        FROM public.equipment_condition_assessments
     ),
     history AS (
        SELECT ca.asset_id, ca.asset_type, ca.condition, ca.assessed_at,
               LAG(ca.condition) OVER (
                   PARTITION BY ca.asset_type, ca.asset_id
                   ORDER BY ca.assessed_at, ca.id
               ) AS previous_condition
        FROM assessments ca
        JOIN assets a ON a.id = ca.asset_id AND a.asset_type = ca.asset_type
        WHERE 1 = 1 $deteriorationFilterSql
     )
     SELECT COUNT(*)::int AS events,
            COUNT(DISTINCT (asset_type, asset_id))::int AS assets
     FROM history
     WHERE assessed_at >= CAST(:date_from AS date)
       AND assessed_at < (CAST(:date_to AS date) + INTERVAL '1 day')
       AND previous_condition IS NOT NULL
       AND (CASE condition WHEN 'Good' THEN 1 WHEN 'Fair' THEN 2 WHEN 'Poor' THEN 3 ELSE 0 END)
           > (CASE previous_condition WHEN 'Good' THEN 1 WHEN 'Fair' THEN 2 WHEN 'Poor' THEN 3 ELSE 0 END)",
    $deteriorationParams
);

$deteriorationCount = (int)($deteriorationResult['rows'][0]['events'] ?? 0);
$deterioratedAssets = (int)($deteriorationResult['rows'][0]['assets'] ?? 0);
$equipmentCount = count(array_filter($assets, fn($a) => $a['asset_type'] === 'equipment'));
$facilityCount = count(array_filter($assets, fn($a) => $a['asset_type'] === 'facility'));
$poorCount = count(array_filter($assets, fn($a) => strtolower((string)$a['condition']) === 'poor'));
$conditionValues = array_map(fn($v) => count(array_filter($assets, fn($a) => $a['condition'] === $v)), ['Good', 'Fair', 'Poor']);
$typeValues = [$equipmentCount, $facilityCount];

analytics_page_start('Trend Analysis', 'Track asset acquisitions, portfolio composition, and condition across the selected acquisition period.');
analytics_filter_form($analyticsFrom, $analyticsTo, $analyticsType, $analyticsCondition, $analyticsSearch);
if (!$assetsResult['ok'] || !$monthlyResult['ok'] || !$assessmentResult['ok'] || !$deteriorationResult['ok']) analytics_notice('Analytics could not be loaded from the database. Check the server logs and database connection before relying on this report.', 'error');
if (count($assets) === 0) analytics_notice('No assets match these filters. Try a wider date range or reset the filters.', 'info');
elseif ($deteriorationCount > 0) analytics_notice($deteriorationCount . ' deterioration events were recorded across ' . $deterioratedAssets . ' assets in this period. Review these asset histories and prioritize inspections based on criticality and service impact.', 'warning');
elseif ($poorCount > 0) analytics_notice($poorCount . ' assets acquired in this period are currently marked Poor. Open the asset records, confirm condition assessments, and route critical items for inspection or maintenance review.', 'warning');
echo '<section class="analytics-metrics">';
analytics_metric('Assets acquired in period', number_format(count($assets)), 'Records matching all selected filters', '▦');
analytics_metric('Poor condition', number_format($poorCount), $assets ? number_format(($poorCount / count($assets)) * 100, 1) . '% of filtered assets' : 'No matching assets', '⚠');
analytics_metric('Deterioration events', number_format($deteriorationCount), 'Assessments worse than the prior assessment', '↘');
analytics_metric('Assets deteriorated', number_format($deterioratedAssets), 'Unique assets with deterioration in this period', '⌁');
echo '</section><section class="analytics-grid">';
analytics_chart_panel('Monthly acquisitions', 'Number of assets acquired in each month; based on acquisition date.', 'acquisitionChart');
analytics_chart_panel('Portfolio mix', 'Equipment versus facility records in the filtered results.', 'typeChart');
analytics_chart_panel('Condition distribution', 'Current recorded condition for the assets acquired in the selected period.', 'conditionChart');
analytics_chart_panel('Condition assessment trend', 'Count of assessments by recorded grade. Empty periods mean no assessments were recorded, not zero risk.', 'conditionTrendChart', true);
echo '</section>';
analytics_table_start(['Asset tag', 'Asset', 'Type', 'Category', 'Location', 'Condition', 'Acquired']);
if (!$assets) echo '<tr><td colspan="7" class="analytics-empty">No matching records.</td></tr>';
foreach ($assets as $asset) {
    echo '<tr><td>' . e((string)$asset['asset_tag']) . '</td><td>' . e((string)$asset['name']) . '</td><td>' . e(ucfirst((string)$asset['asset_type'])) . '</td><td>' . e((string)($asset['category'] ?? '—')) . '</td><td>' . e((string)$asset['location']) . '</td><td>' . analytics_pill((string)$asset['condition']) . '</td><td>' . e(analytics_date_label($asset['date_acquired'] ?? null)) . '</td></tr>';
}
analytics_table_end();
?>
<script>
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    boxWidth: 8
                }
            }
        }
    };
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    new Chart(document.getElementById('acquisitionChart'), {
        type: 'line',
        data: {
            labels: <?= analytics_json($monthlyLabels) ?>,
            datasets: [{
                label: 'Acquisitions',
                data: <?= analytics_json($monthlyValues) ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.12)',
                fill: true,
                tension: .3,
                pointRadius: 3
            }]
        },
        options: {
            ...chartDefaults,
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
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Equipment', 'Facility'],
            datasets: [{
                data: <?= analytics_json($typeValues) ?>,
                backgroundColor: ['#2563eb', '#0f9f79'],
                borderWidth: 0
            }]
        },
        options: {
            ...chartDefaults,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    new Chart(document.getElementById('conditionChart'), {
        type: 'bar',
        data: {
            labels: ['Good', 'Fair', 'Poor'],
            datasets: [{
                label: 'Assets',
                data: <?= analytics_json($conditionValues) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderRadius: 7
            }]
        },
        options: {
            ...chartDefaults,
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
    new Chart(document.getElementById('conditionTrendChart'), {
        type: 'line',
        data: {
            labels: <?= analytics_json($monthlyLabels) ?>,
            datasets: <?= analytics_json($conditionTrendDatasets) ?>
        },
        options: {
            ...chartDefaults,
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
</script>
<?php analytics_page_end(); ?>