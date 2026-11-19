<?php
declare(strict_types=1);

require_once __DIR__ . '/_analytics.php';

$dateFrom = (string)($_GET['from'] ?? $analyticsFrom);
$dateTo = (string)($_GET['to'] ?? $analyticsTo);
$dateFrom = analytics_date($dateFrom, $analyticsFrom);
$dateTo = analytics_date($dateTo, $analyticsTo);
if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];

$utility = (string)($_GET['utility'] ?? 'all');
if (!in_array($utility, ['all', 'electricity', 'water'], true)) $utility = 'all';
$search = trim(substr((string)($_GET['q'] ?? ''), 0, 100));
$utilityLabels = ['electricity' => 'Electricity', 'water' => 'Water'];
$units = ['electricity' => 'kWh', 'water' => 'm³'];
$prefixes = ['electricity' => 'ELC', 'water' => 'WTR'];

$rows = [];
$queryError = '';
$tableExists = analytics_table_exists($pdo, 'central_external_snapshots');
if (!$tableExists) {
    $queryError = 'The LifeCycleTrack Central snapshot table is missing. Apply database/migration_central_inbox.sql first.';
} else {
    $sql = "SELECT source_id, payload, source_updated_at
              FROM public.central_external_snapshots
             WHERE source_system = 'UtilityTrack'
               AND record_type = 'utility_bill'
               AND deleted_at IS NULL
               AND COALESCE(NULLIF(payload->>'billing_date','')::date,
                            NULLIF(payload->>'billing_period_to','')::date,
                            source_updated_at::date) >= CAST(:date_from AS date)
               AND COALESCE(NULLIF(payload->>'billing_date','')::date,
                            NULLIF(payload->>'billing_period_to','')::date,
                            source_updated_at::date) < CAST(:date_to AS date) + INTERVAL '1 day'";
    $params = [':date_from' => $dateFrom, ':date_to' => $dateTo];
    if ($utility !== 'all') {
        $sql .= " AND payload->>'utility' = :utility";
        $params[':utility'] = $utility;
    }
    $sql .= ' ORDER BY COALESCE(NULLIF(payload->>\'billing_date\',\'\')::date, NULLIF(payload->>\'billing_period_to\',\'\')::date, source_updated_at::date) DESC, source_updated_at DESC LIMIT 5000';
    $result = analytics_query_or_empty($pdo, $sql, $params);
    if (!$result['ok']) {
        $queryError = 'Utility bill snapshots could not be queried. Check the LifeCycleTrack database connection and inbox migration.';
    } else {
        foreach ($result['rows'] as $snapshot) {
            $payload = is_array($snapshot['payload'] ?? null)
                ? $snapshot['payload']
                : json_decode((string)($snapshot['payload'] ?? '{}'), true);
            if (!is_array($payload)) continue;

            $kind = strtolower((string)($payload['utility'] ?? ''));
            if (!isset($utilityLabels[$kind])) continue;
            $billDate = substr((string)($payload['billing_date'] ?? ''), 0, 10);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $billDate)) {
                $billDate = substr((string)($payload['billing_period_to'] ?? ''), 0, 10);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $billDate)) {
                $billDate = substr((string)($snapshot['source_updated_at'] ?? ''), 0, 10);
            }
            $id = (string)($snapshot['source_id'] ?? '');
            $row = [
                'id' => $id,
                'reference' => $prefixes[$kind] . '-' . $id,
                'utility' => $kind,
                'date' => $billDate,
                'period' => trim((string)($payload['billing_period'] ?? '')),
                'period_from' => (string)($payload['billing_period_from'] ?? ''),
                'period_to' => (string)($payload['billing_period_to'] ?? ''),
                'location' => trim((string)($payload['location'] ?? '')),
                'source' => trim((string)($payload['source'] ?? '')),
                'consumption' => is_numeric($payload['consumption'] ?? null) ? (float)$payload['consumption'] : null,
                'rate' => is_numeric($payload['rate'] ?? null) ? (float)$payload['rate'] : null,
                'amount' => is_numeric($payload['total_amount'] ?? null) ? (float)$payload['total_amount'] : 0.0,
                'status' => trim((string)($payload['status'] ?? '')),
                'due_date' => (string)($payload['due_date'] ?? ''),
                'received_at' => (string)($snapshot['source_updated_at'] ?? ''),
            ];
            $haystack = strtolower(implode(' ', [$row['reference'], $row['location'], $row['source'], $row['period'], $row['status']]));
            if ($search !== '' && !str_contains($haystack, strtolower($search))) continue;
            $rows[] = $row;
        }
    }
}

$billCount = count($rows);
$totalAmount = array_sum(array_column($rows, 'amount'));
$electricityTotal = array_sum(array_map(static fn(array $r): float => $r['utility'] === 'electricity' ? $r['amount'] : 0.0, $rows));
$waterTotal = array_sum(array_map(static fn(array $r): float => $r['utility'] === 'water' ? $r['amount'] : 0.0, $rows));
$months = analytics_months($dateFrom, $dateTo);
$monthlyTotals = array_fill_keys(array_column($months, 'key'), 0.0);
foreach ($rows as $row) {
    $month = substr($row['date'], 0, 7);
    if (array_key_exists($month, $monthlyTotals)) $monthlyTotals[$month] += $row['amount'];
}

