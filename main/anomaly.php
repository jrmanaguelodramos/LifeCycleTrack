<?php

declare(strict_types=1);
require_once __DIR__ . '/_analytics.php';
require_once __DIR__ . '/../services/ai_integ.php';

const ANOMALY_LIMIT = 20.0;
const USE_SAMPLE_DATA = true;

$sampleBills = [
    'electricity' => [
        ['id' => 1, 'bill_date' => '2026-06-01', 'qty' => 320, 'cost' => 2300.00],
        ['id' => 2, 'bill_date' => '2026-07-01', 'qty' => 310, 'cost' => 2170.00],
        ['id' => 3, 'bill_date' => '2026-08-01', 'qty' => 335, 'cost' => 2500.00],
        ['id' => 4, 'bill_date' => '2026-09-01', 'qty' => 300, 'cost' => 2032.00],
        ['id' => 5, 'bill_date' => '2026-10-01', 'qty' => 330, 'cost' => 2241.60],
        ['id' => 6, 'bill_date' => '2026-11-01', 'qty' => 405, 'cost' => 4000.00],
    ],
    'water' => [
        ['id' => 1, 'bill_date' => '2026-06-01', 'qty' => 12.1, 'cost' => 430.00],
        ['id' => 2, 'bill_date' => '2026-07-01', 'qty' => 12.8, 'cost' => 455.00],
        ['id' => 3, 'bill_date' => '2026-08-01', 'qty' => 13.2, 'cost' => 470.00],
        ['id' => 4, 'bill_date' => '2026-09-01', 'qty' => 12.5, 'cost' => 450.00],
        ['id' => 5, 'bill_date' => '2026-11-01', 'qty' => 13.0, 'cost' => 470.00],
        ['id' => 6, 'bill_date' => '2026-10-01', 'qty' => 13.0, 'cost' => 480.00],
    ],
];

$utilityConfig = [
    'electricity' => ['label' => 'Electricity', 'prefix' => 'ELC', 'table' => 'electricity_bills', 'date' => 'billing_month', 'qty' => 'consumption', 'cost' => 'total_amount', 'unit' => 'kWh', 'decimals' => 0, 'color' => '#d98c10'],
    'water'       => ['label' => 'Water', 'prefix' => 'WTR', 'table' => 'water_bills', 'date' => 'billing_month', 'qty' => 'consumption', 'cost' => 'total_amount', 'unit' => 'm³', 'decimals' => 1, 'color' => '#0e8fa6'],
];

$validDate = fn(string $v): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1
    && checkdate((int)substr($v, 5, 2), (int)substr($v, 8, 2), (int)substr($v, 0, 4));
$monthLabel = fn(string $ym): string => date('M Y', (int)strtotime($ym . '-01'));
$pctOf = fn(float $now, float $before): ?float => $before > 0 ? (($now - $before) / $before) * 100 : null;
$fmtPct = fn(?float $v): string => $v === null ? 'N/A' : sprintf('%+d%%', (int)round($v));
$peso = fn(float $v): string => '₱' . number_format(abs($v), 2);
$signedPeso = fn(float $v): string => ($v < 0 ? '-' : '+') . '₱' . number_format(abs($v), 2);
$prevMonth = fn(string $ym): string => date('Y-m', (int)strtotime($ym . '-01 -1 month'));

$dateFrom = (string)($_GET['from'] ?? ($analyticsFrom ?? ''));
if (!$validDate($dateFrom)) {
    $dateFrom = date('Y-m-01', (int)strtotime('-5 months'));
}
$dateTo = (string)($_GET['to'] ?? ($analyticsTo ?? ''));
if (!$validDate($dateTo)) {
    $dateTo = date('Y-m-d');
}
if ($dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}
$utilityFilter = (string)($_GET['utility'] ?? 'all');
if (!in_array($utilityFilter, ['all', 'electricity', 'water'], true)) {
    $utilityFilter = 'all';
}
$search = trim((string)($_GET['q'] ?? ''));
$visibleKeys = $utilityFilter === 'all' ? array_keys($utilityConfig) : [$utilityFilter];
$fromYm = substr($dateFrom, 0, 7);
$toYm = substr($dateTo, 0, 7);
$fetchFrom = date('Y-m-01', (int)strtotime($dateFrom . ' -1 month'));

