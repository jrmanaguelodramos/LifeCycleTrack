<?php
require_once __DIR__ . '/_app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

if (!isset($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}

$facilityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$facilityId || $facilityId < 1) {
    header("Location: facilities_list.php");
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, name, condition, status, location, category, remarks, image_url
       FROM facilities
      WHERE id = :id'
);
$stmt->execute([':id' => $facilityId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    header("Location: facilities_list.php");
    exit;
}

$description = trim((string) ($row['remarks'] ?? ''));

if ($description === '') {
    $parts = array_filter([
        trim((string) ($row['category'] ?? '')),
        trim((string) ($row['location'] ?? '')),
    ]);
    $description = $parts ? implode(' • ', $parts) : 'No description available.';
}

$facility = [
    'id'          => (int) $row['id'],
    'name'        => $row['name'],
    'condition'   => $row['condition'],
    'status'      => $row['status'],
    'description' => $description,
    'image_url'   => $row['image_url'],
];

$stmt = $pdo->prepare(
    "SELECT
        a.id,
        to_char(a.assessed_at AT TIME ZONE 'Asia/Manila', 'YYYY-MM-DD') AS date,
        a.condition,
        a.status,
        a.notes AS remarks
       FROM facility_condition_assessments a
      WHERE a.facility_id = :id
      ORDER BY a.assessed_at DESC, a.id DESC"
);
$stmt->execute([':id' => $facilityId]);

$logs = array_map(static function (array $r): array {
    return [
        'date'      => (string) $r['date'],
        'condition' => (string) $r['condition'],
        'status'    => (string) ($r['status'] ?? ''),
        'remarks'   => (string) ($r['remarks'] ?? ''),
    ];
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Condition History</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition_logs.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
            <nav aria-label="Breadcrumb" class="mb-2">
                <ol class="flex items-center gap-2 text-sm text-slate-500 [&_a:hover]:text-blue-900 [&_a:hover]:underline [&_i.sep]:text-[10px] [&_i.sep]:text-slate-300">
                    <li><a href="facilities_list.php" class="block max-w-[200px] no-underline hover:underline mr-2 truncate"> <i class="fa-regular fa-building mr-2"></i>Facilities List</a></li>
                    <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right mr-2"></i></li>
                   <li><a href="viewfacility.php?id=<?= (int)$facility['id'] ?>" class="block max-w-[200px] no-underline hover:underline mr-2 truncate">Information</a></li>
                    <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right mr-2"></i></li>
                    <li aria-current="page" class="font-medium text-blue-900 ">Condition Logs</li>
                </ol>
            </nav>

        <div class="cl-page">
            <h1 class="text-3xl font-bold">Facility Condition History</h1>
            <p class="mt-2 mb-3 text-gray-600">View the condition records and inspection history of this facility.</p>

            <div class="cl-wrap">
                <div class="cl-head">
                    <div class="cl-who">
                        <div class="cl-img"><img src="<?= ev($facility['image_url'] ?: '../img/logo.png') ?>" alt="<?= ev($facility['name']) ?>"></div>
                        <div>
                            <h2><?= ev($facility['name']) ?></h2>
                            <p class="cl-id"><?= 'FA-' . str_pad((string) $facility['id'], 3, '0', STR_PAD_LEFT) ?></p>
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
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
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
                                    <tr data-condition="<?= ev($log['condition']) ?>"
                                        data-status="<?= ev($log['status']) ?>"
                                        data-date="<?= ev($log['date']) ?>"
                                        data-date-label="<?= ev(date('M d, Y', strtotime($log['date']))) ?>"
                                        data-remarks="<?= ev($log['remarks']) ?>"
                                        data-search="<?= ev(strtolower($log['status'] . ' ' . $log['remarks'])) ?>">
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
            const exportBtn = document.getElementById('exportBtn');

            const modal = document.getElementById('remarksModal');
            const modalMeta = document.getElementById('remarksMeta');
            const modalText = document.getElementById('remarksText');

            function updateOverflow() {
                rows.forEach(row => {
                    if (row.style.display === 'none') return;
                    const text = row.querySelector('.cl-remarks');
                    const btn = row.querySelector('.cl-more');
                    const clamped = text.scrollHeight > text.clientHeight + 1;
                    const longText = row.dataset.remarks.length > 100;
                    btn.hidden = !(clamped || longText);
                });
            }

            function applyFilters() {
                const query = search.value.trim().toLowerCase();
                const condition = filter.value;
                const status = statusFilter.value;
                const from = startDate.value;
                const to = endDate.value;

                endDate.min = from || '';
                startDate.max = to || '';

                let shown = 0;

                rows.forEach(row => {
                    const d = row.dataset.date;
                    const visible =
                        (!condition || row.dataset.condition === condition) &&
                        (!status || row.dataset.status === status) &&
                        (!from || d >= from) &&
                        (!to || d <= to) &&
                        (!query || row.dataset.search.includes(query));

                    row.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });

                none.style.display = shown ? 'none' : '';
                count.textContent = 'Showing ' + shown + ' of ' + rows.length + ' records';
                updateOverflow();
            }

            [search, startDate, endDate].forEach(el => el.addEventListener('input', applyFilters));
            [filter, statusFilter].forEach(el => el.addEventListener('change', applyFilters));

            clearBtn.addEventListener('click', function () {
                search.value = '';
                filter.value = '';
                statusFilter.value = '';
                startDate.value = '';
                endDate.value = '';
                applyFilters();
            });

            window.addEventListener('resize', updateOverflow);
            window.addEventListener('load', updateOverflow);

            document.querySelector('#logTable tbody').addEventListener('click', function (e) {
                const btn = e.target.closest('.cl-more');
                if (!btn) return;
                const row = btn.closest('tr');
                modalMeta.textContent = '';
                const meta = document.createElement('span');
                meta.textContent = row.dataset.dateLabel + ' \u2022 ' + row.dataset.condition + ' \u2022 ' + row.dataset.status;
                modalMeta.appendChild(meta);
                modalText.textContent = row.dataset.remarks;
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

            exportBtn.addEventListener('click', function () {
                const lines = [['Date', 'Condition', 'Status', 'Change', 'Remarks']];

                rows.filter(row => row.style.display !== 'none').forEach(row => {
                    const cells = row.querySelectorAll('td');
                    lines.push([
                        row.dataset.dateLabel,
                        row.dataset.condition,
                        row.dataset.status,
                        cells[3].innerText.replace(/\s+/g, ' ').trim(),
                        row.dataset.remarks
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

            applyFilters();
        })();
    </script>
</body>
</html>