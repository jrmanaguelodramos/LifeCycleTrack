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
    <title>Trend Analysis | LifeCycleTrack</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            color: #172b4d;
            font-family: Inter, system-ui, Arial, sans-serif
        }

        .report-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 28px
        }

        .header {

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px
        }


        .eyebrow {

            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: .12em;
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 7px
        }


        h1 {

            font-size: 30px;
            font-weight: 800;
            margin: 0
        }


        h2 {

            font-size: 17px;
            font-weight: 800;
            margin: 0
        }


        .subtitle {
            color: #718096;
            font-size: 14px;
            margin: 7px 0 0
        }


        .btn {

            background: #173f68;
            color: #fff;
            padding: 11px 16px;
            border: 0;
            border-radius: 11px;
            font-weight: 700;
            cursor: pointer
        }


        .metrics {

            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px
        }


        .metric,
        .panel,
        .filters {

            background: #fff;
            border: 1px solid #e3ebf4;
            border-radius: 17px;
            box-shadow: 0 5px 18px #1831530a
        }

        .metric {
            padding: 19px
        }

        .metric-label {
            font-size: 13px;
            color: #718096;
            font-weight: 650
        }

        .metric-value {
            font-size: 30px;
            font-weight: 800;
            margin-top: 10px
        }

        .note,
        .desc {
            font-size: 12px;
            color: #8a98aa;
            margin-top: 5px
        }

        .filters {
            padding: 15px;
            display: flex;
            gap: 12px;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 20px
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #52647c;
            margin-bottom: 6px
        }

        select,
        input {
            border: 1px solid #d8e2ee;
            border-radius: 10px;
            background: white;
            padding: 10px 12px;
            min-height: 40px;
            color: #24364d
        }

        .charts {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 20px
        }

        .panel {
            padding: 20px;
            min-width: 0
        }

        .chartbox {
            height: 270px;
            position: relative;
            margin-top: 16px
        }

        .wide {
            grid-column: 1/-1
        }

        .table-panel {
            padding: 0;
            overflow: hidden
        }

        .table-head {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap
        }

        .scroll {
            overflow-x: auto
        }

        table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left
        }

        th,
        td {
            padding: 13px 15px;
            border-bottom: 1px solid #edf1f6
        }

        th {
            background: #f7f9fc;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em
        }

        td:first-child {
            font-weight: 750;
            color: #24518a
        }

        tbody tr:hover {
            background: #f9fbfe
        }

        .pill {
            display: inline-block;
            border-radius: 99px;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 800;
            background: #e2e8f0;
            color: #475569;
            white-space: nowrap
        }

        .good,
        .completed,
        .restored,
        .inservice {
            background: #dcfce7;
            color: #166534
        }

        .fair,
        .pending,
        .maintenance {
            background: #fef3c7;
            color: #92400e
        }

        .poor,
        .overdue,
        .ongoing,
        .critical,
        .replacement {
            background: #ffe4e6;
            color: #be123c
        }

        .scheduled,
        .low {
            background: #dbeafe;
            color: #1d4ed8
        }

        .high {
            background: #ffedd5;
            color: #c2410c
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #94a3b8
        }

        @media(max-width:1050px) {
            .report-main {
                margin-left: 230px;
                width: calc(100% - 230px);
                padding: 20px
            }

            .metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:760px) {
            .report-main {
                margin: 0;
                width: 100%;
                padding: 15px
            }

            .charts {
                grid-template-columns: 1fr
            }

            .wide {
                grid-column: auto
            }

            h1 {
                font-size: 25px
            }

            .chartbox {
                height: 240px
            }
        }

        @media print {
            .report-main {
                margin: 0;
                width: 100%;
                padding: 10px
            }

            .no-print,
            .filters {
                display: none !important
            }
        }
    </style>
</head>