$notices = [];
$queryOk = true;
$bills = [];
$monthly = [];
foreach ($visibleKeys as $key) {
    $cfg = $utilityConfig[$key];
    if (USE_SAMPLE_DATA) {
        $sourceRows = array_values(array_filter(
            $sampleBills[$key] ?? [],
            fn($r) => $r['bill_date'] >= $fetchFrom && $r['bill_date'] <= $dateTo
        ));
    } else {
        if (!analytics_table_exists($pdo, $cfg['table'])) {
            $notices[] = [$cfg['label'] . ' bills are unavailable because the table "' . $cfg['table'] . '" was not found. Update $utilityConfig at the top of this file.', 'warning'];
            continue;
        }
        $sql = "
        SELECT
            b.id,
            CAST(b.{$cfg['date']} AS date) AS bill_date,
            COALESCE(b.{$cfg['qty']}, 0) AS qty,
            COALESCE(b.{$cfg['cost']}, 0) AS cost
        FROM public.{$cfg['table']} b
        WHERE CAST(b.{$cfg['date']} AS date) >= CAST(:date_from AS date)
          AND CAST(b.{$cfg['date']} AS date) < (CAST(:date_to AS date) + INTERVAL '1 day')
        ORDER BY bill_date DESC, b.id DESC
        ";
        $result = analytics_query_or_empty($pdo, $sql, [':date_from' => $fetchFrom, ':date_to' => $dateTo]);
        if (!$result['ok']) {
            $queryOk = false;
            $notices[] = [$cfg['label'] . ' bills could not be queried. Check the column names in $utilityConfig and the application logs.', 'error'];
            continue;
        }
        $sourceRows = $result['rows'];
    }
    foreach ($sourceRows as $r) {
        $date = substr((string)$r['bill_date'], 0, 10);
        $ym = substr($date, 0, 7);
        $qty = (float)$r['qty'];
        $cost = (float)$r['cost'];
        $monthly[$key][$ym]['qty'] = ($monthly[$key][$ym]['qty'] ?? 0.0) + $qty;
        $monthly[$key][$ym]['cost'] = ($monthly[$key][$ym]['cost'] ?? 0.0) + $cost;
        $bills[] = [
            'key' => $key,
            'external_id' => $cfg['prefix'] . '-' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT),
            'date' => $date,
            'ym' => $ym,
            'qty' => $qty,
            'cost' => $cost,
        ];
    }
}

$eval = [];
foreach ($monthly as $key => $byMonth) {
    ksort($byMonth);
    $monthly[$key] = $byMonth;
    foreach ($byMonth as $ym => $m) {
        $prevYm = $prevMonth($ym);
        $prev = $byMonth[$prevYm] ?? null;
        $qtyPct = $prev ? $pctOf($m['qty'], $prev['qty']) : null;
        $costPct = $prev ? $pctOf($m['cost'], $prev['cost']) : null;
        $lead = $qtyPct;
        if ($lead === null || ($costPct !== null && abs($costPct) > abs($lead))) {
            $lead = $costPct;
        }
        $eval[$key][$ym] = [
            'prev_ym' => $prevYm,
            'has_prev' => $prev !== null,
            'qty_pct' => $qtyPct,
            'cost_pct' => $costPct,
            'lead' => $lead,
            'flagged' => ($qtyPct !== null && abs($qtyPct) > ANOMALY_LIMIT) || ($costPct !== null && abs($costPct) > ANOMALY_LIMIT),
        ];
    }
}

