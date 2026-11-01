<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['access_token'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

const EQUIPMENT_VIEW_PAGE = 'viewequip.php';
const FACILITY_VIEW_PAGE  = 'viewfacility.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getCount(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetchColumn();
}

function calculateScore(array $counts): int
{
    $total = array_sum($counts);

    if ($total === 0) {
        return 0;
    }

    $score = (
        ($counts['Good'] * 100) +
        ($counts['Fair'] * 60) +
        ($counts['Poor'] * 20)
    ) / $total;

    return (int) round($score);
}

function getConditionCounts(PDO $pdo, string $table): array
{
    $allowedTables = ['facilities', 'equipment'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid table.');
    }

    $counts = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];

    $stmt = $pdo->query(
        "SELECT
            CASE
                WHEN LOWER(condition) = 'good' THEN 'Good'
                WHEN LOWER(condition) = 'fair' THEN 'Fair'
                WHEN LOWER(condition) = 'poor' THEN 'Poor'
                ELSE NULL
            END AS condition_name,
            COUNT(*) AS total
         FROM {$table}
         GROUP BY condition_name"
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['condition_name'] !== null) {
            $counts[$row['condition_name']] = (int) $row['total'];
        }
    }

    return $counts;
}

$dashboardError = '';
$totalFacilities = 0;
$totalEquipments = 0;
$conditionAlerts = 0;

$facilitiesCounts = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];
$equipmentsCounts = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];

$facilitiesScore = 0;
$equipmentsScore = 0;
$recentActivities = [];

