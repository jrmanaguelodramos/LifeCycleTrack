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
    <title>Lifecycle Report | LifeCycleTrack</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #f3f6fb;
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
                <p class="eyebrow">Reports / Asset Lifecycle</p>
                <h1>Lifecycle Report</h1>
                <p class="subtitle">Review asset age, condition, and lifecycle stage.</p>
            </div><button class="btn no-print" onclick="window.print()">Print Report</button>
        </div>
        <div class="filters no-print">
            <div><label>Stage</label><select id="stage">
                    <option value="">All stages</option>
                    <option>In Service</option>
                    <option>Maintenance</option>
                    <option>Replacement Review</option>
                    <option>Retired</option>
                </select></div>
            <div><label>Category</label><select id="category">
                    <option value="">All categories</option>
                    <option>Equipment</option>
                    <option>Facility</option>
                </select></div>
            <div><label>Condition</label><select id="condition">
                    <option value="">All conditions</option>
                    <option>Good</option>
                    <option>Fair</option>
                    <option>Poor</option>
                </select></div><span class="note">Static demo data — no database or Supabase connection.</span>
        </div>
        <section class="metrics">
            <div class="metric">
                <div class="metric-label">Total assets</div>
                <div class="metric-value" id="total">10</div>
                <div class="note">Sample asset records</div>
            </div>
            <div class="metric">
                <div class="metric-label">In service</div>
                <div class="metric-value" id="inservice">4</div>
                <div class="note">Currently active</div>
            </div>
            <div class="metric">
                <div class="metric-label">Replacement review</div>
                <div class="metric-value" id="replacement">2</div>
                <div class="note">Review for replacement</div>
            </div>
            <div class="metric">
                <div class="metric-label">Retired</div>
                <div class="metric-value" id="retired">2</div>
                <div class="note">No longer in service</div>
            </div>
        </section>
        <section class="charts">
            <article class="panel">
                <h2>Lifecycle Stage Distribution</h2>
                <p class="desc">Assets grouped by lifecycle stage</p>
                <div class="chartbox"><canvas id="stageChart"></canvas></div>
            </article>
            <article class="panel">
                <h2>Condition Distribution</h2>
                <p class="desc">Condition breakdown of sample assets</p>
                <div class="chartbox"><canvas id="conditionChart"></canvas></div>
            </article>
            <article class="panel wide">
                <h2>Asset Age Profile</h2>
                <p class="desc">Assets grouped by years since acquisition</p>
                <div class="chartbox"><canvas id="ageChart"></canvas></div>
            </article>
        </section>
        <section class="panel table-panel">
            <div class="table-head">
                <div>
                    <h2>Lifecycle Asset Register</h2>
                    <p class="desc">Sample lifecycle records</p>
                </div><input id="search" class="no-print" placeholder="Search assets...">
            </div>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Asset ID</th>
                            <th>Asset name</th>
                            <th>Category</th>
                            <th>Date acquired</th>
                            <th>Useful life</th>
                            <th>Stage</th>
                            <th>Condition</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </section>
        <script>
            const data = [
                ['AST-001', 'Desktop Computer', 'Equipment', '2022-03-15', 5, 'In Service', 'Good'],
                ['AST-002', 'Generator Set', 'Equipment', '2017-06-20', 8, 'Replacement Review', 'Poor'],
                ['AST-003', 'Administration Building', 'Facility', '2015-01-10', 30, 'In Service', 'Good'],
                ['AST-004', 'Air Conditioning Unit', 'Equipment', '2020-09-01', 7, 'Maintenance', 'Fair'],
                ['AST-005', 'Old Printer', 'Equipment', '2014-05-12', 6, 'Retired', 'Poor'],
                ['AST-006', 'Water Pump', 'Equipment', '2018-11-05', 8, 'Replacement Review', 'Fair'],
                ['AST-007', 'Storage Facility', 'Facility', '2021-02-18', 25, 'In Service', 'Good'],
                ['AST-008', 'Network Switch', 'Equipment', '2023-08-10', 6, 'In Service', 'Good'],
                ['AST-009', 'Elevator System', 'Equipment', '2019-04-22', 15, 'Maintenance', 'Fair'],
                ['AST-010', 'Legacy Workstation', 'Equipment', '2013-07-01', 5, 'Retired', 'Poor']
            ];
            Chart.defaults.color = '#64748b';
            const stageChart = new Chart(document.getElementById('stageChart'), {
                type: 'doughnut',
                data: {
                    labels: ['In Service', 'Maintenance', 'Replacement Review', 'Retired'],
                    datasets: [{
                        data: [4, 2, 2, 2],
                        backgroundColor: ['#10b981', '#f59e0b', '#f43f5e', '#94a3b8'],
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
            const conditionChart = new Chart(document.getElementById('conditionChart'), {
                type: 'bar',
                data: {
                    labels: ['Good', 'Fair', 'Poor'],
                    datasets: [{
                        data: [4, 3, 3],
                        backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
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
            const ageChart = new Chart(document.getElementById('ageChart'), {
                type: 'bar',
                data: {
                    labels: ['0–2 years', '3–5 years', '6–10 years', 'Over 10 years'],
                    datasets: [{
                        data: [1, 3, 3, 3],
                        backgroundColor: '#6366f1',
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
                    s = document.getElementById('stage').value,
                    c = document.getElementById('category').value,
                    v = document.getElementById('condition').value,
                    r = data.filter(a => (!s || a[5] === s) && (!c || a[2] === c) && (!v || a[6] === v) && a.join(' ').toLowerCase().includes(q));
                document.getElementById('rows').innerHTML = r.map(a => `<tr><td>${a[0]}</td><td>${a[1]}</td><td>${a[2]}</td><td>${a[3]}</td><td>${a[4]} years</td><td><span class="pill ${a[5]==='In Service'?'inservice':a[5]==='Replacement Review'?'replacement':a[5].toLowerCase()}">${a[5]}</span></td><td><span class="pill ${a[6].toLowerCase()}">${a[6]}</span></td></tr>`).join('') || '<tr><td colspan="7" class="empty">No sample assets match.</td></tr>';
                document.getElementById('total').textContent = r.length;
                document.getElementById('inservice').textContent = r.filter(a => a[5] === 'In Service').length;
                document.getElementById('replacement').textContent = r.filter(a => a[5] === 'Replacement Review').length;
                document.getElementById('retired').textContent = r.filter(a => a[5] === 'Retired').length;
                stageChart.data.datasets[0].data = ['In Service', 'Maintenance', 'Replacement Review', 'Retired'].map(v => r.filter(a => a[5] === v).length);
                stageChart.update();
                conditionChart.data.datasets[0].data = ['Good', 'Fair', 'Poor'].map(v => r.filter(a => a[6] === v).length);
                conditionChart.update();
                let bins = [0, 0, 0, 0],
                    now = new Date();
                r.forEach(a => {
                    let age = Math.floor((now - new Date(a[3] + 'T00:00:00')) / (365.25 * 86400000));
                    bins[age <= 2 ? 0 : age <= 5 ? 1 : age <= 10 ? 2 : 3]++
                });
                ageChart.data.datasets[0].data = bins;
                ageChart.update()
            } ['search', 'stage', 'category', 'condition'].forEach(id => document.getElementById(id).addEventListener(id === 'search' ? 'input' : 'change', render));
            render();
        </script>
    </main>
</body>

</html>