$rows = array_values(array_filter($bills, function ($b) use ($dateFrom, $dateTo, $search, $utilityConfig) {
    if ($b['date'] < $dateFrom || $b['date'] > $dateTo) {
        return false;
    }
    if ($search === '') {
        return true;
    }
    $hay = $utilityConfig[$b['key']]['label'] . ' ' . $b['external_id'] . ' ' . date('M Y', (int)strtotime($b['date']));
    return stripos($hay, $search) !== false;
}));
usort($rows, fn($a, $b) => strcmp($b['date'], $a['date']));

$months = analytics_months($dateFrom, $dateTo);
$monthlyLabels = array_column($months, 'label');

$costSeries = [];
$combinedByMonth = [];
foreach ($visibleKeys as $key) {
    $costSeries[$key] = [];
    foreach ($months as $m) {
        $c = round((float)($monthly[$key][$m['key']]['cost'] ?? 0), 2);
        $costSeries[$key][] = $c;
        $combinedByMonth[$m['key']] = ($combinedByMonth[$m['key']] ?? 0) + $c;
    }
}

$totalCost = array_sum(array_column($rows, 'cost'));
$costByUtility = [];
foreach ($visibleKeys as $key) {
    $costByUtility[$key] = array_sum(array_map(fn($b) => $b['key'] === $key ? $b['cost'] : 0.0, $rows));
}

$anomalies = [];
$changeEntries = [];
foreach ($eval as $key => $byMonth) {
    foreach ($byMonth as $ym => $ev) {
        if ($ym < $fromYm || $ym > $toYm) {
            continue;
        }
        if ($ev['flagged']) {
            $anomalies[] = $ev + ['key' => $key, 'ym' => $ym];
        }
        if ($ev['lead'] !== null) {
            $changeEntries[] = ['label' => $utilityConfig[$key]['label'] . ' · ' . $monthLabel($ym), 'value' => round((float)$ev['lead'], 1)];
        }
    }
}
usort($anomalies, fn($a, $b) => abs((float)$b['lead']) <=> abs((float)$a['lead']));
usort($changeEntries, fn($a, $b) => abs($b['value']) <=> abs($a['value']));
$changeEntries = array_slice($changeEntries, 0, 7);

$latestYm = null;
foreach ($months as $m) {
    if (($combinedByMonth[$m['key']] ?? 0) > 0) {
        $latestYm = $m['key'];
    }
}

$peakYm = null;
if ($combinedByMonth) {
    $sorted = $combinedByMonth;
    arsort($sorted);
    $peakYm = (string)array_key_first($sorted);
    if ($sorted[$peakYm] <= 0) {
        $peakYm = null;
    }
}

$topAnomaly = $anomalies[0] ?? null;
$latestFlagged = false;
foreach ($anomalies as $a) {
    if ($a['ym'] === $latestYm) {
        $latestFlagged = true;
    }
}

if ($anomalies) {
    $mainHighlight = count($anomalies) . ' anomal' . (count($anomalies) === 1 ? 'y' : 'ies') . ' detected in utility bills';
    if ($topAnomaly) {
        $mainHighlight .= ': ' . $utilityConfig[$topAnomaly['key']]['label'] . ' ' . $fmtPct($topAnomaly['lead']) . ' in ' . $monthLabel($topAnomaly['ym']);
    }
    $highlightClass = $latestFlagged ? 'is-critical' : 'is-warning';
} elseif ($rows) {
    $mainHighlight = 'All utility bills are within the normal range';
    $highlightClass = 'is-ok';
} else {
    $mainHighlight = 'No utility bills found for the selected filters';
    $highlightClass = 'is-info';
}

$insights = [];

