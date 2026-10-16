<?php
require_once __DIR__ . '/_app.php';
require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}

$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$equipment = [
    'id'          => $equipmentId,
    'name'        => 'Printer',
    'condition'   => 'Good',
    'status'      => 'Serviceable (functioning)',
    'description' => 'Printer for document printing',
];

$logs = [
    ['date' => '2026-05-15', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'remarks' => 'Routine inspection. No issues found.'],
    ['date' => '2026-02-20', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'remarks' => 'Paper feed roller replaced. Back to normal use.'],
    ['date' => '2025-11-12', 'condition' => 'Fair', 'status' => 'Unserviceable: for maintenance',   'remarks' => 'Paper jams occasionally. Servicing scheduled.'],
    ['date' => '2025-08-08', 'condition' => 'Fair', 'status' => 'Unserviceable: for maintenance',   'remarks' => 'Ink cartridge nearly empty. Cleaning requested.'],
    ['date' => '2025-05-14', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'remarks' => 'Routine inspection. No issues found.'],
    ['date' => '2025-02-03', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'remarks' => 'Print head replaced and recalibrated.'],
    ['date' => '2024-11-10', 'condition' => 'Poor', 'status' => 'Unserviceable: for repair',        'remarks' => 'Printer not printing. Sent for repair.'],
    ['date' => '2024-08-05', 'condition' => 'Good', 'status' => 'Serviceable (functioning)',        'remarks' => 'Newly deployed. Working properly.'],
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

$statusOptions = [
    'Serviceable (functioning)',
    ['group' => 'Unserviceable', 'options' => [
        'Unserviceable: for maintenance',
        'Unserviceable: for repair',
        'Unserviceable: for inspection',
    ]],
    'Beyond repair (irreparable)',
    'Subject for disposal or condemnation',
    'Disposed',
];

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
    <link rel="stylesheet" href="../dist/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition_logs.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="cl-page">

        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="flex items-center gap-2 text-sm text-slate-500 [&_a:hover]:text-blue-900 [&_a:hover]:underline [&_i.sep]:text-[10px] [&_i.sep]:text-slate-300">
                <li><a href="equipments_list.php" class="block max-w-[200px] no-underline hover:underline mr-2 truncate"> <i class="fa-solid fa-wrench mr-2"></i>Equipments List</a></li>
                <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right mr-2"></i></li>
                <li><a href="viewequip.php" class="block max-w-[200px] no-underline hover:underline mr-2 truncate"> Information</a></li>
                <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right mr-2"></i></li>
                <li aria-current="page" class="font-medium text-blue-900 ">Condition Logs</li>
            </ol>
        </nav>

            <h1 class="text-3xl font-bold">Condition History</h1>
            <p class="mt-2 mb-3 text-gray-600">View the condition records and inspection history of this equipment.</p>

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

                        <div class="cl-field">
                            <label for="startDate">Start date</label>
                            <input id="startDate" type="date">
                        </div>
                        <div class="cl-field">
                            <label for="endDate">End date</label>
                            <input id="endDate" type="date">
                        </div>

                        <div class="cl-field">
                            <label for="logFilter">Condition</label>
                            <select id="logFilter">
                                <option value="">All conditions</option>
                                <option>Good</option>
                                <option>Fair</option>
                                <option>Poor</option>
                            </select>
                        </div>

                        <div class="cl-field">
                            <label for="statusFilter">Status</label>
                            <select id="statusFilter">
                                <option value="">All statuses</option>
                                <?php foreach ($statusOptions as $opt): ?>
                                    <?php if (is_array($opt)): ?>
                                        <optgroup label="<?= ev($opt['group']) ?>">
                                            <?php foreach ($opt['options'] as $o): ?>
                                                <option value="<?= ev($o) ?>"><?= ev($o) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php else: ?>
                                        <option value="<?= ev($opt) ?>"><?= ev($opt) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="button" id="clearFilters" class="cl-clear">Clear Filters</button>
                    </div>

                    <p class="cl-count" id="resultCount" aria-live="polite"></p>

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
                                    <tr data-condition="<?= ev($log['condition']) ?>"
                                        data-status="<?= ev($log['status']) ?>"
                                        data-date="<?= ev($log['date']) ?>"
                                        data-date-label="<?= ev(date('M d, Y', strtotime($log['date']))) ?>"
                                        data-remarks="<?= ev($log['remarks']) ?>"
                                        data-search="<?= ev(strtolower($log['status'] . ' ' . $log['remarks'])) ?>">
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
                                        <td class="cl-remarks-cell">
                                            <div class="cl-remarks"><?= ev($log['remarks']) ?></div>
                                            <button type="button" class="cl-more" hidden>View full text</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="cl-empty" id="noResults" style="display:none"><td colspan="5">No records match your filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <dialog class="cl-modal" id="remarksModal" aria-labelledby="remarksModalTitle">
        <div class="cl-modal-head">
            <h4 id="remarksModalTitle">Remarks</h4>
            <button type="button" class="cl-modal-close" id="remarksClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cl-modal-body">
            <div class="cl-modal-meta" id="remarksMeta"></div>
            <p class="cl-modal-text" id="remarksText"></p>
        </div>
    </dialog>
    
    <div id="toast-warning" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-exclamation text-lg"></i>
        </div>
        <div id="toast-warning-message" class="ml-3 flex-1 text-sm font-medium">Please complete the required fields.</div>
        <button type="button" data-dismiss-toast="warning" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="toast-success" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div id="toast-success-message" class="ml-3 flex-1 text-sm font-medium">Facility assessment saved successfully!</div>
        <button type="button" data-dismiss-toast="success" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="toast-danger" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-xmark text-lg"></i>
        </div>
        <div id="toast-danger-message" class="ml-3 flex-1 text-sm font-medium">Unable to save the assessment.</div>
        <button type="button" data-dismiss-toast="danger" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('#logTable tbody tr[data-condition]'));
            const search = document.getElementById('logSearch');
            const filter = document.getElementById('logFilter');
            const statusFilter = document.getElementById('statusFilter');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const clearBtn = document.getElementById('clearFilters');
            const count = document.getElementById('resultCount');
            const none = document.getElementById('noResults');

            const modal = document.getElementById('remarksModal');
            const modalMeta = document.getElementById('remarksMeta');
            const modalText = document.getElementById('remarksText');

            function updateOverflow() {
                rows.forEach(r => {
                    if (r.style.display === 'none') return;
                    const text = r.querySelector('.cl-remarks');
                    const btn = r.querySelector('.cl-more');
                    const clamped = text.scrollHeight > text.clientHeight + 1;
                    const longText = r.dataset.remarks.length > 100;
                    btn.hidden = !(clamped || longText);
                });
            }

            function apply() {
                const q = search.value.trim().toLowerCase();
                const f = filter.value;
                const s = statusFilter.value;
                const from = startDate.value;
                const to = endDate.value;

                endDate.min = from || '';
                startDate.max = to || '';

                let shown = 0;
                rows.forEach(r => {
                    const d = r.dataset.date;
                    const ok =
                        (!f || r.dataset.condition === f) &&
                        (!s || r.dataset.status === s) &&
                        (!from || d >= from) &&
                        (!to || d <= to) &&
                        (!q || r.dataset.search.includes(q));
                    r.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                none.style.display = shown ? 'none' : '';
                count.textContent = 'Showing ' + shown + ' of ' + rows.length + ' records';
                updateOverflow();
            }

            [search, startDate, endDate].forEach(el => el.addEventListener('input', apply));
            [filter, statusFilter].forEach(el => el.addEventListener('change', apply));

            clearBtn.addEventListener('click', function () {
                search.value = '';
                filter.value = '';
                statusFilter.value = '';
                startDate.value = '';
                endDate.value = '';
                apply();
            });

            window.addEventListener('resize', updateOverflow);
            window.addEventListener('load', updateOverflow);

            document.querySelector('#logTable tbody').addEventListener('click', function (e) {
                const btn = e.target.closest('.cl-more');
                if (!btn) return;
                const r = btn.closest('tr');
                modalMeta.textContent = '';
                const meta = document.createElement('span');
                meta.textContent = r.dataset.dateLabel + ' \u2022 ' + r.dataset.condition + ' \u2022 ' + r.dataset.status;
                modalMeta.appendChild(meta);
                modalText.textContent = r.dataset.remarks;
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    modal.setAttribute('open', '');
                }
            });

            document.getElementById('remarksClose').addEventListener('click', () => modal.close());
            modal.addEventListener('click', function (e) {
                if (e.target === modal) modal.close();
            });

            document.getElementById('exportBtn').addEventListener('click', function () {
                const lines = [['Date', 'Condition', 'Status', 'Change', 'Remarks']];
                rows.filter(r => r.style.display !== 'none').forEach(r => {
                    const td = r.querySelectorAll('td');
                    lines.push([
                        td[0].innerText.trim(),
                        td[1].innerText.trim(),
                        td[2].innerText.trim(),
                        td[3].innerText.replace(/\s+/g, ' ').trim(),
                        r.dataset.remarks
                    ]);
                });
                const csv = lines.map(l => l.map(v => '"' + v.replace(/"/g, '""') + '"').join(',')).join('\r\n');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
                a.download = 'condition_history_<?= (int) $equipment['id'] ?>.csv';
                a.click();
                URL.revokeObjectURL(a.href);
            });

            apply();
        })();
    </script>

</body>

</html>