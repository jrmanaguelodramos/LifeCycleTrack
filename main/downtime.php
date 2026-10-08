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
    <title>Downtime Report | LifeCycleTrack</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
                <p class="eyebrow">Reports / Reliability</p>
                <h1>Downtime Report</h1>
                <p class="subtitle">Track interruptions, severity, and restoration status.</p>
            </div><button class="btn no-print" onclick="window.print()">Print Report</button>
        </div>
        <div class="filters no-print">
            <div><label>Status</label><select id="status">
                    <option value="">All statuses</option>
                    <option>Ongoing</option>
                    <option>Restored</option>
                </select></div>
            <div><label>Severity</label><select id="severity">
                    <option value="">All severities</option>
                    <option>Critical</option>
                    <option>High</option>
                    <option>Medium</option>
                    <option>Low</option>
                </select></div><span class="note">Static demo data — no database or Supabase connection.</span>
        </div>
        <section class="metrics">
            <div class="metric">
                <div class="metric-label">Total incidents</div>
                <div class="metric-value" id="total">7</div>
                <div class="note">Sample records</div>
            </div>
            <div class="metric">
                <div class="metric-label">Downtime hours</div>
                <div class="metric-value" id="hours">21.8</div>
                <div class="note">Sample incident hours</div>
            </div>
            <div class="metric">
                <div class="metric-label">Ongoing</div>
                <div class="metric-value" id="ongoing">3</div>
                <div class="note">Not yet restored</div>
            </div>
            <div class="metric">
                <div class="metric-label">Restored</div>
                <div class="metric-value" id="restored">4</div>
                <div class="note">Marked restored</div>
            </div>
        </section>
        <section class="charts">
            <article class="panel">
                <h2>Downtime by Month</h2>
                <p class="desc">Illustrative hours over six months</p>
                <div class="chartbox"><canvas id="trend"></canvas></div>
            </article>
            <article class="panel">
                <h2>Incident Severity</h2>
                <p class="desc">Incidents grouped by severity</p>
                <div class="chartbox"><canvas id="severityChart"></canvas></div>
            </article>
            <article class="panel wide">
                <h2>Incident Status</h2>
                <p class="desc">Ongoing compared with restored</p>
                <div class="chartbox"><canvas id="statusChart"></canvas></div>
            </article>
        </section>
        <section class="panel table-panel">
            <div class="table-head">
                <div>
                    <h2>Downtime Records</h2>
                    <p class="desc">Sample incident history</p>
                </div><input id="search" class="no-print" placeholder="Search incidents...">
            </div>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Incident</th>
                            <th>Asset</th>
                            <th>Start time</th>
                            <th>Hours</th>
                            <th>Cause</th>
                            <th>Severity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </section>
        <script>
            const data = [
                ['DT-001', 'Generator Set', '2026-10-08 08:30', 6.5, 'Power system failure', 'Critical', 'Ongoing'],
                ['DT-002', 'Air Conditioning Unit', '2026-10-06 10:00', 3.5, 'Refrigerant leak', 'Medium', 'Restored'],
                ['DT-003', 'Office Computer', '2026-10-04 09:00', 2, 'Hardware fault', 'Low', 'Restored'],
                ['DT-004', 'Water Pump', '2026-10-07 14:00', 4, 'Motor overheating', 'High', 'Ongoing'],
                ['DT-005', 'Network Switch', '2026-10-02 08:00', 1.25, 'Network fault', 'Medium', 'Restored'],
                ['DT-006', 'Elevator System', '2026-10-01 15:00', 3, 'Door sensor failure', 'High', 'Restored'],
                ['DT-007', 'Fire Alarm System', '2026-10-09 07:00', 1.5, 'Control panel fault', 'Critical', 'Ongoing']
            ];
            Chart.defaults.color = '#64748b';
            new Chart(document.getElementById('trend'), {
                type: 'line',
                data: {
                    labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        data: [18, 12, 24, 16, 21, 21.75],
                        borderColor: '#f43f5e',
                        backgroundColor: '#f43f5e20',
                        fill: true,
                        tension: .35
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
                            beginAtZero: true
                        }
                    }
                }
            });
            const sev = new Chart(document.getElementById('severityChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low'],
                    datasets: [{
                        data: [2, 2, 2, 1],
                        backgroundColor: ['#e11d48', '#f97316', '#f59e0b', '#3b82f6'],
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
            const st = new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: ['Ongoing', 'Restored'],
                    datasets: [{
                        data: [3, 4],
                        backgroundColor: ['#f43f5e', '#10b981'],
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
                    v = document.getElementById('severity').value,
                    r = data.filter(a => (!s || a[6] === s) && (!v || a[5] === v) && a.join(' ').toLowerCase().includes(q));
                document.getElementById('rows').innerHTML = r.map(a => `<tr><td>${a[0]}</td><td>${a[1]}</td><td>${a[2]}</td><td>${a[3].toFixed(2)}</td><td>${a[4]}</td><td><span class="pill ${a[5].toLowerCase()}">${a[5]}</span></td><td><span class="pill ${a[6].toLowerCase()}">${a[6]}</span></td></tr>`).join('') || '<tr><td colspan="7" class="empty">No sample incidents match.</td></tr>';
                document.getElementById('total').textContent = r.length;
                document.getElementById('hours').textContent = r.reduce((n, a) => n + a[3], 0).toFixed(1);
                document.getElementById('ongoing').textContent = r.filter(a => a[6] === 'Ongoing').length;
                document.getElementById('restored').textContent = r.filter(a => a[6] === 'Restored').length;
                sev.data.datasets[0].data = ['Critical', 'High', 'Medium', 'Low'].map(v => r.filter(a => a[5] === v).length);
                sev.update();
                st.data.datasets[0].data = ['Ongoing', 'Restored'].map(v => r.filter(a => a[6] === v).length);
                st.update()
            } ['search', 'status', 'severity'].forEach(id => document.getElementById(id).addEventListener(id === 'search' ? 'input' : 'change', render));
            render();
        </script>
    </main>
</body>

</html>