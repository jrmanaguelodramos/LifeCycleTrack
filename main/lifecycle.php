<?php
declare(strict_types=1);
require_once __DIR__ . '/_analytics.php';

$hasStage = analytics_column_exists($pdo, 'assets', 'lifecycle_stage');
$hasUsefulLife = analytics_column_exists($pdo, 'assets', 'useful_life_years');
$hasAcqCost = analytics_column_exists($pdo, 'assets', 'acquisition_cost');
$hasReplaceCost = analytics_column_exists($pdo, 'assets', 'replacement_cost_estimate');
$hasCriticality = analytics_column_exists($pdo, 'assets', 'criticality');
$hasTag = analytics_column_exists($pdo, 'assets', 'asset_tag');
$stageExpr = $hasStage ? 'a.lifecycle_stage' : "CASE WHEN lower(a.condition)='poor' THEN 'Replacement Review' ELSE 'In Service' END";
$tagExpr = $hasTag ? 'a.asset_tag' : "('AST-' || LPAD(a.id::text, 6, '0'))";
$lifeExpr = $hasUsefulLife ? 'a.useful_life_years' : 'NULL::integer';
$costExpr = $hasAcqCost ? 'a.acquisition_cost' : 'NULL::numeric';
$replaceExpr = $hasReplaceCost ? 'a.replacement_cost_estimate' : 'NULL::numeric';
$criticalExpr = $hasCriticality ? 'a.criticality' : "'Medium'::text";
$where = ['1=1']; $params = [];
if ($analyticsType !== 'all') { $where[] = 'a.asset_type = :asset_type'; $params[':asset_type'] = $analyticsType; }
if ($analyticsCondition !== 'all') { $where[] = 'a.condition = :condition'; $params[':condition'] = $analyticsCondition; }
if ($analyticsSearch !== '') { $where[] = '(a.name ILIKE :q1 OR a.location ILIKE :q2 OR COALESCE(a.category, \'\') ILIKE :q3 OR ' . $tagExpr . ' ILIKE :q4)'; foreach ([':q1',':q2',':q3',':q4'] as $k) $params[$k] = '%' . $analyticsSearch . '%'; }
$sql = "SELECT a.id, $tagExpr AS asset_tag, a.name, a.asset_type, a.category, a.location, a.condition, a.date_acquired, $stageExpr AS lifecycle_stage, $lifeExpr AS useful_life_years, $costExpr AS acquisition_cost, $replaceExpr AS replacement_cost_estimate, $criticalExpr AS criticality FROM assets a WHERE " . implode(' AND ', $where) . ' ORDER BY CASE lower(a.condition) WHEN \'poor\' THEN 1 WHEN \'fair\' THEN 2 ELSE 3 END, a.date_acquired ASC NULLS LAST, a.id DESC';
$result = analytics_query_or_empty($pdo, $sql, $params);
$assets = $result['rows'];
$today = new DateTimeImmutable('today');
$ageOf = static function(array $asset) use ($today): ?int {
    if (empty($asset['date_acquired'])) return null;
    try { return max(0, (int)$today->diff(new DateTimeImmutable((string)$asset['date_acquired']))->y); }
    catch (Throwable) { return null; }
};
$replacementCandidates = [];
foreach ($assets as $asset) {
    $age = $ageOf($asset);
    $stage = strtolower((string)($asset['lifecycle_stage'] ?? ''));
    $life = isset($asset['useful_life_years']) ? (int)$asset['useful_life_years'] : null;
    if (!in_array($stage, ['retired','disposed'], true) && (in_array($stage, ['replacement review','retirement review','end of life'], true) || strtolower((string)$asset['condition']) === 'poor' || ($life !== null && $life > 0 && $age !== null && $age >= $life))) $replacementCandidates[] = $asset;
}
$stageLabels = ['Planned','In Setup','In Service','Maintenance','Replacement Review','Retirement Review','Retired','Disposed'];
$stageValues = array_map(fn($s) => count(array_filter($assets, fn($a) => strcasecmp((string)$a['lifecycle_stage'], $s) === 0)), $stageLabels);
$conditionLabels = ['Good','Fair','Poor'];
$conditionValues = array_map(fn($s) => count(array_filter($assets, fn($a) => strcasecmp((string)$a['condition'], $s) === 0)), $conditionLabels);
$ageLabels = ['Unknown','0–2 years','3–5 years','6–10 years','Over 10 years']; $ageValues = array_fill(0,5,0);
foreach ($assets as $asset) { $age = $ageOf($asset); $index = $age === null ? 0 : ($age <= 2 ? 1 : ($age <= 5 ? 2 : ($age <= 10 ? 3 : 4))); $ageValues[$index]++; }
$knownAcqCosts = array_filter($assets, fn($a) => $a['acquisition_cost'] !== null);
$totalAcqCost = array_sum(array_map(fn($a) => (float)($a['acquisition_cost'] ?? 0), $knownAcqCosts));
$knownReplacementCosts = array_filter($replacementCandidates, fn($a) => $a['replacement_cost_estimate'] !== null);
$replacementForecast = array_sum(array_map(fn($a) => (float)($a['replacement_cost_estimate'] ?? 0), $knownReplacementCosts));
$poorCount = count(array_filter($assets, fn($a) => strtolower((string)$a['condition']) === 'poor'));