try {

    $totalFacilities = getCount(
        $pdo,
        "SELECT COUNT(*) FROM facilities"
    );

    $totalEquipments = getCount(
        $pdo,
        "SELECT COUNT(*) FROM equipment"
    );

    $conditionAlerts = getCount(
        $pdo,
        "SELECT
            (SELECT COUNT(*) FROM facilities WHERE LOWER(condition) = 'poor')
          + (SELECT COUNT(*) FROM equipment  WHERE LOWER(condition) = 'poor')"
    );

    $facilitiesCounts = getConditionCounts($pdo, 'facilities');
    $equipmentsCounts = getConditionCounts($pdo, 'equipment');

    $facilitiesScore = calculateScore($facilitiesCounts);
    $equipmentsScore = calculateScore($equipmentsCounts);

    $stmt = $pdo->query(
        "SELECT
            al.created_at,
            al.action,
            al.details,
            al.facility_id,
            al.equipment_id,
            COALESCE(f.name, e.name) AS asset_name,
            CASE WHEN al.equipment_id IS NOT NULL THEN 'equipment' ELSE 'facility' END AS asset_type
         FROM activity_log AS al
         LEFT JOIN facilities AS f ON f.id = al.facility_id
         LEFT JOIN equipment AS e ON e.id = al.equipment_id
         ORDER BY al.created_at DESC
         LIMIT 5"
    );

    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {
    error_log('Dashboard database error: ' . $exception->getMessage());
    $dashboardError = 'Unable to load dashboard data. Check the database tables and connection.';
}


function activityLink(array $activity): ?string
{
    if (empty($activity['asset_name'])) {
        return null;
    }

    if (!empty($activity['equipment_id'])) {
        return EQUIPMENT_VIEW_PAGE . '?id=' . (int) $activity['equipment_id'];
    }

    if (!empty($activity['facility_id'])) {
        return FACILITY_VIEW_PAGE . '?id=' . (int) $activity['facility_id'];
    }

    return null;
}

function formatAction(?string $action): string
{
    $action = trim((string) $action);
    return $action === '' ? 'Activity' : ucwords(str_replace(['_', '-'], ' ', $action));
}

function parseDetails(?string $details): array
{
    $badgeColors = [
        'good' => ['#e7f8ed', '#1e7d3f'],
        'fair' => ['#fff1e0', '#b45309'],
        'poor' => ['#ffe5e5', '#b91c1c'],
    ];
    $default = ['#eef2f6', '#374151'];

    $items = [];
    foreach (explode(';', (string) $details) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        $label = '';
        $value = $part;
        if (strpos($part, ':') !== false) {
            [$label, $value] = array_map('trim', explode(':', $part, 2));
        }

        $colors = $default;
        if (strcasecmp($label, 'Condition') === 0) {
            $colors = $badgeColors[strtolower($value)] ?? $default;
        }

        $items[] = ['label' => $label, 'value' => $value, 'bg' => $colors[0], 'fg' => $colors[1]];
    }

    return $items;
}

function lifecycleChart(int $score, array $counts): void
{
    $colors = [
        'Good' => '#2fbd5b',
        'Fair' => '#ff7900',
        'Poor' => '#ff0000',
    ];

    $total = array_sum($counts);
    $radius = 44;
    $circumference = 2 * M_PI * $radius;
    $gap = 3;
    $offset = 0;
    ?>

    <div class="flex items-center gap-6 mt-3"
         style="display:flex;align-items:center;gap:24px;margin-top:12px;">

        <div class="relative w-28 h-28 shrink-0"
             style="position:relative;width:112px;height:112px;flex-shrink:0;">

            <svg viewBox="0 0 112 112"
                 style="width:100%;height:100%;transform:rotate(-90deg);"
                 role="img"
                 aria-label="Score <?= $score ?> out of 100">

                <circle cx="56" cy="56" r="<?= $radius ?>"
                        fill="none" stroke="#EEF2F6" stroke-width="12"/>

                <?php if ($total > 0): ?>
                    <?php foreach ($counts as $name => $count): ?>
                        <?php
                        if ($count <= 0) {
                            continue;
                        }

                        $segment = ($count / $total) * $circumference;
                        $dash = max(0, $segment - $gap);
                        ?>

                        <circle cx="56" cy="56" r="<?= $radius ?>"
                                fill="none"
                                stroke="<?= $colors[$name] ?>"
                                stroke-width="12"
                                stroke-linecap="round"
                                stroke-dasharray="<?= round($dash, 2) ?> <?= round($circumference - $dash, 2) ?>"
                                stroke-dashoffset="<?= round(-$offset, 2) ?>"/>

                        <?php $offset += $segment; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            </svg>

            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <span class="text-3xl font-black text-gray-900 leading-none">
                    <?= $score ?>
                </span>
                <span class="text-xs text-gray-500 mt-1">out of 100</span>
            </div>
        </div>

        <div style="flex:1;display:flex;flex-direction:column;gap:10px;">

            <?php foreach ($counts as $name => $count): ?>
                <?php
                $percentage = $total > 0
                    ? round(($count / $total) * 100)
                    : 0;
                ?>

                <div style="display:grid;grid-template-columns:70px 1fr 28px;align-items:center;gap:10px;font-size:14px;">

                    <span style="display:flex;align-items:center;gap:7px;white-space:nowrap;color:#4b5563;">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?= $colors[$name] ?>;"></span>
                        <?= e($name) ?>
                    </span>

                    <span style="display:block;height:8px;border-radius:9999px;background:#EEF2F6;overflow:hidden;">
                        <span style="display:block;height:100%;width:<?= $percentage ?>%;border-radius:9999px;background:<?= $colors[$name] ?>;"></span>
                    </span>

                    <span style="text-align:right;font-weight:700;">
                        <?= $count ?>
                    </span>

                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <?php
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../src/output.css">
</head>

<!--bg-[#F4F7FB]-->
<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content p-8">

        <h1 class="text-3xl font-bold">Dashboard</h1>

        <p class="mt-2 text-gray-600 mb-8">
            Overview of facilities, equipment and current condition status
        </p>

        <?php if ($dashboardError !== ''): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <?= e($dashboardError) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-3 gap-4 mb-5 w-full">

            <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-5 h-full"
                 style="border-left:4px solid rgb(14,58,99);">

                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-bold text-gray-900">Total Facilities</h2>

                    <a href="facilities_list.php"
                       aria-label="View facilities"
                       class="w-9 h-9 shrink-0 rounded-full bg-[#155B92] flex items-center justify-center">
                        <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                    </a>
                </div>

                <p class="text-5xl xl:text-6xl font-bold text-black mt-3">
                    <?= $totalFacilities ?>
                </p>
            </div>

            <div class="min-w-0 bg-white rounded-2xl shadow-md border border-gray-200 p-5 h-full"
                 style="border-left:4px solid rgb(14,58,99);">

                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-bold text-gray-900">Total Equipments</h2>

                    <a href="equipments_list.php"
                       aria-label="View equipment"
                       class="w-9 h-9 shrink-0 rounded-full bg-[#155B92] flex items-center justify-center">
                        <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                    </a>
                </div>

                <p class="text-5xl xl:text-6xl font-bold text-black mt-3">
                    <?= $totalEquipments ?>
                </p>
            </div>

            <div class="min-w-0 bg-[#FFD2D2] rounded-2xl shadow-md border border-red-200 p-5 h-full"
                 style="border-left:4px solid rgb(239,68,68);">

                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-bold text-gray-900">Condition Alerts</h2>

                    <a href="facility_condition.php"
                       aria-label="View condition assessments"
                       class="w-9 h-9 shrink-0 rounded-full bg-[#155B92] flex items-center justify-center">
                        <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                    </a>
                </div>

                <p class="text-5xl xl:text-6xl font-bold text-red-600 mt-3">
                    <?= $conditionAlerts ?>
                </p>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-7">

            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5"
                 style="border-left:4px solid rgb(14,58,99);">

                <h2 class="text-base font-bold text-gray-900">
                    Facilities LifeCycle Score
                </h2>

                <?php lifecycleChart($facilitiesScore, $facilitiesCounts); ?>
            </div>

            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5"
                 style="border-left:4px solid rgb(14,58,99);">

                <h2 class="text-base font-bold text-gray-900">
                    Equipments LifeCycle Score
                </h2>

                <?php lifecycleChart($equipmentsScore, $equipmentsCounts); ?>
            </div>

        </div>

        <h2 class="text-xl font-bold mb-3 mt-3">Recent Activity</h2>

        <div class="custom-table-container overflow-x-auto">
            <table class="custom-table w-full">

                <thead>
                    <tr>
                        <th><i class="fa-solid fa-calendar-minus"></i> Date</th>
                        <th><i class="fa-solid fa-tag"></i> Name</th>
                        <th><i class="fa-solid fa-gauge-high"></i> Category</th>
                        <th><i class="fa-solid fa-gear"></i> Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($recentActivities)): ?>
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500">
                                No activity yet.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($recentActivities as $activity): ?>
                            <?php $link = activityLink($activity); ?>
                            <tr>
                                <td>
                                    <?php
                                    $timestamp = strtotime((string)$activity['created_at']);
                                    echo e($timestamp !== false
                                        ? date('m-d-Y H:i', $timestamp)
                                        : $activity['created_at']);
                                    ?>
                                </td>

                                <td>
                                    <?= e($activity['asset_name'] ?? 'Deleted asset') ?>
                                </td>

                                <td>
                                    <?= e(ucfirst((string)($activity['asset_type'] ?? ''))) ?>
                                </td>

                                <td>
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;text-align:left;font-weight:400;">
                                        <div>
                                            <div style="font-weight:600;"><?= e(formatAction($activity['action'] ?? '')) ?></div>

                                            <?php $detailItems = parseDetails($activity['details'] ?? ''); ?>
                                            <?php if ($detailItems): ?>
                                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                                                    <?php foreach ($detailItems as $item): ?>
                                                        <span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:12px;font-weight:500;background:<?= e($item['bg']) ?>;color:<?= e($item['fg']) ?>;">
                                                            <?php if ($item['label'] !== ''): ?>
                                                                <span style="opacity:.75;"><?= e($item['label']) ?>:</span>
                                                            <?php endif; ?>
                                                            <?= e($item['value']) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($link !== null): ?>
                                            <a href="<?= e($link) ?>"
                                               title="View <?= e($activity['asset_name']) ?>"
                                               aria-label="View <?= e($activity['asset_name']) ?>"
                                                 class="border rounded-2xl p-2 bg-[#15588F] text-white hover:bg-[#10456f] transition">
                                                <i class="fa-solid fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>
    </main>
</body>
</html>s