<body>
    <?php
        include('sidebar.php');
    ?>
    
    <main class="report-main">
        <div class="header">
            <div>
                <p class="eyebrow">Reports / Analytics</p>
                <h1>Trend Analysis</h1>
                <p class="subtitle">Asset growth, category mix, and condition distribution.</p>
            </div><button class="btn no-print" onclick="window.print()">Print Report</button>
        </div>
        <div class="filters no-print">
            <div><label>Category</label><select id="cat">
                    <option value="">All categories</option>
                    <option>Equipment</option>
                    <option>Facility</option>
                </select></div>
            <div><label>Acquisition period</label><select id="period">
                    <option value="all">All sample periods</option>
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                </select></div><span class="note">Static demo data — no database or Supabase connection.</span>
        </div>
        <section class="metrics">
            <div class="metric">
                <div class="metric-label">Assets in report</div>
                <div class="metric-value" id="total">12</div>
                <div class="note">Sample records</div>
            </div>
            <div class="metric">
                <div class="metric-label">Equipment</div>
                <div class="metric-value" id="eq">8</div>
                <div class="note">Equipment assets</div>
            </div>
            <div class="metric">
                <div class="metric-label">Facilities</div>
                <div class="metric-value" id="fac">4</div>
                <div class="note">Facility assets</div>
            </div>
            <div class="metric">
                <div class="metric-label">Poor condition</div>
                <div class="metric-value" id="poor">2</div>
                <div class="note">Need attention</div>
            </div>
        </section>
        <section class="charts">
            <article class="panel">
                <h2>Monthly Acquisitions</h2>
                <p class="desc">Sample acquisitions by month</p>
                <div class="chartbox"><canvas id="acq"></canvas></div>
            </article>
            <article class="panel">
                <h2>Category Distribution</h2>
                <p class="desc">Equipment versus facilities</p>
                <div class="chartbox"><canvas id="catChart"></canvas></div>
            </article>
            <article class="panel wide">
                <h2>Asset Condition</h2>
                <p class="desc">Condition breakdown for the sample assets</p>
                <div class="chartbox"><canvas id="cond"></canvas></div>
            </article>
        </section>
        <section class="panel table-panel">
            <div class="table-head">
                <div>
                    <h2>Asset Records</h2>
                    <p class="desc">Example records used in the charts</p>
                </div><input id="search" class="no-print" placeholder="Search assets...">
            </div>
            <div class="scroll">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Condition</th>
                            <th>Acquired</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </section>
        <script>
            const data = [
                ['AST-001', 'Desktop Computer', 'Equipment', 'Admin Office', 'Good', '2026-05-12'],
                ['AST-002', 'Generator Set', 'Equipment', 'Utility Room', 'Poor', '2026-05-20'],
                ['AST-003', 'Administration Building', 'Facility', 'Main Campus', 'Good', '2026-06-03'],
                ['AST-004', 'Air Conditioning Unit', 'Equipment', 'Room 101', 'Fair', '2026-06-15'],
                ['AST-005', 'Water Pump', 'Equipment', 'Pump Room', 'Fair', '2026-07-08'],
                ['AST-006', 'Storage Facility', 'Facility', 'North Wing', 'Good', '2026-07-19'],
                ['AST-007', 'Network Switch', 'Equipment', 'Server Room', 'Good', '2026-08-02'],
                ['AST-008', 'Elevator System', 'Equipment', 'Main Building', 'Fair', '2026-08-24'],
                ['AST-009', 'Training Hall', 'Facility', 'East Campus', 'Good', '2026-09-05'],
                ['AST-010', 'Office Printer', 'Equipment', 'Admin Office', 'Poor', '2026-09-18'],
                ['AST-011', 'Fire Alarm System', 'Equipment', 'All Floors', 'Good', '2026-10-01'],
                ['AST-012', 'Research Building', 'Facility', 'West Campus', 'Good', '2026-10-04']
            ];
            Chart.defaults.color = '#64748b';
            const acq = new Chart(document.getElementById('acq'), {
                type: 'line',
                data: {
                    labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        data: [2, 2, 2, 2, 2, 2],
                        borderColor: '#2563eb',
                        backgroundColor: '#2563eb20',
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
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            const cc = new Chart(document.getElementById('catChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Equipment', 'Facility'],
                    datasets: [{
                        data: [8, 4],
                        backgroundColor: ['#3b82f6', '#10b981'],
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
            const co = new Chart(document.getElementById('cond'), {
                type: 'bar',
                data: {
                    labels: ['Good', 'Fair', 'Poor'],
                    datasets: [{
                        data: [7, 3, 2],
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

            function render() {
                let q = document.getElementById('search').value.toLowerCase(),
                    c = document.getElementById('cat').value,
                    p = document.getElementById('period').value,
                    cut = new Date();
                if (p !== 'all') cut.setMonth(cut.getMonth() - Number(p));
                let r = data.filter(a => (!c || a[2] === c) && (p === 'all' || new Date(a[5]) >= cut) && a.join(' ').toLowerCase().includes(q));
                document.getElementById('rows').innerHTML = r.map(a => `<tr><td>${a[0]}</td><td>${a[1]}</td><td>${a[2]}</td><td>${a[3]}</td><td><span class="pill ${a[4].toLowerCase()}">${a[4]}</span></td><td>${a[5]}</td></tr>`).join('') || '<tr><td colspan="6" class="empty">No sample records match.</td></tr>';
                document.getElementById('total').textContent = r.length;
                document.getElementById('eq').textContent = r.filter(a => a[2] === 'Equipment').length;
                document.getElementById('fac').textContent = r.filter(a => a[2] === 'Facility').length;
                document.getElementById('poor').textContent = r.filter(a => a[4] === 'Poor').length;
                cc.data.datasets[0].data = ['Equipment', 'Facility'].map(v => r.filter(a => a[2] === v).length);
                cc.update();
                co.data.datasets[0].data = ['Good', 'Fair', 'Poor'].map(v => r.filter(a => a[4] === v).length);
                co.update();
                acq.data.datasets[0].data = ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'].map((_, i) => r.filter(a => new Date(a[5] + 'T00:00:00').getMonth() === i + 4).length);
                acq.update()
            } ['search', 'cat', 'period'].forEach(id => document.getElementById(id).addEventListener(id === 'search' ? 'input' : 'change', render));
            render();
        </script>
    </main>
</body>

</html>