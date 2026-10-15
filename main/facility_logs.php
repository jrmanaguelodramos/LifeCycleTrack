<?php
require_once __DIR__ . '/_app.php';
require_login();

$facilityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$facility = [
    'id' => $facilityId, 'name' => 'Reception Hall', 'condition' => 'Good',
    'status' => 'Serviceable',
    'description' => 'Main academic building used for classrooms and administrative offices.',
];

$logs = [
    ['date' => '2026-05-15', 'condition' => 'Good', 'status' => 'Serviceable', 'remarks' => 'Routine inspection completed. Building is in good condition.'],
    ['date' => '2026-02-20', 'condition' => 'Good', 'status' => 'Serviceable', 'remarks' => 'Minor lighting issues addressed. Classrooms remain usable.'],
    ['date' => '2025-11-12', 'condition' => 'Fair', 'status' => 'For maintenance', 'remarks' => 'Minor ceiling leaks observed in selected areas.'],
    ['date' => '2025-08-08', 'condition' => 'Fair', 'status' => 'For maintenance', 'remarks' => 'Several wall sections require repainting and minor repairs.'],
    ['date' => '2025-05-14', 'condition' => 'Good', 'status' => 'Serviceable', 'remarks' => 'Routine inspection. Electrical fixtures are functioning properly.'],
    ['date' => '2025-02-03', 'condition' => 'Good', 'status' => 'Serviceable', 'remarks' => 'Minor maintenance completed in hallways and classrooms.'],
    ['date' => '2024-11-10', 'condition' => 'Poor', 'status' => 'For repair', 'remarks' => 'Roof leakage and damaged ceiling sections require repair.'],
    ['date' => '2024-08-05', 'condition' => 'Good', 'status' => 'Serviceable', 'remarks' => 'Initial inspection completed. Facility was suitable for use.'],
];

if (!function_exists('ev')) {
    function ev($value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('facility_badge')) {
    function facility_badge(string $condition): string {
        $class = strtolower($condition);
        return '<span class="cl-badge cl-' . ev($class) . '"><i></i>' . ev($condition) . '</span>';
    }
}

usort($logs, fn($a, $b) => strcmp($b['date'], $a['date']));

foreach ($logs as $i => &$log) {
    $older = $logs[$i + 1] ?? null;
    $log['from'] = $older ? $older['condition'] : null;
}
unset($log);

$total = count($logs);
$lastChk = $logs[0]['date'] ?? null;
$counts = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];

