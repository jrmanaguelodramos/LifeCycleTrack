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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <title>Trend Analysis</title>
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/trend.css">
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

        <section class="panel insights">
            <h2>Insights &amp; Recommendations</h2>
            <p class="desc">Generated automatically from the currently filtered records.
                Risk rule: Poor condition ≥ 25% = High, 10–24% = Moderate, below 10% = Low.</p>
            <div class="insight-grid" id="insights"></div>
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

            function renderInsights(r, m) {
                const box = document.getElementById('insights');
                const n = r.length;
                if (n < 2) {
                    box.innerHTML = '<div class="empty">Not enough records in this filter to generate insights.</div>';
                    return;
                }
                const months = ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'];
                const pct = (a, b) => b ? Math.round(a / b * 100) : 0;
                const sum = arr => arr.reduce((x, y) => x + y, 0);
                const cards = [];

                // 1. Acquisition trend
                const early = sum(m.slice(0, 3)),
                    late = sum(m.slice(3));
                const peak = Math.max(...m);
                const peakMonths = months.filter((_, i) => m[i] === peak).join(', ');
                const trend = late > early ? 'Increasing' : late < early ? 'Decreasing' : 'Steady';
                cards.push({
                    type: trend === 'Decreasing' ? 'warn' : 'ok',
                    title: `Acquisition trend: ${trend}`,
                    basis: `${early} asset(s) were acquired in May–Jul versus ${late} in Aug–Oct. Peak activity: ${peakMonths} (${peak} per month).`,
                    action: trend === 'Steady' ?
                        'Procurement is consistent, so maintenance and budget planning can follow a fixed yearly cycle.' :
                        trend === 'Increasing' ?
                        'Asset volume is growing. Plan for a matching increase in maintenance staffing, storage, and budget.' :
                        'Fewer assets are being added. Check whether this is intentional or caused by procurement delays.'
                });

                // 2. Category mix
                const eq = r.filter(a => a[2] === 'Equipment').length,
                    fac = n - eq;
                const dom = eq >= fac ? 'Equipment' : 'Facility';
                const domN = Math.max(eq, fac);
                cards.push({
                    type: 'info',
                    title: `${dom} dominates the portfolio (${pct(domN, n)}%)`,
                    basis: `${eq} equipment (${pct(eq, n)}%) vs ${fac} facility (${pct(fac, n)}%) out of ${n} assets.`,
                    action: dom === 'Equipment' ?
                        'Equipment wears out faster than buildings, so prioritize preventive maintenance schedules and spare-parts planning.' :
                        'Facility-heavy portfolios need larger, long-term repair budgets and regular structural inspections.'
                });

                // 3. Condition risk (with category cross-check)
                const poor = r.filter(a => a[4] === 'Poor'),
                    fair = r.filter(a => a[4] === 'Fair');
                const poorPct = pct(poor.length, n),
                    attnPct = pct(poor.length + fair.length, n);
                const level = poorPct >= 25 ? 'High' : poorPct >= 10 ? 'Moderate' : 'Low';
                const rate = c => {
                    const g = r.filter(a => a[2] === c);
                    return g.length ? pct(g.filter(a => a[4] !== 'Good').length, g.length) : 0;
                };
                const eqRate = rate('Equipment'),
                    facRate = rate('Facility');
                const worse = eqRate === facRate ? null : (eqRate > facRate ? 'Equipment' : 'Facility');
                cards.push({
                    type: level === 'High' ? 'risk' : level === 'Moderate' ? 'warn' : 'ok',
                    title: `Condition risk: ${level}`,
                    basis: `${poor.length} Poor (${poorPct}%) and ${fair.length} Fair; ${attnPct}% of assets are not in Good condition. ` +
                        `Non-good rate: Equipment ${eqRate}% vs Facility ${facRate}%` +
                        (worse ? `, so ${worse} is the weaker category.` : '.'),
                    action: level === 'Low' ?
                        'Overall condition is healthy. Keep routine inspections going.' :
                        `Allocate maintenance budget first to ${worse || 'both categories'}, and schedule inspections for Fair assets before they degrade to Poor.`
                });

                // 4. Priority list
                cards.push({
                    type: poor.length ? 'risk' : 'ok',
                    title: poor.length ? 'Priority assets for action' : 'No critical assets',
                    basis: poor.length ?
                        poor.map(a => `${a[1]} (${a[0]}, ${a[3]})`).join('; ') + '.' :
                        'No asset is currently rated Poor in this filter.',
                    action: poor.length ?
                        'Inspect these first and decide between repair or replacement. Poor-rated equipment can cause downtime and safety risks.' :
                        'Focus on keeping Fair assets from declining.'
                });

                box.innerHTML = cards.map(c => `
                    <article class="insight ${c.type}">
                        <h3>${c.title}</h3>
                        <p><span class="tag">Basis</span>${c.basis}</p>
                        <p><span class="tag">Recommendation</span>${c.action}</p>
                    </article>`).join('');
            }

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
                acq.update();
                renderInsights(r, acq.data.datasets[0].data);
            }

            ['search', 'cat', 'period'].forEach(id => document.getElementById(id).addEventListener(id === 'search' ? 'input' : 'change', render));
            render();
        </script>
    </main>
</body>

</html>