if ($rows) {
    $parts = [];
    foreach ($visibleKeys as $key) {
        $parts[] = strtolower($utilityConfig[$key]['label']) . ' ' . $peso($costByUtility[$key]);
    }

    $insights[] = number_format(count($rows)) . ' bill(s) totaling ' . $peso($totalCost) . ' were recorded between ' . $dateFrom . ' and ' . $dateTo . ' (' . implode(', ', $parts) . ').';
    $insights[] = $anomalies
        ? count($anomalies) . ' month(s) changed by more than ' . number_format(ANOMALY_LIMIT, 0) . '% in consumption or cost compared with the previous month.'
        : 'No month changed by more than ' . number_format(ANOMALY_LIMIT, 0) . '% in consumption or cost compared with the previous month.';

    if ($topAnomaly) {
        $insights[] = $utilityConfig[$topAnomaly['key']]['label'] . ' went from ' . $monthLabel($topAnomaly['prev_ym']) . ' to ' . $monthLabel($topAnomaly['ym']) . ' with consumption ' . $fmtPct($topAnomaly['qty_pct']) . ' and cost ' . $fmtPct($topAnomaly['cost_pct']) . '.';
    }

    if ($latestYm !== null) {
        $latestPrev = $prevMonth($latestYm);
        $latestCost = (float)$combinedByMonth[$latestYm];
        $change = $pctOf($latestCost, (float)($combinedByMonth[$latestPrev] ?? 0));
        $insights[] = 'The latest month with bills (' . $monthLabel($latestYm) . ') cost ' . $peso($latestCost) . ($change !== null ? ', ' . $fmtPct($change) . ' versus ' . $monthLabel($latestPrev) . '.' : '.');
    }

    if ($peakYm !== null && count(array_filter($combinedByMonth, fn($v) => $v > 0)) > 1) {
        $insights[] = 'Combined utility cost peaked in ' . $monthLabel($peakYm) . ' at ' . $peso((float)$combinedByMonth[$peakYm]) . '.';
    }

    foreach ($monthly as $key => $byMonth) {
        $firstYm = array_key_first($byMonth);
        foreach ($byMonth as $ym => $m) {
            if ($ym >= $fromYm && $ym <= $toYm && $ym > $firstYm && !$eval[$key][$ym]['has_prev'] && $m['cost'] > 0) {
                $insights[] = $utilityConfig[$key]['label'] . ' has no bill for ' . $monthLabel($eval[$key][$ym]['prev_ym']) . ', so the change for ' . $monthLabel($ym) . ' cannot be computed. Verify the missing record.';
            }
        }
    }
} else {
    $insights[] = 'No utility bills were recorded for the selected filters.';
}

$aiResult = ['ok' => false, 'title' => 'AI suggestions unavailable', 'suggestions' => []];

if ($rows) {
    $aiMonthlyData = [];
    foreach ($months as $m) {
        foreach ($visibleKeys as $key) {
            $record = $monthly[$key][$m['key']] ?? null;
            $evaluation = $eval[$key][$m['key']] ?? null;
            $aiMonthlyData[] = [
                'month' => $m['key'],
                'utility' => $utilityConfig[$key]['label'],
                'bill_recorded' => $record !== null,
                'consumption' => $record !== null ? round($record['qty'], 2) : null,
                'cost' => $record !== null ? round($record['cost'], 2) : null,
                'previous_month' => $evaluation['prev_ym'] ?? null,
                'consumption_change_percent' => isset($evaluation['qty_pct']) ? round($evaluation['qty_pct'], 2) : null,
                'cost_change_percent' => isset($evaluation['cost_pct']) ? round($evaluation['cost_pct'], 2) : null,
                'anomaly' => $evaluation['flagged'] ?? false,
            ];
        }
    }

    $aiAnalytics = [
        'report' => 'Utility Anomaly Analysis',
        'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
        'utility_filter' => $utilityFilter,
        'anomaly_threshold_percent' => ANOMALY_LIMIT,
        'total_bills' => count($rows),
        'total_cost' => round($totalCost, 2),
        'cost_by_utility' => $costByUtility,
        'anomaly_count' => count($anomalies),
        'statistical_findings' => $insights,
        'monthly_records' => $aiMonthlyData,
    ];

    try {
        $aiResult = (new ai_integ())->generate($aiAnalytics);
    } catch (Throwable $ex) {
        error_log('AI Insight Error: ' . $ex->getMessage());
    }
}