foreach ($logs as $log) {
    if (isset($counts[$log['condition']])) $counts[$log['condition']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Condition History</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition_logs.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="cl-page">
            <h1 class="text-3xl font-bold">Facility Condition History</h1>
            <p class="mt-2 mb-3 text-gray-600">View the condition records and inspection history of this facility.</p>

            <nav aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2" style="color: #0E3A63; font-size:18px;">
                    <li><a href="facilities_list.php" class="font-medium hover:text-[#0E3A63] no-underline hover:underline"><i class="fa-solid fa-list mr-1"></i> Back to List</a></li>
                    <li><i class="fa-solid fa-chevron-right text-xs mx-2"></i> <a href="viewfacility.php" class="font-medium hover:text-[#0E3A63] no-underline hover:underline">Profile/Information</a></li>
                    <li><i class="fa-solid fa-chevron-right text-xs mx-2"></i> <a href="facility_logs.php" class="font-medium hover:text-[#0E3A63] no-underline hover:underline">Condition History</a></li>
                </ol>
            </nav>

            <div class="cl-wrap">
                <div class="cl-head">
                    <div class="cl-who">
                        <div class="cl-img"><img src="../img/logo.png" alt="<?= ev($facility['name']) ?>"></div>
                        <div>
                            <h2><?= ev($facility['name']) ?></h2>
                            <p class="cl-id"><?= str_pad((string) $facility['id'], 4, '0', STR_PAD_LEFT) ?></p>
                            <?= facility_badge($facility['condition']) ?>
                            <p class="cl-desc"><?= ev($facility['description']) ?></p>
                        </div>
                    </div>
                    <button type="button" id="exportBtn" class="cl-export"><i class="fa-solid fa-file-export"></i> Export</button>
                </div>

                <div class="cl-stats">
                    <div class="cl-stat"><small>Total inspections</small><div class="big"><?= $total ?></div></div>
                    <div class="cl-stat">
                        <small>Last inspected</small>
                        <div class="big"><?= $lastChk ? date('F j, Y', strtotime($lastChk)) : 'N/A' ?></div>
                    </div>
                    <div class="cl-stat"><small>Current condition</small><?= facility_badge($facility['condition']) ?></div>
                    <div class="cl-stat">
                        <small>Condition breakdown</small>
                        <div class="cl-break">
                            <span><i class="cl-dot cl-d-good"></i><?= $counts['Good'] ?> Good</span>
                            <span><i class="cl-dot cl-d-fair"></i><?= $counts['Fair'] ?> Fair</span>
                            <span><i class="cl-dot cl-d-poor"></i><?= $counts['Poor'] ?> Poor</span>
                        </div>
                    </div>
                </div>

                <section class="cl-card">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Condition History</h3>
                    <div class="cl-tools">
                        <div class="cl-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input id="logSearch" type="text" placeholder="Search remarks or status">
                        </div>
                        <select id="logFilter">
                            <option value="">All conditions</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>

                    <div class="cl-scroll">
                        <table class="cl-table" id="logTable">
                            <thead>
                                <tr class="text">
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-calendar mr-2"></i> Date</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-shield-halved mr-2"></i> Condition</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-clipboard-check mr-2"></i> Status</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Change</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-message mr-2"></i> Remarks</div></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr data-condition="<?= ev($log['condition']) ?>">
                                        <td class="date"><?= date('M d, Y', strtotime($log['date'])) ?></td>
                                        <td><?= facility_badge($log['condition']) ?></td>
                                        <td><?= ev($log['status']) ?></td>
                                        <td>
                                            <?php if ($log['from'] === null): ?>
                                                <span class="cl-nochange">Initial inspection</span>
                                            <?php elseif ($log['from'] === $log['condition']): ?>
                                                <span class="cl-nochange">No change</span>
                                            <?php else: ?>
                                                <span class="cl-chg"><?= facility_badge($log['from']) ?> <i class="fa-solid fa-arrow-right text-xs"></i> <?= facility_badge($log['condition']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= ev($log['remarks']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="cl-empty" id="noResults" style="display:none"><td colspan="5">No records match your search.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('#logTable tbody tr[data-condition]'));
            const search = document.getElementById('logSearch');
            const filter = document.getElementById('logFilter');
            const none = document.getElementById('noResults');
            const exportBtn = document.getElementById('exportBtn');

            function applyFilters() {
                const query = search.value.trim().toLowerCase();
                const condition = filter.value;
                let shown = 0;

                rows.forEach(row => {
                    const matchesCondition = !condition || row.dataset.condition === condition;
                    const matchesSearch = !query || row.textContent.toLowerCase().includes(query);
                    const visible = matchesCondition && matchesSearch;
                    row.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });

                none.style.display = shown ? 'none' : '';
            }

            search.addEventListener('input', applyFilters);
            filter.addEventListener('change', applyFilters);

            exportBtn.addEventListener('click', function () {
                const lines = [['Date', 'Condition', 'Status', 'Change', 'Remarks']];

                rows.filter(row => row.style.display !== 'none').forEach(row => {
                    const cells = row.querySelectorAll('td');
                    lines.push([
                        cells[0].innerText.trim(),
                        cells[1].innerText.trim(),
                        cells[2].innerText.trim(),
                        cells[3].innerText.replace(/\s+/g, ' ').trim(),
                        cells[4].innerText.trim()
                    ]);
                });

                const csv = '\uFEFF' + lines.map(line =>
                    line.map(value => '"' + value.replace(/"/g, '""') + '"').join(',')
                ).join('\r\n');

                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = url;
                link.download = 'facility_condition_history_<?= (int) $facility['id'] ?>.csv';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            });
        })();
    </script>
</body>
</html>