analytics_page_start('Life Cycle Analysis', 'Assess asset age, current condition, service stage, and candidates for technical replacement review.');
// Life cycle analysis intentionally uses the full current asset register; the date filters are not applied to avoid hiding older assets.
echo '<form class="analytics-filters no-print" method="get"><label>Asset type<select name="type"><option value="all"' . ($analyticsType === 'all' ? ' selected' : '') . '>All types</option><option value="equipment"' . ($analyticsType === 'equipment' ? ' selected' : '') . '>Equipment</option><option value="facility"' . ($analyticsType === 'facility' ? ' selected' : '') . '>Facility</option></select></label><label>Condition<select name="condition"><option value="all"' . ($analyticsCondition === 'all' ? ' selected' : '') . '>All conditions</option><option' . ($analyticsCondition === 'Good' ? ' selected' : '') . '>Good</option><option' . ($analyticsCondition === 'Fair' ? ' selected' : '') . '>Fair</option><option' . ($analyticsCondition === 'Poor' ? ' selected' : '') . '>Poor</option></select></label><label class="search-field">Search<input type="search" name="q" maxlength="100" value="' . e($analyticsSearch) . '" placeholder="Asset tag, name, location..."></label><button class="analytics-button" type="submit">Apply filters</button><a class="analytics-reset" href="lifecycle.php">Reset</a></form>';
if (!$result['ok']) analytics_notice('Life cycle data could not be loaded. Check the database connection and application logs.', 'error');
if (!$hasStage || !$hasUsefulLife || !$hasAcqCost || !$hasReplaceCost || !$hasCriticality || !$hasTag) analytics_notice('Some life cycle fields are not installed yet. Run migrations/analytics_extensions.sql to enable asset tags, useful life, acquisition and replacement cost, criticality, and formal life cycle stage. Until then, stage and replacement indicators use limited fallback rules.', 'warning');
if (!$assets) analytics_notice('No assets match these filters.', 'info');
elseif (count($replacementCandidates) > 0) analytics_notice(count($replacementCandidates) . ' assets meet a rule-based replacement-review trigger (poor condition, useful life reached, or a review stage). This is a shortlist for technical assessment, not an automatic replacement or disposal decision.', 'warning');
echo '<section class="analytics-metrics">';
analytics_metric('Assets reviewed', number_format(count($assets)), 'Current register matching selected filters', '▦');
analytics_metric('Poor condition', number_format($poorCount), 'Requires condition validation and action planning', '⚠');
analytics_metric('Replacement review candidates', number_format(count($replacementCandidates)), 'Rule-based shortlist; not an automatic disposal decision', '↻');
analytics_metric('Estimated replacement cost', $hasReplaceCost ? analytics_money($replacementForecast) : 'N/A', $hasReplaceCost ? count($knownReplacementCosts) . ' of ' . count($replacementCandidates) . ' candidates have estimates recorded' : 'Add replacement cost estimates to forecast funding', '₱');
echo '</section><section class="analytics-grid">';
analytics_chart_panel('Life cycle stage', 'Distribution by recorded stage; fallback stages are inferred when migration is not installed.', 'stageChart');
analytics_chart_panel('Current condition', 'Current condition is not a substitute for historical condition movement.', 'conditionChart');
analytics_chart_panel('Asset age profile', 'Age calculated from acquisition date; unknown dates are shown separately.', 'ageChart', true);
echo '</section>';
analytics_table_start(['Asset tag', 'Asset', 'Location', 'Age', 'Useful life', 'Criticality', 'Condition', 'Stage', 'Replacement estimate', 'Review flag']);
if (!$assets) echo '<tr><td colspan="10" class="analytics-empty">No assets to display.</td></tr>';
foreach ($assets as $asset) {
    $age = $ageOf($asset); $life = $asset['useful_life_years'] !== null ? (int)$asset['useful_life_years'] : null;
    $isCandidate = in_array($asset, $replacementCandidates, true);
    $review = $isCandidate ? 'Review' : 'Monitor';
    echo '<tr><td>' . e((string)$asset['asset_tag']) . '</td><td>' . e((string)$asset['name']) . '<br><small>' . e(ucfirst((string)$asset['asset_type']) . ($asset['category'] ? ' · ' . $asset['category'] : '')) . '</small></td><td>' . e((string)$asset['location']) . '</td><td>' . e($age === null ? 'Unknown' : $age . ' years') . '</td><td>' . e($life === null ? 'Not set' : $life . ' years') . '</td><td>' . analytics_pill((string)$asset['criticality']) . '</td><td>' . analytics_pill((string)$asset['condition']) . '</td><td>' . analytics_pill((string)$asset['lifecycle_stage']) . '</td><td>' . e($asset['replacement_cost_estimate'] === null ? 'Not estimated' : analytics_money((float)$asset['replacement_cost_estimate'])) . '</td><td>' . analytics_pill($review) . '</td></tr>';
}
analytics_table_end();
?>
<script>
const lifecycleDefaults={responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8}}}}; Chart.defaults.color='#64748b';
new Chart(document.getElementById('stageChart'),{type:'doughnut',data:{labels:<?= analytics_json($stageLabels) ?>,datasets:[{data:<?= analytics_json($stageValues) ?>,backgroundColor:['#93c5fd','#60a5fa','#2563eb','#f59e0b','#ef4444','#c2410c','#64748b','#334155'],borderWidth:0}]},options:lifecycleDefaults});
new Chart(document.getElementById('conditionChart'),{type:'bar',data:{labels:<?= analytics_json($conditionLabels) ?>,datasets:[{label:'Assets',data:<?= analytics_json($conditionValues) ?>,backgroundColor:['#10b981','#f59e0b','#ef4444'],borderRadius:7}]},options:{...lifecycleDefaults,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
new Chart(document.getElementById('ageChart'),{type:'bar',data:{labels:<?= analytics_json($ageLabels) ?>,datasets:[{label:'Assets',data:<?= analytics_json($ageValues) ?>,backgroundColor:'#6366f1',borderRadius:7}]},options:{...lifecycleDefaults,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
</script>
<?php analytics_page_end(); ?>