[$highlightIcon, $highlightVars] = [
    'is-critical' => ['!', '[--accent:#b91c1c] [--tint:#fee2e2]'],
    'is-warning'  => ['!', '[--accent:#b45309] [--tint:#fef3c7]'],
    'is-ok'       => ['✓', '[--accent:#047857] [--tint:#d1fae5]'],
    'is-info'     => ['i', '[--accent:#0e7490] [--tint:#cffafe]'],
][$highlightClass];

$topDetail = null;
if ($topAnomaly) {
    $tk = $topAnomaly['key'];
    $cur = $monthly[$tk][$topAnomaly['ym']] ?? null;
    $prv = $monthly[$tk][$topAnomaly['prev_ym']] ?? null;
    if ($cur && $prv) {
        $tc = $utilityConfig[$tk];
        $topDetail = [
            'utility' => $tc['label'],
            'from' => $monthLabel($topAnomaly['prev_ym']),
            'to' => $monthLabel($topAnomaly['ym']),
            'rows' => [
                ['label' => 'Consumption', 'prev' => number_format($prv['qty'], $tc['decimals']) . ' ' . $tc['unit'], 'cur' => number_format($cur['qty'], $tc['decimals']) . ' ' . $tc['unit'], 'pct' => $topAnomaly['qty_pct']],
                ['label' => 'Cost', 'prev' => $peso((float)$prv['cost']), 'cur' => $peso((float)$cur['cost']), 'pct' => $topAnomaly['cost_pct']],
            ],
        ];
    }
}

$fieldCls = 'flex flex-col gap-1.5 text-[.85rem] font-semibold text-slate-600';
$inputCls = 'h-10 px-3 border border-slate-300 rounded-[10px] text-[.95rem] bg-white text-slate-900';
$btnCls = 'h-10 px-5 rounded-[10px] font-bold cursor-pointer';
$pillCls = 'inline-block px-3 py-[3px] rounded-full text-[.8rem] font-bold';
$aiLiCls = 'flex items-start gap-3 mb-2.5 px-3.5 py-3 bg-white border border-slate-200 rounded-[10px] text-[.92rem] leading-[1.55] text-slate-700 '
    . '[counter-increment:step] before:content-[counter(step)] before:grid before:place-items-center before:shrink-0 before:w-6 before:h-6 '
    . 'before:rounded-full before:bg-[rgb(14,58,99)] before:text-white before:text-[.8rem] before:font-extrabold';

