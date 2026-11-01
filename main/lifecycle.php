<?php

declare(strict_types=1);
require_once __DIR__ . '/_analytics.php';

// These fields are present in both facilities and equipment in your schema.
// Facility tags are generated for display; equipment uses its stored tag when available.
$hasStage = true;
$hasUsefulLife = true;
$hasAcqCost = true;
$hasReplaceCost = true;
$hasCriticality = true;
$hasTag = true;

$where = ['1=1'];
$params = [];

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
        OR COALESCE(a.category, '') ILIKE :q3 OR a.asset_tag ILIKE :q4)";
    foreach ([':q1', ':q2', ':q3', ':q4'] as $key) {
        $params[$key] = '%' . $analyticsSearch . '%';
    }
}

$assetCte = "WITH assets AS (
    SELECT
        f.id,
        f.name,
        'facility'::text AS asset_type,
        f.category,
        f.location,
        f.condition,
        f.date_acquired,
        COALESCE(
            NULLIF(BTRIM(f.lifecycle_stage), ''),
            'In Service'::text
        ) AS lifecycle_stage,
        f.useful_life_years,
        f.acquisition_cost,
        f.replacement_cost_estimate,
        COALESCE(NULLIF(BTRIM(f.criticality), ''), 'Not recorded') AS criticality,
        'FAC-' || LPAD(f.id::text, 6, '0') AS asset_tag
    FROM public.facilities f

    UNION ALL

    SELECT
        e.id,
        e.name,
        'equipment'::text AS asset_type,
        e.category,
        e.location,
        e.condition,
        e.date_acquired,
        COALESCE(
            NULLIF(BTRIM(e.lifecycle_stage), ''),
            'In Service'::text
        ) AS lifecycle_stage,
        e.useful_life_years,
        e.acquisition_cost,
        e.replacement_cost_estimate,
        COALESCE(NULLIF(BTRIM(e.criticality), ''), 'Not recorded') AS criticality,
        COALESCE(NULLIF(e.asset_tag, ''), 'EQP-' || LPAD(e.id::text, 6, '0')) AS asset_tag
    FROM public.equipment e
)";

$sql = "$assetCte
    SELECT
        a.id, a.asset_tag, a.name, a.asset_type, a.category, a.location,
        a.condition, a.date_acquired, a.lifecycle_stage, a.useful_life_years,
        a.acquisition_cost, a.replacement_cost_estimate, a.criticality
    FROM assets a
    WHERE " . implode(' AND ', $where) . "
    ORDER BY
        CASE lower(a.condition)
            WHEN 'poor' THEN 1
            WHEN 'fair' THEN 2
            ELSE 3
        END,
        a.date_acquired ASC NULLS LAST,
        a.id DESC";

$result = analytics_query_or_empty($pdo, $sql, $params);
$assets = $result['rows'];
$today = new DateTimeImmutable('today');
$ageOf = static function (array $asset) use ($today): ?int {
    if (empty($asset['date_acquired'])) return null;
    try {
        return max(0, (int)$today->diff(new DateTimeImmutable((string)$asset['date_acquired']))->y);
    } catch (Throwable) {
        return null;
    }
};
$replacementCandidates = [];
$maintenanceCandidates = [];
foreach ($assets as &$asset) {
    $age = $ageOf($asset);
    $stage = strtolower(trim((string)($asset['lifecycle_stage'] ?? '')));
    $condition = strtolower(trim((string)($asset['condition'] ?? '')));
    $life = isset($asset['useful_life_years']) ? (float)$asset['useful_life_years'] : null;
    $criticality = strtolower(trim((string)($asset['criticality'] ?? 'not recorded')));
    $isTerminal = in_array($stage, ['retired', 'disposed'], true);
    $stageReview = in_array($stage, ['replacement review', 'retirement review', 'end of life'], true);
    $lifeReached = $life !== null && $life > 0 && $age !== null && $age >= $life;

    if ($isTerminal) {
        $action = 'Retired / disposed'; $actionRank = 4; $reasons = ['Asset is marked ' . $stage . '.'];
    } elseif ($stageReview || $lifeReached) {
        $action = 'Replacement review'; $actionRank = 0; $reasons = [];
        if ($stageReview) $reasons[] = 'Lifecycle stage is ' . (string)$asset['lifecycle_stage'] . '.';
        if ($lifeReached) $reasons[] = 'Useful life reached (' . $age . ' years old; ' . number_format($life, 0) . ' year useful life).';
        if ($condition === 'poor') $reasons[] = 'Current condition is Poor.';
    } elseif ($condition === 'poor') {
        $action = 'Assess repair vs. replacement'; $actionRank = 1;
        $reasons = ['Current condition is Poor; inspect to compare repair and replacement options.'];
    } elseif ($condition === 'fair' || $stage === 'maintenance') {
        $action = 'Maintain / inspect'; $actionRank = 2; $reasons = [];
        if ($condition === 'fair') $reasons[] = 'Current condition is Fair.';
        if ($stage === 'maintenance') $reasons[] = 'Lifecycle stage is Maintenance.';
    } else {
        $action = 'Monitor'; $actionRank = 3;
        $reasons = ['No replacement or maintenance trigger found in recorded fields.'];
    }

    $asset['_action'] = $action;
    $asset['_action_rank'] = $actionRank;
    $asset['_reason'] = implode(' ', $reasons);
    $asset['_criticality_rank'] = match ($criticality) {
        'critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3, default => 4,
    };
    $asset['_condition_rank'] = match ($condition) {
        'poor' => 0, 'fair' => 1, 'good' => 2, default => 3,
    };
    $asset['_life_use'] = ($life !== null && $life > 0 && $age !== null) ? $age / $life : 0.0;
    if ($actionRank <= 1) $replacementCandidates[] = $asset;
    if ($actionRank === 2) $maintenanceCandidates[] = $asset;
}
unset($asset);
usort($assets, static function (array $a, array $b): int {
    $priority = [$a['_action_rank'], $a['_criticality_rank'], $a['_condition_rank']]
        <=> [$b['_action_rank'], $b['_criticality_rank'], $b['_condition_rank']];
    return $priority !== 0 ? $priority : ($b['_life_use'] <=> $a['_life_use']);
});