if (($_GET['export'] ?? '') === 'csv' && $queryError === '') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="utility-bills-' . $dateFrom . '-to-' . $dateTo . '.csv"');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Bill ID', 'Utility', 'Billing date', 'Billing period', 'Location', 'Provider/source', 'Consumption', 'Unit', 'Rate', 'Total amount', 'Status', 'Due date', 'Source updated at']);
    foreach ($rows as $row) {
        $period = $row['period'] ?: trim($row['period_from'] . ' to ' . $row['period_to'], ' to');
        $values = [$row['reference'], $utilityLabels[$row['utility']], $row['date'], $period, $row['location'], $row['source'], $row['consumption'], $units[$row['utility']], $row['rate'], number_format($row['amount'], 2, '.', ''), $row['status'], $row['due_date'], $row['received_at']];
        foreach ($values as &$value) {
            $value = (string)($value ?? '');
            if (preg_match('/^[\s]*[=+@\-]/', $value)) $value = "'" . $value;
        }
        unset($value);
        fputcsv($out, $values);
    }
    fclose($out);
    exit;
}

analytics_page_start('Utility Bills Report', 'Read-only report of UtilityTrack bill snapshots received through Central. UtilityTrack remains the source of truth.');
?>
<form class="analytics-filters no-print" method="get">
    <label>From<input type="date" name="from" value="<?= e($dateFrom) ?>"></label>
    <label>To<input type="date" name="to" value="<?= e($dateTo) ?>"></label>
    <label>Utility<select name="utility">
        <option value="all" <?= $utility === 'all' ? 'selected' : '' ?>>All utilities</option>
        <option value="electricity" <?= $utility === 'electricity' ? 'selected' : '' ?>>Electricity</option>
        <option value="water" <?= $utility === 'water' ? 'selected' : '' ?>>Water</option>
    </select></label>
    <label class="search-field">Search<input type="search" name="q" maxlength="100" value="<?= e($search) ?>" placeholder="Bill ID, location, provider..."></label>
    <button class="analytics-button" type="submit">Apply filters</button>
    <a class="analytics-reset" href="utility_bills_report.php">Reset</a>
</form>

<?php if ($queryError !== ''): ?>
    <?php analytics_notice($queryError, 'error'); ?>
<?php else: ?>
    <div class="no-print" style="display:flex;justify-content:flex-end;margin:-6px 0 14px">
        <a class="analytics-button" href="?<?= e(http_build_query(['from'=>$dateFrom,'to'=>$dateTo,'utility'=>$utility,'q'=>$search,'export'=>'csv'])) ?>">Export CSV</a>
    </div>
    <section class="analytics-metrics">
        <?php analytics_metric('Bills in report', number_format($billCount), 'UtilityTrack records received for the selected filters.', '▤'); ?>
        <?php analytics_metric('Total billed', analytics_money($totalAmount), 'Sum of the displayed bill amounts.', '₱'); ?>
        <?php analytics_metric('Electricity', analytics_money($electricityTotal), 'Electricity bills in this report.', '⚡'); ?>
        <?php analytics_metric('Water', analytics_money($waterTotal), 'Water bills in this report.', '♧'); ?>
    </section>
    <?php analytics_chart_panel('Monthly billed amount', 'Total billed amount by the billing date or period end date.', 'utilityBillMonthlyChart', true); ?>
    <section class="analytics-panel table-panel">
        <div class="panel-heading"><h2>Utility bills</h2><p>Showing up to 5,000 received bill snapshots. Personal account details and source documents are not included in the Central integration payload.</p></div>
        <div class="table-scroll"><table><thead><tr>
            <th>Bill ID</th><th>Utility</th><th>Billing date</th><th>Billing period</th><th>Location / provider</th><th>Consumption</th><th>Rate</th><th>Total</th><th>Status</th><th>Due date</th>
        </tr></thead><tbody>
        <?php if (!$rows): ?><tr><td colspan="10" class="analytics-empty">No UtilityTrack bill snapshots match these filters.</td></tr><?php endif; ?>
        <?php foreach ($rows as $row):
            $period = $row['period'] ?: trim($row['period_from'] . ' to ' . $row['period_to'], ' to');
            $locationSource = trim(implode(' · ', array_filter([$row['location'], $row['source']])));
        ?><tr>
            <td><?= e($row['reference']) ?></td>
            <td><?= e($utilityLabels[$row['utility']]) ?></td>
            <td><?= e($row['date']) ?></td>
            <td><?= e($period ?: '—') ?></td>
            <td><?= e($locationSource ?: '—') ?></td>
            <td><?= $row['consumption'] === null ? '—' : e(number_format($row['consumption'], $row['utility'] === 'electricity' ? 0 : 2) . ' ' . $units[$row['utility']]) ?></td>
            <td><?= $row['rate'] === null ? '—' : e(analytics_money($row['rate'])) ?></td>
            <td><?= e(analytics_money($row['amount'])) ?></td>
            <td><?= $row['status'] !== '' ? analytics_pill($row['status']) : '—' ?></td>
            <td><?= e($row['due_date'] ?: '—') ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
    <script>
    (() => {
        const canvas = document.getElementById('utilityBillMonthlyChart');
        if (!canvas || typeof Chart === 'undefined') return;
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: <?= analytics_json(array_column($months, 'label')) ?>,
                datasets: [{ label: 'Billed amount (₱)', data: <?= analytics_json(array_values($monthlyTotals)) ?>, backgroundColor: '#1d4ed8', borderRadius: 6 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: value => '₱' + Number(value).toLocaleString() } } } }
        });
    })();
    </script>
<?php endif; ?>
<?php analytics_page_end(); ?>