analytics_page_start('Anomaly Analysis', 'Compare utility bills month over month to find unusual changes in consumption or cost.');
?>
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<script>tailwind.config = { corePlugins: { preflight: false } };</script>
<form class="flex flex-wrap items-end gap-[14px] bg-white border border-slate-200 rounded-xl px-5 py-4 mt-0 mb-2" method="get">
    <label class="<?= $fieldCls ?>">From
        <input class="<?= $inputCls ?>" type="date" name="from" value="<?= e($dateFrom) ?>">
    </label>
    <label class="<?= $fieldCls ?>">To
        <input class="<?= $inputCls ?>" type="date" name="to" value="<?= e($dateTo) ?>">
    </label>
    <label class="<?= $fieldCls ?>">Utility Type
        <select class="<?= $inputCls ?>" name="utility">
            <?php foreach (['all' => 'All', 'electricity' => 'Electricity', 'water' => 'Water'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $utilityFilter === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="<?= $fieldCls ?>">Search
        <input class="<?= $inputCls ?>" type="search" name="q" value="<?= e($search) ?>" placeholder="Bill no., utility or month">
    </label>
    <button class="<?= $btnCls ?> border-0 bg-[rgb(14,58,99)] text-white" type="submit">Apply Filters</button>
    <a class="<?= $btnCls ?> inline-flex items-center bg-white text-[rgb(14,58,99)] border border-slate-300 no-underline" href="?">Reset</a>
</form>
<?php
foreach ($notices as $n) {
    analytics_notice($n[0], $n[1]);
}
// sample data
if (!USE_SAMPLE_DATA && !$notices && !$rows) {
    analytics_notice('No utility bills match these filters. Confirm that bills have been encoded for this period.', 'info');
}
?>

<div class="grid grid-cols-1 min-[993px]:grid-cols-[minmax(320px,2fr)_3fr] gap-5 items-start my-5 [&>*]:min-w-0">

    <section class="bg-white border border-slate-200 rounded-[14px] px-6 py-[22px] m-0 shadow-[0_1px_3px_rgba(15,23,42,.08)] [&>*+*]:mt-[22px]">

        <p class="flex items-start gap-3 m-0 px-4 py-3.5 rounded-[10px] text-[1.1rem] font-extrabold leading-[1.35] text-[var(--accent)] bg-[var(--tint)] <?= e($highlightVars) ?>">
            <span class="grid place-items-center shrink-0 w-7 h-7 rounded-full bg-[var(--accent)] text-white text-[.95rem] font-extrabold" aria-hidden="true"><?= e($highlightIcon) ?></span>
            <span><?= e($mainHighlight) ?></span>
        </p>

        <?php if ($rows): ?>
            <div class="px-[18px] pt-[18px] pb-2 bg-slate-50 border border-slate-200 rounded-xl">
                <div class="flex items-center justify-between gap-2.5 mt-0 mb-1">
                    <h3 class="m-0 text-[1.05rem] font-extrabold text-[rgb(14,58,99)]">What to do next</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[.7rem] font-extrabold tracking-[.06em] uppercase text-indigo-700 bg-indigo-100">AI suggestions</span>
                </div>

                <?php if ($aiResult['ok']): ?>
                    <p class="mt-0 mb-3.5 text-[.95rem] font-bold text-slate-700"><?= e($aiResult['title']) ?></p>
                    <ol class="m-0 p-0 list-none [counter-reset:step]">
                        <?php foreach ($aiResult['suggestions'] as $suggestion): ?>
                            <li class="<?= $aiLiCls ?>"><span><?= e($suggestion) ?></span></li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="mt-0 mb-2.5 text-[.9rem] text-slate-500">AI suggestions are temporarily unavailable.</p>

                    <!-- placeholder lang to para makita ko lang error ng ai integration / tanggalin nalang if ever -->
                    <p class="mt-0 mb-2.5 text-[.8rem] text-red-700">Error: <?= e($aiResult['error'] ?? 'Unknown error') ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </section>

    <div class="flex flex-col gap-5">
        <?php
        analytics_chart_panel('Monthly utility cost', 'Total billed amount (₱) by billing month for each utility.', 'billTrendChart');
        analytics_chart_panel('Total cost by utility', 'Total billed amount (₱) for the selected period.', 'billUtilityChart');
        ?>
    </div>

</div>

<?php
$monthlyRows = [];
foreach ($rows as $b) {
    $monthlyRows[$b['ym']]['cost'] = ($monthlyRows[$b['ym']]['cost'] ?? 0.0) + $b['cost'];
    $monthlyRows[$b['ym']]['by'][$b['key']]['qty'] = ($monthlyRows[$b['ym']]['by'][$b['key']]['qty'] ?? 0.0) + $b['qty'];
    $monthlyRows[$b['ym']]['by'][$b['key']]['cost'] = ($monthlyRows[$b['ym']]['by'][$b['key']]['cost'] ?? 0.0) + $b['cost'];
}
krsort($monthlyRows);

$tableHeaders = ['Month'];
foreach ($visibleKeys as $key) {
    array_push($tableHeaders, $utilityConfig[$key]['label'] . ' (' . $utilityConfig[$key]['unit'] . ')', $utilityConfig[$key]['label'] . ' cost');
}
array_push($tableHeaders, 'Total cost', '% Total cost', 'Status');

analytics_table_start($tableHeaders);
if (!$monthlyRows) {
    echo '<tr><td colspan="' . count($tableHeaders) . '" class="analytics-empty">No monthly bills to display.</td></tr>';
}
foreach ($monthlyRows as $ym => $mr) {
    $prevYm = $prevMonth($ym);
    $monthTotal = $prevTotal = 0.0;
    $isFlagged = false;
    foreach ($visibleKeys as $key) {
        $monthTotal += (float)($monthly[$key][$ym]['cost'] ?? 0);
        $prevTotal += (float)($monthly[$key][$prevYm]['cost'] ?? 0);
        $isFlagged = $isFlagged || !empty($eval[$key][$ym]['flagged']);
    }
    echo '<tr><td>' . e($monthLabel($ym)) . '</td>';
    foreach ($visibleKeys as $key) {
        $by = $mr['by'][$key] ?? null;
        echo $by
            ? '<td>' . e(number_format($by['qty'], $utilityConfig[$key]['decimals'])) . '</td><td>' . e($peso($by['cost'])) . '</td>'
            : '<td>—</td><td>—</td>';
    }
    echo '<td>' . e($peso($mr['cost'])) . '</td><td>' . e($fmtPct($pctOf($monthTotal, $prevTotal))) . '</td><td><span class="' . $pillCls . ' '
        . ($isFlagged ? 'text-red-700 bg-red-100' : 'text-emerald-700 bg-emerald-100') . '">' . ($isFlagged ? 'Anomaly' : 'Normal') . '</span></td></tr>';
}
analytics_table_end();

$utilityChartLabels = array_map(fn($k) => $utilityConfig[$k]['label'], $visibleKeys);
$utilityChartValues = array_map(fn($k) => round((float)$costByUtility[$k], 2), $visibleKeys);
$utilityChartColors = array_map(fn($k) => $utilityConfig[$k]['color'], $visibleKeys);
$trendDatasets = [];
foreach ($visibleKeys as $key) {
    $trendDatasets[] = [
        'label' => $utilityConfig[$key]['label'],
        'data' => $costSeries[$key],
        'borderColor' => $utilityConfig[$key]['color'],
        'backgroundColor' => $utilityConfig[$key]['color'] . '1a',
        'fill' => true,
        'tension' => 0.3,
    ];
}
$changeLabels = array_column($changeEntries, 'label');
$changeValues = array_column($changeEntries, 'value');
$changeColors = array_map(fn($v) => abs((float)$v) > ANOMALY_LIMIT ? '#dc2626' : '#6366f1', $changeValues);
?>
<script>
    const billDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }
    };
    const noLegend = { legend: { display: false } };
    Chart.defaults.color = '#64748b';
    new Chart(document.getElementById('billTrendChart'), {
        type: 'line',
        data: { labels: <?= analytics_json($monthlyLabels) ?>, datasets: <?= analytics_json($trendDatasets) ?> },
        options: { ...billDefaults, scales: { y: { beginAtZero: true, title: { display: true, text: '₱' } } } }
    });
    new Chart(document.getElementById('billUtilityChart'), {
        type: 'bar',
        data: {
            labels: <?= analytics_json($utilityChartLabels) ?>,
            datasets: [{ label: 'Total cost (₱)', data: <?= analytics_json($utilityChartValues) ?>, backgroundColor: <?= analytics_json($utilityChartColors) ?>, borderRadius: 7 }]
        },
        options: { ...billDefaults, plugins: noLegend, scales: { y: { beginAtZero: true } } }
    });

    const changeEl = document.getElementById('billChangeChart');
    if (changeEl) {
        new Chart(changeEl, {
            type: 'bar',
            data: {
                labels: <?= analytics_json($changeLabels) ?>,
                datasets: [{ label: 'Change vs previous month (%)', data: <?= analytics_json($changeValues) ?>, backgroundColor: <?= analytics_json($changeColors) ?>, borderRadius: 7 }]
            },
            options: { ...billDefaults, indexAxis: 'y', plugins: noLegend, scales: { x: { title: { display: true, text: '%' } } } }
        });
    }
</script>
<?php analytics_page_end(); ?>