$stageLabels = ['Planned', 'In Setup', 'In Service', 'Maintenance', 'Replacement Review', 'Retirement Review', 'Retired', 'Disposed'];
$stageValues = array_map(fn($s) => count(array_filter($assets, fn($a) => strcasecmp((string)$a['lifecycle_stage'], $s) === 0)), $stageLabels);
$conditionLabels = ['Good', 'Fair', 'Poor'];
$conditionValues = array_map(fn($s) => count(array_filter($assets, fn($a) => strcasecmp((string)$a['condition'], $s) === 0)), $conditionLabels);
$ageLabels = ['Unknown', '0–2 years', '3–5 years', '6–10 years', 'Over 10 years'];
$ageValues = array_fill(0, 5, 0);
foreach ($assets as $asset) {
    $age = $ageOf($asset);
    $index = $age === null ? 0 : ($age <= 2 ? 1 : ($age <= 5 ? 2 : ($age <= 10 ? 3 : 4)));
    $ageValues[$index]++;
}
$knownAcqCosts = array_filter($assets, fn($a) => $a['acquisition_cost'] !== null);
$totalAcqCost = array_sum(array_map(fn($a) => (float)($a['acquisition_cost'] ?? 0), $knownAcqCosts));
$knownReplacementCosts = array_filter($replacementCandidates, fn($a) => $a['replacement_cost_estimate'] !== null);
$replacementForecast = array_sum(array_map(fn($a) => (float)($a['replacement_cost_estimate'] ?? 0), $knownReplacementCosts));
$poorCount = count(array_filter($assets, fn($a) => strtolower((string)$a['condition']) === 'poor'));

