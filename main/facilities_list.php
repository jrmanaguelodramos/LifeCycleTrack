<?php

declare(strict_types=1);

session_start();

if (empty($_SESSION['access_token'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$search = trim((string)($_GET['search'] ?? ''));
$condition = strtolower(trim((string)($_GET['condition'] ?? 'all')));

$allowedConditions = ['all', 'good', 'fair', 'poor'];

if (!in_array($condition, $allowedConditions, true)) {
    $condition = 'all';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 7;
$offset = ($page - 1) * $perPage;

$facilities = [];
$totalFacilities = 0;
$totalPages = 1;
$error = '';
$success = trim((string)($_GET['success'] ?? ''));

try {
    $where = ["asset_type = 'facility'"];
    $params = [];

    if ($search !== '') {
        $where[] = "(name ILIKE :search OR CAST(id AS TEXT) ILIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if ($condition !== 'all') {
        $where[] = 'LOWER(condition) = :condition';
        $params['condition'] = $condition;
    }

    $whereSql = implode(' AND ', $where);

    // Total matching records.
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM assets WHERE $whereSql"
    );

    $countStmt->execute($params);
    $totalFacilities = (int)$countStmt->fetchColumn();

    $totalPages = max(
        1,
        (int)ceil($totalFacilities / $perPage)
    );

    // If the requested page no longer exists, show the last page.
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    // Get current page.
    $sql = "
        SELECT id, name, location, condition
        FROM assets
        WHERE $whereSql
        ORDER BY id ASC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    error_log('Facilities list error: ' . $exception->getMessage());
    $error = 'Unable to load facilities. Please check your database connection and table columns.';
}

$modal = __DIR__ . '/addfacilitymodal.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/facilities_list.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold ">
            Facilities List
        </h1>
        <p class="mt-2 text-black-100 mb-8">
            View and manage all registered facilities and their current status
        </p>

        <?php if ($success !== ''): ?>
            <div class="mb-4 rounded-md bg-green-100 text-green-800 p-3">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-4 rounded-md bg-red-100 text-red-800 p-3">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        <form method="GET"
            class="flex flex-wrap items-end gap-4 mb-5">

            <div class="w-full max-w-sm">
                <label for="search" class="block text-xs font-medium text-gray-700 mb-1">
                    Search
                </label>

                <div class="relative flex items-center">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 text-slate-600"></i>

                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Search by name or ID..."
                        class="w-full bg-white text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 focus:outline-none focus:border-[#155B92]">
                </div>
            </div>

            <div>
                <label for="condition" class="block text-xs font-medium text-gray-700 mb-1">
                    Condition
                </label>

                <select
                    id="condition"
                    name="condition"
                    class="w-32 h-10 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92]">
                    <option value="all" <?= $condition === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="good" <?= $condition === 'good' ? 'selected' : '' ?>>Good</option>
                    <option value="fair" <?= $condition === 'fair' ? 'selected' : '' ?>>Fair</option>
                    <option value="poor" <?= $condition === 'poor' ? 'selected' : '' ?>>Poor</option>
                </select>
            </div>

            <button type="submit"
                class="h-10 px-4 rounded-md bg-[#155B92] text-white hover:bg-[#124b79]">
                <i class="fa-solid fa-filter mr-1"></i>
                Apply
            </button>

            <a href="facilities_list.php"
                class="h-10 px-4 inline-flex items-center rounded-md border border-gray-300 bg-white hover:bg-gray-50">
                Reset
            </a>

            <button
                type="button"
                id="openFacilityModal"
                class="h-10 px-4 rounded-[10px] bg-[#15588F] text-white hover:bg-[#124b79]">
                <i class="fa-solid fa-circle-plus mr-2"></i>
                Add Facility
            </button>

        </form>

        <div class="facility-table-container">

            <table class="facility-table">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-id-card"></i> ID</th>
                        <th><i class="fa-solid fa-tag"></i> Name</th>
                        <th><i class="fa-solid fa-location-dot"></i> Location</th>
                        <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                        <th><i class="fa-solid fa-gear"></i> Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!$facilities): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500">
                                No facilities found.
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($facilities as $facility): ?>

                            <?php
                            $status = strtolower((string)($facility['condition'] ?? ''));
                            $statusClass = in_array(
                                $status,
                                ['good', 'fair', 'poor'],
                                true
                            ) ? $status : 'unknown';
                            ?>

                            <tr>
                                <td><?= e($facility['id']) ?></td>

                                <td><?= e($facility['name']) ?></td>

                                <td><?= e($facility['location'] ?? '—') ?></td>

                                <td>
                                    <span class="condition <?= e($statusClass) ?>">
                                        <span></span>
                                        <?= e(ucfirst($status ?: 'Unknown')) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        class="view-button"
                                        href="viewfacility.php?id=<?= urlencode((string)$facility['id']) ?>">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- edit nalang tong pagination -->
        <div class="pagination">

            <button class="pagination-btn">
                <i class="fa-solid fa-chevron-left"></i>
                Previous
            </button>

            <button class="pagination-number active">1</button>
            <button class="pagination-number">2</button>
            <button class="pagination-number">3</button>

            <button class="pagination-btn">
                Next
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <?php
        $modal = __DIR__ . '/addfacilitymodal.php';

        if (file_exists($modal)) {
            include $modal;
        } else {
            echo "File NOT found: " . $modal;
        } ?>
    </main>
</body>

</html>