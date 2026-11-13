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

const EQUIPMENT_CONDITION_PAGE = 'equip_condition.php';
const FACILITY_CONDITION_PAGE  = 'facility_condition.php';

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
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

$dashboardError = '';
$totalFacilities = 0;
$totalEquipments = 0;
$conditionAlerts = 0;

$facilitiesCounts = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];
$equipmentsCounts = ['Good' => 0, 'Fair' => 0, 'Poor' => 0];

$facilitiesScore = 0;
$equipmentsScore = 0;
$recentActivities = [];
$poorEquipment = [];
$poorFacilities = [];

try {

    $facilityStats = $pdo->query(
        "SELECT COUNT(*) AS total,
                COUNT(*) FILTER (WHERE LOWER(condition) = 'good') AS good,
                COUNT(*) FILTER (WHERE LOWER(condition) = 'fair') AS fair,
                COUNT(*) FILTER (WHERE LOWER(condition) = 'poor') AS poor
         FROM public.facilities"
    )->fetch(PDO::FETCH_ASSOC);
    $equipmentStats = $pdo->query(
        "SELECT COUNT(*) AS total,
                COUNT(*) FILTER (WHERE LOWER(condition) = 'good') AS good,
                COUNT(*) FILTER (WHERE LOWER(condition) = 'fair') AS fair,
                COUNT(*) FILTER (WHERE LOWER(condition) = 'poor') AS poor
         FROM public.equipment"
    )->fetch(PDO::FETCH_ASSOC);

    $totalFacilities = (int) $facilityStats['total'];
    $totalEquipments = (int) $equipmentStats['total'];
    $facilitiesCounts = ['Good' => (int)$facilityStats['good'], 'Fair' => (int)$facilityStats['fair'], 'Poor' => (int)$facilityStats['poor']];
    $equipmentsCounts = ['Good' => (int)$equipmentStats['good'], 'Fair' => (int)$equipmentStats['fair'], 'Poor' => (int)$equipmentStats['poor']];
    $conditionAlerts = $facilitiesCounts['Poor'] + $equipmentsCounts['Poor'];

    $facilitiesScore = calculateScore($facilitiesCounts);
    $equipmentsScore = calculateScore($equipmentsCounts);

    $poorEquipment = $pdo->query(
        "SELECT id, name, location
         FROM equipment
         WHERE LOWER(condition) = 'poor'
         ORDER BY name"
    )->fetchAll(PDO::FETCH_ASSOC);

    $poorFacilities = $pdo->query(
        "SELECT id, name, location
         FROM facilities
         WHERE LOWER(condition) = 'poor'
         ORDER BY name"
    )->fetchAll(PDO::FETCH_ASSOC);

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

function alertGroup(string $title, string $icon, array $items, string $page): void
{
    ?>
    <div style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <h3 style="font-size:15px;font-weight:700;color:#103F6C;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid <?= e($icon) ?>"></i><?= e($title) ?>
            </h3>
            <span style="font-size:12px;font-weight:600;padding:2px 10px;border-radius:9999px;background:#ffe5e5;color:#b91c1c;">
                <?= count($items) ?>
            </span>
        </div>

        <?php if (empty($items)): ?>
            <p style="font-size:14px;color:#6b7280;padding:10px 12px;border:1px dashed #d1d5db;border-radius:10px;">
                No poor-condition items.
            </p>
        <?php else: ?>
            <div data-alert-list style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($items as $item): ?>
                    <a href="<?= e($page . '?id=' . (int) $item['id']) ?>"
                       class="alert-item"
                       style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#111827;">
                        <span style="min-width:0;">
                            <span style="display:block;font-size:14px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= e($item['name']) ?>
                            </span>
                            <span style="display:block;font-size:12px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= e(($item['location'] ?? '') !== '' ? $item['location'] : 'No location') ?>
                            </span>
                        </span>
                        <span style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                            <span style="font-size:12px;font-weight:600;padding:2px 10px;border-radius:9999px;background:#ffe5e5;color:#b91c1c;">Poor</span>
                            <i class="fa-solid fa-chevron-right" style="font-size:11px;color:#9ca3af;"></i>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
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
    <style>
        .alert-item:hover {
            background: #f3f7fb;
            border-color: #155B92 !important;
        }
    </style>
</head>

<!--bg-[#F4F7FB]-->
<body class="bg-[url('../img/img2.png')] bg-cover bg-center bg-no-repeat">

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

                    <button type="button"
                            id="openAlertsModal"
                            aria-label="View poor-condition assets"
                            aria-haspopup="dialog"
                            class="w-9 h-9 shrink-0 rounded-full bg-[#155B92] flex items-center justify-center"
                            style="border:0;cursor:pointer;">
                        <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                    </button>
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
            <table class="custom-table w-full" style="table-layout:fixed;width:100%;">
                <colgroup>
                    <col style="width:18%;">
                    <col style="width:22%;">
                    <col style="width:18%;">
                    <col style="width:42%;">
                </colgroup>

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
                                    <div style="display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:20px;text-align:left;font-weight:400;">
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
                                                 style="flex-shrink:0;white-space:nowrap;"
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

    <div id="alertsModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="alertsModalTitle"
         style="display:none;position:fixed;top:0;right:0;bottom:0;left:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,.5);padding:16px;">

        <div style="display:flex;flex-direction:column;width:100%;max-width:560px;max-height:80vh;background:#fff;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,.25);">

            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:20px 24px 12px;border-bottom:1px solid #e5e7eb;">
                <div>
                    <h2 id="alertsModalTitle" style="font-size:20px;font-weight:700;color:#111827;">Condition Alerts</h2>
                    <p style="margin-top:2px;font-size:13px;color:#6b7280;">
                        <?= (int) $conditionAlerts ?> asset(s) in poor condition. Click an item to open its condition page.
                    </p>
                </div>
                <button type="button"
                        id="closeAlertsModal"
                        aria-label="Close"
                        style="border:0;background:transparent;cursor:pointer;font-size:26px;line-height:1;color:#6b7280;">
                    &times;
                </button>
            </div>

            <div style="overflow-y:auto;padding:20px 24px 4px;">
                <?php
                alertGroup('Equipment', 'fa-wrench', $poorEquipment, EQUIPMENT_CONDITION_PAGE);
                alertGroup('Facilities', 'fa-building', $poorFacilities, FACILITY_CONDITION_PAGE);
                ?>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('alertsModal');
            const openButton = document.getElementById('openAlertsModal');
            const closeButton = document.getElementById('closeAlertsModal');

            function openModal() {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                closeButton.focus();
            }

            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                openButton.focus();
            }

            const ALERTS_PER_PAGE = 5;

            document.querySelectorAll('[data-alert-list]').forEach(function(list) {
                const items = Array.from(list.children);
                if (items.length <= ALERTS_PER_PAGE) return;

                const totalPages = Math.ceil(items.length / ALERTS_PER_PAGE);
                const pager = document.createElement('div');
                pager.style.cssText = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin-top:10px;';
                list.parentNode.appendChild(pager);

                let page = 1;

                function pageButton(label, target, options) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.innerHTML = label;
                    button.disabled = !!options.disabled;
                    button.setAttribute('aria-label', options.aria || 'Page ' + label);
                    if (options.current) button.setAttribute('aria-current', 'page');

                    button.style.cssText =
                        'min-width:30px;height:30px;padding:0 8px;border-radius:8px;font-size:13px;font-weight:600;' +
                        'border:1px solid ' + (options.current ? '#155B92' : '#d1d5db') + ';' +
                        'background:' + (options.current ? '#155B92' : '#fff') + ';' +
                        'color:' + (options.current ? '#fff' : '#374151') + ';' +
                        'cursor:' + (options.disabled ? 'not-allowed' : 'pointer') + ';' +
                        'opacity:' + (options.disabled ? '0.5' : '1') + ';';

                    if (!options.disabled && !options.current) {
                        button.addEventListener('click', function() {
                            page = target;
                            render();
                        });
                    }

                    return button;
                }

                function visiblePages() {
                    const pages = [];
                    for (let i = 1; i <= totalPages; i++) {
                        if (i === 1 || i === totalPages || Math.abs(i - page) <= 1) {
                            pages.push(i);
                        } else if (pages[pages.length - 1] !== '...') {
                            pages.push('...');
                        }
                    }
                    return pages;
                }

                function render() {
                    const start = (page - 1) * ALERTS_PER_PAGE;
                    const end = Math.min(start + ALERTS_PER_PAGE, items.length);

                    items.forEach(function(item, index) {
                        item.style.display = index >= start && index < end ? 'flex' : 'none';
                    });

                    pager.replaceChildren();

                    const info = document.createElement('span');
                    info.style.cssText = 'font-size:12px;color:#6b7280;';
                    info.textContent = 'Showing ' + (start + 1) + '-' + end + ' of ' + items.length;
                    pager.appendChild(info);

                    const controls = document.createElement('div');
                    controls.style.cssText = 'display:flex;align-items:center;gap:4px;';

                    controls.appendChild(pageButton('<i class="fa-solid fa-chevron-left"></i>', page - 1, {
                        disabled: page === 1,
                        aria: 'Previous page'
                    }));

                    visiblePages().forEach(function(entry) {
                        if (entry === '...') {
                            const dots = document.createElement('span');
                            dots.textContent = '...';
                            dots.style.cssText = 'padding:0 4px;color:#9ca3af;font-size:13px;';
                            controls.appendChild(dots);
                        } else {
                            controls.appendChild(pageButton(String(entry), entry, {
                                current: entry === page
                            }));
                        }
                    });

                    controls.appendChild(pageButton('<i class="fa-solid fa-chevron-right"></i>', page + 1, {
                        disabled: page === totalPages,
                        aria: 'Next page'
                    }));

                    pager.appendChild(controls);
                }

                render();
            });

            openButton.addEventListener('click', openModal);
            closeButton.addEventListener('click', closeModal);

            modal.addEventListener('click', function(event) {
                if (event.target === modal) closeModal();
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.style.display === 'flex') closeModal();
            });
        })();
    </script>
</body>

</html>