analytics_page_start('Life Cycle Analysis', 'Assess asset age, current condition, service stage, and candidates for technical replacement review.');
// Life cycle analysis intentionally uses the full current asset register; the date filters are not applied to avoid hiding older assets.
echo '<form class="analytics-filters no-print" method="get"><label>Asset type<select name="type"><option value="all"' . ($analyticsType === 'all' ? ' selected' : '') . '>All types</option><option value="equipment"' . ($analyticsType === 'equipment' ? ' selected' : '') . '>Equipment</option><option value="facility"' . ($analyticsType === 'facility' ? ' selected' : '') . '>Facility</option></select></label><label>Condition<select name="condition"><option value="all"' . ($analyticsCondition === 'all' ? ' selected' : '') . '>All conditions</option><option' . ($analyticsCondition === 'Good' ? ' selected' : '') . '>Good</option><option' . ($analyticsCondition === 'Fair' ? ' selected' : '') . '>Fair</option><option' . ($analyticsCondition === 'Poor' ? ' selected' : '') . '>Poor</option></select></label><label class="search-field">Search<input type="search" name="q" maxlength="100" value="' . e($analyticsSearch) . '" placeholder="Asset tag, name, location..."></label><button class="analytics-button" type="submit">Apply filters</button><a class="analytics-reset" href="lifecycle.php">Reset</a></form>';
if (!$result['ok']) analytics_notice('Life cycle data could not be loaded. Check the database connection and application logs.', 'error');
if (!$assets) analytics_notice('No assets match these filters.', 'info');
elseif (count($replacementCandidates) > 0) analytics_notice(count($replacementCandidates) . ' assets need replacement or repair-vs-replacement review. These are prioritization flags for technical assessment, not automatic replacement decisions.', 'warning');
echo '<section class="analytics-metrics">';
analytics_metric('Assets reviewed', number_format(count($assets)), 'Current register matching selected filters', '▦');
analytics_metric('Poor condition', number_format($poorCount), 'Requires condition validation and action planning', '⚠');
analytics_metric('Replace / technical review', number_format(count($replacementCandidates)), 'Includes replacement review and repair-vs-replacement assessment', '↻');
analytics_metric('Maintain / inspect', number_format(count($maintenanceCandidates)), 'Fair-condition assets or assets in the maintenance stage', '🛠');
analytics_metric('Estimated replacement cost', $hasReplaceCost ? analytics_money($replacementForecast) : 'N/A', $hasReplaceCost ? count($knownReplacementCosts) . ' of ' . count($replacementCandidates) . ' candidates have estimates recorded' : 'Add replacement cost estimates to forecast funding', '₱');
echo '</section><section class="analytics-grid">';
analytics_chart_panel('Life cycle stage', 'Distribution by recorded stage; blank stages are inferred from current condition.', 'stageChart');
analytics_chart_panel('Current condition', 'Current condition is not a substitute for historical condition movement.', 'conditionChart');
analytics_chart_panel('Asset age profile', 'Age calculated from acquisition date; unknown dates are shown separately.', 'ageChart', true);
echo '</section>';
analytics_table_start(['Asset tag', 'Asset', 'Age / useful life', 'Criticality', 'Condition', 'Stage', 'Replacement estimate', 'Recommended next step', 'Reason', 'Data gaps']);
if (!$assets) echo '<tr><td colspan="10" class="analytics-empty">No assets to display.</td></tr>';
$lastAction = null;
foreach ($assets as $asset) {
    $age = $ageOf($asset);
    $life = $asset['useful_life_years'] !== null ? (int)$asset['useful_life_years'] : null;
    $gaps = [];
    if ($age === null) $gaps[] = 'Acquisition date';
    if ($life === null || $life <= 0) $gaps[] = 'Useful life';
    if (strtolower((string)$asset['criticality']) === 'not recorded') $gaps[] = 'Criticality';
    if ($asset['replacement_cost_estimate'] === null && $asset['_action_rank'] <= 1) $gaps[] = 'Replacement estimate';

    if ($lastAction !== $asset['_action']) {
        $groupCount = count(array_filter($assets, fn($row) => $row['_action'] === $asset['_action']));
        echo '<tr><td colspan="10" style="background:#f1f5f9;font-weight:700;padding:.75rem 1rem;">'
            . e((string)$asset['_action']) . ' queue · ' . number_format($groupCount) . ' asset(s)</td></tr>';
        $lastAction = $asset['_action'];
    }

    echo '<tr><td>' . e((string)$asset['asset_tag']) . '</td>'
        . '<td>' . e((string)$asset['name']) . '<br><small>' . e(ucfirst((string)$asset['asset_type']) . ($asset['category'] ? ' · ' . $asset['category'] : '')) . '</small><br><small>' . e((string)$asset['location']) . '</small></td>'
        . '<td>' . e($age === null ? 'Unknown' : $age . ' years') . ' / ' . e($life === null || $life <= 0 ? 'Not recorded' : $life . ' years') . '</td>'
        . '<td>' . analytics_pill((string)$asset['criticality']) . '</td>'
        . '<td>' . analytics_pill((string)$asset['condition']) . '</td>'
        . '<td>' . analytics_pill((string)$asset['lifecycle_stage']) . '</td>'
        . '<td>' . e($asset['replacement_cost_estimate'] === null ? 'Not estimated' : analytics_money((float)$asset['replacement_cost_estimate'])) . '</td>'
        . '<td>' . analytics_pill((string)$asset['_action']) . '</td>'
        . '<td>' . e((string)$asset['_reason']) . '</td>'
        . '<td>' . e($gaps ? implode(', ', $gaps) : 'None identified') . '</td></tr>';
}
analytics_table_end();
?>
<script>
    const lifecycleDefaults = {
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
    new Chart(document.getElementById('stageChart'), {
        type: 'doughnut',
        data: {
            labels: <?= analytics_json($stageLabels) ?>,
            datasets: [{
                data: <?= analytics_json($stageValues) ?>,
                backgroundColor: ['#93c5fd', '#60a5fa', '#2563eb', '#f59e0b', '#ef4444', '#c2410c', '#64748b', '#334155'],
                borderWidth: 0
            }]
        },
        options: lifecycleDefaults
    });
    new Chart(document.getElementById('conditionChart'), {
        type: 'bar',
        data: {
            labels: <?= analytics_json($conditionLabels) ?>,
            datasets: [{
                label: 'Assets',
                data: <?= analytics_json($conditionValues) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderRadius: 7
            }]
        },
        options: {
            ...lifecycleDefaults,
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
    new Chart(document.getElementById('ageChart'), {
        type: 'bar',
        data: {
            labels: <?= analytics_json($ageLabels) ?>,
            datasets: [{
                label: 'Assets',
                data: <?= analytics_json($ageValues) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 7
            }]
        },
        options: {
            ...lifecycleDefaults,
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
</script>
<?php analytics_page_end(); ?>


