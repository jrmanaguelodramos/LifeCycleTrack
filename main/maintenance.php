<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Maintenance Report | LifeCycleTrack</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        color: #172b4d;
        font-family: Inter, system-ui, Arial, sans-serif;
    }

    .report-main {
        margin-left: 250px;
        width: calc(100% - 250px);
        min-height: 100vh;
        padding: 28px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    .eyebrow {
        color: #2563eb;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 12px;
        font-weight: 800;
        margin: 0 0 7px;
    }

    h1 {
        font-size: 30px;
        font-weight: 800;
        margin: 0;
    }

    h2 {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
    }

    .subtitle {
        color: #718096;
        font-size: 14px;
        margin: 7px 0 0;
    }

    .btn {
        background: #173f68;
        color: #fff;
        padding: 11px 16px;
        border: 0;
        border-radius: 11px;
        font-weight: 700;
        cursor: pointer;
    }

    .metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .metric,
    .panel,
    .filters {
        background: #fff;
        border: 1px solid #e3ebf4;
        border-radius: 17px;
        box-shadow: 0 5px 18px #1831530a;
    }

    .metric {
        padding: 19px;
    }

    .metric-label {
        font-size: 13px;
        color: #718096;
        font-weight: 650;
    }

    .metric-value {
        font-size: 30px;
        font-weight: 800;
        margin-top: 10px;
    }

    .note,
    .desc {
        font-size: 12px;
        color: #8a98aa;
        margin-top: 5px;
    }

    .filters {
        padding: 15px;
        display: flex;
        gap: 12px;
        align-items: end;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #52647c;
        margin-bottom: 6px;
    }

    select,
    input {
        border: 1px solid #d8e2ee;
        border-radius: 10px;
        background: white;
        padding: 10px 12px;
        min-height: 40px;
        color: #24364d;
    }

    .charts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 20px;
    }

    .panel {
        padding: 20px;
        min-width: 0;
    }

    .chartbox {
        height: 270px;
        position: relative;
        margin-top: 16px;
    }

    .wide {
        grid-column: 1 / -1;
    }

    .table-panel {
        padding: 0;
        overflow: hidden;
    }

    .table-head {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .scroll {
        overflow-x: auto;
    }

    table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }

    th,
    td {
        padding: 13px 15px;
        border-bottom: 1px solid #edf1f6;
    }

    th {
        background: #f7f9fc;
        color: #64748b;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    td:first-child {
        font-weight: 750;
        color: #24518a;
    }

    tbody tr:hover {
        background: #f9fbfe;
    }

    .pill {
        display: inline-block;
        border-radius: 99px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 800;
        background: #e2e8f0;
        color: #475569;
        white-space: nowrap;
    }

    .good,
    .completed,
    .restored,
    .inservice {
        background: #dcfce7;
        color: #166534;
    }

    .fair,
    .pending,
    .maintenance {
        background: #fef3c7;
        color: #92400e;
    }

    .poor,
    .overdue,
    .ongoing,
    .critical,
    .replacement {
        background: #ffe4e6;
        color: #be123c;
    }

    .scheduled,
    .low {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .high {
        background: #ffedd5;
        color: #c2410c;
    }

    .empty {
        text-align: center;
        padding: 25px;
        color: #94a3b8;
    }

    @media (max-width: 1050px) {
        .report-main {
            margin-left: 230px;
            width: calc(100% - 230px);
            padding: 20px;
        }

        .metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .report-main {
            margin: 0;
            width: 100%;
            padding: 15px;
        }

        .charts {
            grid-template-columns: 1fr;
        }

        .wide {
            grid-column: auto;
        }

        h1 {
            font-size: 25px;
        }

        .chartbox {
            height: 240px;
        }
    }

    @media print {
        .report-main {
            margin: 0;
            width: 100%;
            padding: 10px;
        }

        .no-print,
        .filters {
            display: none !important;
        }
    }
</style>
</head>

<body><?php include_once __DIR__ . '/sidebar.php'; ?><main class="report-main">
        <div class="header">
            <div>
                <p class="eyebrow">Reports / Maintenance</p>
                <h1>Maintenance Report</h1>
                <p class="subtitle">Review schedules, completion, overdue tasks, and workload.</p>
            </div><button class="btn no-print" onclick="window.print()">Print Report</button>
        </div>
        <div class="filters no-print">
            <div><label>Status</label><select id="status">
                    <option value="">All statuses</option>
                    <option>Scheduled</option>
                    <option>Completed</option>
                    <option>Overdue</option>
                    <option>Pending</option>
                </select></div>
            <div><label>Type</label><select id="type">
                    <option value="">All types</option>
                    <option>Preventive</option>
                    <option>Corrective</option>
                    <option>Inspection</option>
                </select></div><span class="note">Static demo data — no database or Supabase connection.</span>
        </div>
        <section class="metrics">
            <div class="metric">
                <div class="metric-label">Total tasks</div>
                <div class="metric-value" id="total">8</div>
                <div class="note">Sample records</div>
            </div>
            <div class="metric">
                <div class="metric-label">Scheduled</div>
                <div class="metric-value" id="scheduled">3</div>
                <div class="note">Planned maintenance</div>
            </div>
            <div class="metric">
                <div class="metric-label">Completed</div>
                <div class="metric-value" id="completed">2</div>
                <div class="note">Finished tasks</div>
            </div>
            <div class="metric">
                <div class="metric-label">Overdue</div>
                <div class="metric-value" id="overdue">2</div>
                <div class="note">Require follow-up</div>
            </div>
        </section>
        <section class="charts">
            <article class="panel">
                <h2>Maintenance Status</h2>
                <p class="desc">Sample tasks by status</p>
                <div class="chartbox"><canvas id="statusChart"></canvas></div>
            </article>
            <article class="panel">
                <h2>Maintenance Type</h2>
                <p class="desc">Preventive, corrective, and inspection</p>
                <div class="chartbox"><canvas id="typeChart"></canvas></div>
            </article>
            <article class="panel wide">
                <h2>Monthly Maintenance Activity</h2>
                <p class="desc">Illustrative workload over six months</p>
                <div class="chartbox"><canvas id="monthly"></canvas></div>
            </article>
        </section>
        <section class="panel table-panel">
            <div class="table-head">
                <div>
                    <h2>Maintenance Records</h2>
                    <p class="desc">Example maintenance schedule</p>
                </div><input id="search" class="no-print" placeholder="Search records...">
            </div>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Asset</th>
                            <th>Type</th>
                            <th>Scheduled date</th>
                            <th>Technician</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </section>
        <script>
            const data = [
                ['MNT-001', 'Air Conditioning Unit', 'Preventive', '2026-10-12', 'Maintenance Team', 'Scheduled'],
                ['MNT-002', 'Generator Set', 'Corrective', '2026-10-05', 'Facilities Team', 'Overdue'],
                ['MNT-003', 'Office Computer', 'Preventive', '2026-10-03', 'IT Support', 'Completed'],
                ['MNT-004', 'Water Pump', 'Corrective', '2026-10-18', 'Facilities Team', 'Scheduled'],
                ['MNT-005', 'Elevator System', 'Inspection', '2026-10-07', 'Service Contractor', 'Completed'],
                ['MNT-006', 'Network Switch', 'Preventive', '2026-10-22', 'IT Support', 'Pending'],
                ['MNT-007', 'Fire Alarm System', 'Inspection', '2026-10-15', 'Safety Team', 'Scheduled'],
                ['MNT-008', 'Backup Power Unit', 'Corrective', '2026-10-01', 'Facilities Team', 'Overdue']
            ];
            Chart.defaults.color = '#64748b';
            const sc = new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Scheduled', 'Completed', 'Overdue', 'Pending'],
                    datasets: [{
                        data: [3, 2, 2, 1],
                        backgroundColor: ['#3b82f6', '#10b981', '#f43f5e', '#f59e0b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            const tc = new Chart(document.getElementById('typeChart'), {
                type: 'pie',
                data: {
                    labels: ['Preventive', 'Corrective', 'Inspection'],
                    datasets: [{
                        data: [3, 3, 2],
                        backgroundColor: ['#6366f1', '#f59e0b', '#06b6d4'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            new Chart(document.getElementById('monthly'), {
                type: 'bar',
                data: {
                    labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        data: [5, 8, 6, 10, 7, 8],
                        backgroundColor: '#3b82f6',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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

            function render() {
                let q = document.getElementById('search').value.toLowerCase(),
                    s = document.getElementById('status').value,
                    t = document.getElementById('type').value,
                    r = data.filter(a => (!s || a[5] === s) && (!t || a[2] === t) && a.join(' ').toLowerCase().includes(q));
                document.getElementById('rows').innerHTML = r.map(a => `<tr><td>${a[0]}</td><td>${a[1]}</td><td>${a[2]}</td><td>${a[3]}</td><td>${a[4]}</td><td><span class="pill ${a[5].toLowerCase()}">${a[5]}</span></td></tr>`).join('') || '<tr><td colspan="6" class="empty">No sample records match.</td></tr>';
                document.getElementById('total').textContent = r.length;
                ['Scheduled', 'Completed', 'Overdue'].forEach(v => document.getElementById(v.toLowerCase()).textContent = r.filter(a => a[5] === v).length);
                sc.data.datasets[0].data = ['Scheduled', 'Completed', 'Overdue', 'Pending'].map(v => r.filter(a => a[5] === v).length);
                sc.update();
                tc.data.datasets[0].data = ['Preventive', 'Corrective', 'Inspection'].map(v => r.filter(a => a[2] === v).length);
                tc.update()
            } ['search', 'status', 'type'].forEach(id => document.getElementById(id).addEventListener(id === 'search' ? 'input' : 'change', render));
            render();
        </script>
    </main>
</body>

</html>