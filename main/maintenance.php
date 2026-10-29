<?php

declare(strict_types=1);
require_once __DIR__ . '/_analytics.php';
$hasMaintenance = analytics_table_exists($pdo, 'maintenance_records');
$rows = [];
$queryOk = true;
if ($hasMaintenance) {
    $where = [
        '((m.due_at::date BETWEEN :due_from AND :due_to)
      OR (m.completed_at::date BETWEEN :completed_from AND :completed_to)
      OR (m.created_at::date BETWEEN :created_from AND :created_to))'
    ];

    $params = [
        ':due_from' => $analyticsFrom,
        ':due_to' => $analyticsTo,
        ':completed_from' => $analyticsFrom,
        ':completed_to' => $analyticsTo,
        ':created_from' => $analyticsFrom,
        ':created_to' => $analyticsTo,
    ];

    if ($analyticsType === 'equipment') {
        $where[] = 'e.id IS NOT NULL';
    } elseif ($analyticsType === 'facility') {
        // A maintenance row can be associated with a facility through its equipment.
        $where[] = 'f.id IS NOT NULL';
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
        OR COALESCE(m.work_order_code, m.external_id, '') ILIKE :q4
        OR COALESCE(m.failure_cause, '') ILIKE :q5
    )";

        foreach ([':q1', ':q2', ':q3', ':q4', ':q5'] as $key) {
            $params[$key] = '%' . $analyticsSearch . '%';
        }
    }

    $sql = "
    SELECT
        m.id,
        m.external_id,
        m.work_order_code,
        m.external_asset_tag,
        m.equipment_id AS asset_id,
        CASE
            WHEN e.id IS NOT NULL THEN 'equipment'
            WHEN f.id IS NOT NULL THEN 'facility'
            ELSE 'unknown'
        END AS asset_type,
        CASE
            WHEN e.id IS NOT NULL AND f.name IS NOT NULL
                THEN e.name || ' — ' || f.name
            ELSE COALESCE(e.name, f.name, 'Unlinked asset')
        END AS asset_name,
        COALESCE(e.location, f.location, '—') AS location,
        m.maintenance_type,
        m.status,
        m.priority,
        m.due_at,
        m.started_at,
        m.completed_at,
        m.labor_cost,
        m.parts_cost,
        COALESCE(m.labor_cost, 0) + COALESCE(m.parts_cost, 0) AS total_cost,
        m.failure_cause,
        m.created_at
    FROM public.maintenance_records m
    LEFT JOIN public.equipment e ON e.id = m.equipment_id
    LEFT JOIN public.facilities f ON f.id = e.facility_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY
        CASE lower(m.priority)
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            ELSE 4
        END,
        m.due_at NULLS LAST,
        m.created_at DESC
";

    $result = analytics_query_or_empty($pdo, $sql, $params);
    $rows = $result['rows'];
    $queryOk = $result['ok'];
}
$types = ['Preventive', 'Corrective', 'Inspection', 'Other'];
$typeValues = array_map(fn($t) => count(array_filter($rows, fn($r) => strcasecmp((string)$r['maintenance_type'], $t) === 0)), $types);
$monthly = analytics_months($analyticsFrom, $analyticsTo);
$monthlyMap = [];
foreach ($rows as $r) {
    $d = $r['completed_at'] ?: ($r['due_at'] ?: $r['created_at']);
    if ($d) {
        $k = substr((string)$d, 0, 7);
        $monthlyMap[$k] = ($monthlyMap[$k] ?? 0) + 1;
    }
}
$monthlyLabels = array_column($monthly, 'label');
$monthlyValues = array_map(fn($m) => $monthlyMap[$m['key']] ?? 0, $monthly);
$today = new DateTimeImmutable('today');
$open = array_filter($rows, fn($r) => !in_array(strtolower((string)$r['status']), ['completed', 'cancelled', 'closed'], true));
$overdue = array_filter($open, fn($r) => !empty($r['due_at']) && new DateTimeImmutable((string)$r['due_at']) < $today);
$statusLabels = ['Planned', 'Assigned', 'In Progress', 'Completed', 'Overdue', 'Cancelled'];
$statusValues = array_map(function ($label) use ($rows, $today) {
    return count(array_filter($rows, function ($r) use ($label, $today) {
        $isOpen = !in_array(strtolower((string)$r['status']), ['completed', 'cancelled', 'closed'], true);
        $isOverdue = $isOpen && !empty($r['due_at']) && new DateTimeImmutable((string)$r['due_at']) < $today;
        $effective = $isOverdue ? 'Overdue' : (string)$r['status'];
        return strcasecmp($effective, $label) === 0;
    }));
}, $statusLabels);
$pmDue = array_filter($rows, fn($r) => strtolower((string)$r['maintenance_type']) === 'preventive' && !empty($r['due_at']) && substr((string)$r['due_at'], 0, 10) >= $analyticsFrom && substr((string)$r['due_at'], 0, 10) <= $analyticsTo);
$pmOnTime = array_filter($pmDue, fn($r) => strtolower((string)$r['status']) === 'completed' && !empty($r['completed_at']) && !empty($r['due_at']) && strtotime((string)$r['completed_at']) <= strtotime((string)$r['due_at']));
$pmRate = count($pmDue) ? count($pmOnTime) / count($pmDue) * 100 : null;
$totalCost = array_sum(array_map(fn($r) => (float)$r['total_cost'], $rows));
$costIncomplete = count(array_filter($rows, fn($r) => $r['labor_cost'] === null && $r['parts_cost'] === null));
$completedDurations = [];
foreach ($rows as $r) if (!empty($r['started_at']) && !empty($r['completed_at']) && strtolower((string)$r['status']) === 'completed') {
    $hours = (strtotime((string)$r['completed_at']) - strtotime((string)$r['started_at'])) / 3600;
    if ($hours >= 0) $completedDurations[] = $hours;
}
$avgRepair = count($completedDurations) ? array_sum($completedDurations) / count($completedDurations) : null;

