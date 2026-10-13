<?php
require_once __DIR__ . '/_app.php';
require_login();

$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$equipment = [
    'id'          => $equipmentId,
    'name'        => 'Printer',
    'condition'   => 'Good',
    'status'      => 'Serviceable (functioning)',
    'description' => 'Printer for document printing',
];

$logs = [
    ['date' => '2026-05-15', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'score' => 90, 'by' => 'Mang Juan', 'remarks' => 'Routine inspection. No issues found.'],
    ['date' => '2026-02-20', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'score' => 88, 'by' => 'Mang Juan', 'remarks' => 'Paper feed roller replaced. Back to normal use.'],
    ['date' => '2025-11-12', 'condition' => 'Fair', 'status' => 'Unserviceable: for maintenance',   'score' => 72, 'by' => 'Ana Reyes',  'remarks' => 'Paper jams occasionally. Servicing scheduled.'],
    ['date' => '2025-08-08', 'condition' => 'Fair', 'status' => 'Unserviceable: for maintenance',   'score' => 70, 'by' => 'Ana Reyes',  'remarks' => 'Ink cartridge nearly empty. Cleaning requested.'],
    ['date' => '2025-05-14', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'score' => 91, 'by' => 'Mang Juan', 'remarks' => 'Routine inspection. No issues found.'],
    ['date' => '2025-02-03', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'score' => 86, 'by' => 'Mang Juan', 'remarks' => 'Print head replaced and recalibrated.'],
    ['date' => '2024-11-10', 'condition' => 'Poor', 'status' => 'Unserviceable: for repair',        'score' => 45, 'by' => 'Ana Reyes',  'remarks' => 'Printer not printing. Sent for repair.'],
    ['date' => '2024-08-05', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'score' => 92, 'by' => 'Mang Juan', 'remarks' => 'Newly deployed. Working properly.'],
];

if (!function_exists('ev')) {
    function ev($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

usort($logs, fn($a, $b) => strcmp($b['date'], $a['date']));
foreach ($logs as $i => &$log) {
    $older = $logs[$i + 1] ?? null;
    $log['from'] = $older ? $older['condition'] : null;
}
unset($log);

$total   = count($logs);
$lastChk = $logs[0]['date'] ?? null;
$counts  = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];
foreach ($logs as $l) {
    if (isset($counts[$l['condition']])) {
        $counts[$l['condition']]++;
    }
}

function badge(string $c): string
{
    $k = strtolower($c);
    return '<span class="cl-badge cl-' . $k . '"><i></i>' . ev($c) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Condition History</title>
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
            <h1 class="text-3xl font-bold">Condition History</h1>
            <p class="mt-2 mb-3 text-gray-600">View the condition records and inspection history of this equipment.</p>
            <a href="viewequip.php?id=<?= ev($equipment['id']) ?>" class="text-[#103F6C] font-bold flex items-center mb-5">
                <i class="fa-solid fa-arrow-left px-3"></i>Back to Equipment Profile
            </a>

            <div class="cl-wrap">
                <div class="cl-head">
                    <div class="cl-who">
                        <div class="cl-img"><img src="../img/logo.png" alt="<?= ev($equipment['name']) ?>"></div>
                        <div>
                            <h2><?= ev($equipment['name']) ?></h2>
                            <p class="cl-id"><?= str_pad((string) $equipment['id'], 4, '0', STR_PAD_LEFT) ?></p>
                            <?= badge($equipment['condition']) ?>
                            <p class="cl-desc"><?= ev($equipment['description']) ?></p>
                        </div>
                    </div>
                    <button type="button" id="exportBtn" class="cl-export"><i class="fa-solid fa-file-export"></i> Export</button>
                </div>

                <div class="cl-stats">
                    <div class="cl-stat"><small>Total checks</small><div class="big"><?= $total ?></div></div>
                    <div class="cl-stat"><small>Last checked</small><div class="big"><?= $lastChk ? date('F j, Y', strtotime($lastChk)) : 'N/A' ?></div></div>
                    <div class="cl-stat"><small>Current condition</small><?= badge($equipment['condition']) ?></div>
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
                            <input id="logSearch" type="text" placeholder="Search remarks">
                        </div>
                        <select id="logFilter">
                            <option value="">All conditions</option>
                            <option>Good</option>
                            <option>Fair</option>
                            <option>Poor</option>
                        </select>
                    </div>

                    <div class="cl-scroll">
                        <table class="cl-table" id="logTable">
                            <thead>
                              <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-calendar mr-2"></i> Date</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-shield-halved mr-2"></i> Condition</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-clipboard-check mr-2"></i> Status</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-arrow-right-arrow-left mr-2"></i> Change</div></th>
                                    <th class="text-center"><div class="flex items-center justify-center"><i class="fa-solid fa-message mr-2"></i> Remarks</div></th>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr data-condition="<?= ev($log['condition']) ?>">
                                        <td class="date"><?= date('M d, Y', strtotime($log['date'])) ?></td>
                                        <td><?= badge($log['condition']) ?></td>
                                        <td><?= ev($log['status']) ?></td>
                                        <td>
                                            <?php if ($log['from'] === null): ?>
                                                <span class="cl-nochange">Initial check</span>
                                            <?php elseif ($log['from'] === $log['condition']): ?>
                                                <span class="cl-nochange">No change</span>
                                            <?php else: ?>
                                                <span class="cl-chg"><?= badge($log['from']) ?> <i class="fa-solid fa-arrow-right text-xs"></i> <?= badge($log['condition']) ?></span>
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

            function apply() {
                const q = search.value.trim().toLowerCase();
                const f = filter.value;
                let shown = 0;
                rows.forEach(r => {
                    const ok = (!f || r.dataset.condition === f) && (!q || r.textContent.toLowerCase().includes(q));
                    r.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                none.style.display = shown ? 'none' : '';
            }
            search.addEventListener('input', apply);
            filter.addEventListener('change', apply);

            document.getElementById('exportBtn').addEventListener('click', function () {
                const lines = [['Date', 'Condition', 'Status', 'Change', 'Remarks']];
                rows.filter(r => r.style.display !== 'none').forEach(r => {
                    const td = r.querySelectorAll('td');
                    lines.push([
                        td[0].innerText.trim(),
                        td[1].innerText.trim(),
                        td[2].innerText.trim(),
                        td[3].innerText.replace(/\s+/g, ' ').trim(),
                        td[4].innerText.trim()
                    ]);
                });
                const csv = lines.map(l => l.map(v => '"' + v.replace(/"/g, '""') + '"').join(',')).join('\r\n');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
                a.download = 'condition_history_<?= (int) $equipment['id'] ?>.csv';
                a.click();
                URL.revokeObjectURL(a.href);
            });
        })();
    </script>

</body>

</html>