analytics_page_start('Maintenance Analysis', 'Evaluate maintenance timeliness, backlog, work type, cost, and repair performance using linked maintenance-system records.');
analytics_filter_form($analyticsFrom, $analyticsTo, $analyticsType, $analyticsCondition, $analyticsSearch);
if (!$hasMaintenance) analytics_notice('Maintenance analytics are waiting for data integration. Run migrations/analytics_extensions.sql to create the reporting mirror, then sync work-order data from the Maintenance & Operations subsystem. This report does not invent sample maintenance records.', 'warning');
elseif (!$queryOk) analytics_notice('Maintenance records could not be queried. Check the database schema and application logs.', 'error');
elseif (!$rows) analytics_notice('No maintenance records match these filters. Verify that the maintenance subsystem has synchronized records for this period.', 'info');
if ($avgRepair !== null) analytics_notice('Mean recorded repair duration is ' . number_format($avgRepair, 1) . ' hours across completed work orders with both start and completion timestamps.', 'info');
if (count($overdue) > 0) analytics_notice(count($overdue) . ' work orders are overdue. Review the overdue list by priority and age, then assign a responsible owner and target completion date.', 'warning');
echo '<section class="analytics-metrics">';
analytics_metric('Work orders in period', number_format(count($rows)), 'Linked maintenance records returned', '▤');
analytics_metric('Overdue open work', number_format(count($overdue)), 'Open records past due date', '⚠');
analytics_metric('Preventive compliance', $pmRate === null ? 'N/A' : number_format($pmRate, 1) . '%', $pmRate === null ? 'No preventive tasks due in this period' : count($pmOnTime) . ' completed on time of ' . count($pmDue) . ' due', '✓');
analytics_metric('Recorded maintenance cost', analytics_money($totalCost), $costIncomplete ? $costIncomplete . ' records have no cost data; totals are incomplete' : 'Sum of reported labor and parts costs', '₱');
echo '</section>';
echo '<section class="analytics-grid">';
analytics_chart_panel('Work order status', 'Distribution of linked records by current status.', 'maintenanceStatusChart');
analytics_chart_panel('Maintenance type', 'Preventive, corrective, and inspection work.', 'maintenanceTypeChart');
analytics_chart_panel('Work order trend', 'Monthly records grouped by completion date, due date, or creation date.', 'maintenanceTrendChart', true);
echo '</section>';
analytics_table_start(['Work order', 'Asset / facility', 'Type', 'Priority', 'Due date', 'Status', 'Cost', 'Failure cause']);
if (!$rows) echo '<tr><td colspan="8" class="analytics-empty">No maintenance records to display.</td></tr>';
foreach ($rows as $r) {
    $code = $r['work_order_code'] ?: ($r['external_id'] ?: ('WO-' . $r['id']));
    $isOverdue = !in_array(strtolower((string)$r['status']), ['completed', 'cancelled', 'closed'], true) && !empty($r['due_at']) && new DateTimeImmutable((string)$r['due_at']) < $today;
    $status = $isOverdue ? 'Overdue' : (string)$r['status'];
    echo '<tr><td>' . e((string)$code) . '</td><td>' . e((string)$r['asset_name']) . '<br><small>' . e((string)($r['external_asset_tag'] ?: $r['location'])) . '</small></td><td>' . e((string)$r['maintenance_type']) . '</td><td>' . analytics_pill((string)$r['priority']) . '</td><td>' . e(analytics_date_label($r['due_at'] ?? null)) . '</td><td>' . analytics_pill($status) . '</td><td>' . e(analytics_money((float)$r['total_cost'])) . '</td><td>' . e((string)($r['failure_cause'] ?: '—')) . '</td></tr>';
}
analytics_table_end();
?>
<script>
    const maintenanceChartDefaults = {
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
    new Chart(document.getElementById('maintenanceStatusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= analytics_json($statusLabels) ?>,
            datasets: [{
                data: <?= analytics_json($statusValues) ?>,
                backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#cbd5e1'],
                borderWidth: 0
            }]
        },
        options: maintenanceChartDefaults
    });
    new Chart(document.getElementById('maintenanceTypeChart'), {
        type: 'bar',
        data: {
            labels: <?= analytics_json($types) ?>,
            datasets: [{
                label: 'Work orders',
                data: <?= analytics_json($typeValues) ?>,
                backgroundColor: ['#2563eb', '#f59e0b', '#0f9f79', '#64748b'],
                borderRadius: 7
            }]
        },
        options: {
            ...maintenanceChartDefaults,
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
    new Chart(document.getElementById('maintenanceTrendChart'), {
        type: 'line',
        data: {
            labels: <?= analytics_json($monthlyLabels) ?>,
            datasets: [{
                label: 'Work orders',
                data: <?= analytics_json($monthlyValues) ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.12)',
                fill: true,
                tension: .3
            }]
        },
        options: {
            ...maintenanceChartDefaults,
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