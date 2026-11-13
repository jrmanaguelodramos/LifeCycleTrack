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

$message = '';
$error = '';

$showFacilityAddedToast = !empty($_SESSION['facilityAddedToast']);
if ($showFacilityAddedToast) {
    unset($_SESSION['facilityAddedToast']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name = trim((string)($_POST['name'] ?? ''));
            $condition = (string)($_POST['condition'] ?? '');

            if ($name === '' || !in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
                throw new RuntimeException('Name and condition are required.');
            }

            save_facility($pdo, [
                'name' => $name,
                'condition' => $condition,
                'date_acquired' => $_POST['date_acquired'] ?? '',
                'remarks' => $_POST['remarks'] ?? '',
            ]);

            $message = 'Facility added successfully.';
        }
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$filterCondition = trim((string)($_GET['condition'] ?? ''));
$filterFloor = trim((string)($_GET['floor'] ?? ''));
$facilityPage = facilities_list_page($pdo, $search, $filterCondition, $filterFloor, (int)($_GET['page'] ?? 1));
$facilities = $facilityPage['rows'];
$facilityQuery = ['search' => $search, 'condition' => $filterCondition, 'floor' => $filterFloor];
$facilityPageUrl = static function (int $page) use ($facilityQuery): string {
    return '?' . http_build_query(array_filter($facilityQuery, static fn($value) => $value !== '') + ['page' => $page]);
};
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities List</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/facilities_list.css">

    <style>
        #facility-success-toast {
            transform: translateX(calc(100% + 2rem));
            opacity: 0;
            pointer-events: none;
        }

        #facility-success-toast.toast-visible {
            transform: translateX(0);
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">

        <h1 class="text-3xl font-bold">Facilities List</h1>

        <p class="mt-2 text-black-100 mb-8 text-gray-600">
            View and manage all registered facilities and their current status
        </p>

        <?php if ($message !== ''): ?>
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap items-end gap-4 mb-5 w-full">

            <div class="w-full max-w-sm min-w-50">
                <div class="relative flex items-center">

                    <i class="fa-solid fa-magnifying-glass absolute w-5 h-5 top-2.5 left-2.5 text-slate-600"></i>

                    <input
                        id="facilitySearch"
                        type="text"
                        value="<?= e($search) ?>"
                        class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow"
                        placeholder="Search by name or id..." />

                </div>
            </div>

            <select
                id="floorFilter"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all" <?= $filterFloor === '' ? 'selected' : '' ?>>All Floors</option>
                <option value="ground floor" <?= strtolower($filterFloor) === 'ground floor' ? 'selected' : '' ?>>Ground Floor</option>
                <option value="1st floor" <?= strtolower($filterFloor) === '1st floor' ? 'selected' : '' ?>>1st Floor</option>
                <option value="2nd floor" <?= strtolower($filterFloor) === '2nd floor' ? 'selected' : '' ?>>2nd Floor</option>
                <option value="3rd floor" <?= strtolower($filterFloor) === '3rd floor' ? 'selected' : '' ?>>3rd Floor</option>
                <option value="4th floor" <?= strtolower($filterFloor) === '4th floor' ? 'selected' : '' ?>>4th Floor</option>

            </select>

            <div class="equipment-table-container">

                <select
                    id="conditionFilter"
                    class="w-28 h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                    <option value="all" <?= $filterCondition === '' ? 'selected' : '' ?>>All Conditions</option>
                    <option value="good" <?= strtolower($filterCondition) === 'good' ? 'selected' : '' ?>>Good</option>
                    <option value="fair" <?= strtolower($filterCondition) === 'fair' ? 'selected' : '' ?>>Fair</option>
                    <option value="poor" <?= strtolower($filterCondition) === 'poor' ? 'selected' : '' ?>>Poor</option>

                </select>

            </div>

            <button type="button" id="clearFilters" class="clear-filters-btn">
                Clear Filters
            </button>

            <div class="ml-auto flex shrink-0">

                <a
                    href="addfacility.php"
                    class="inline-flex items-center border border-[#15588F] p-2 rounded-[10px] bg-[#15588F] pr-3 text-white cursor-pointer hover:bg-[#10456f] transition">

                    <i class="fa-solid fa-circle-plus mr-3 ml-2 text-white"></i>
                    Add Facility

                </a>

            </div>

        </div>

        <div class="facility-table-container">

            <table class="facility-table">

                <thead>
                    <tr>
                        <th><i class="fa-solid fa-id-card"></i> ID</th>
                        <th><i class="fa-solid fa-tag"></i> Name</th>
                        <th><i class="fa-solid fa-building"></i> Floor</th>
                        <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                        <th><i class="fa-solid fa-gear"></i> Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!$facilities): ?>

                        <tr>
                            <td colspan="5" class="text-center py-6">
                                No facilities found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($facilities as $facility): ?>

                            <tr data-floor="<?= e($facility['location'] ?? '') ?>">

                            <td>
                                <?= 'FA-' . str_pad(
                                    (string)$facility['id'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </td>

                                <td>
                                    <?= e($facility['name']) ?>
                                </td>

                                <td>
                                    <?= e($facility['location'] ?? '') ?>
                                </td>

                                <td>
                                    <?= condition_badge($facility['condition'] ?? '') ?>
                                </td>

                                <td>
                                    <a
                                        href="viewfacility.php?id=<?= (int)$facility['id'] ?>"
                                        class="border rounded-2xl p-2 bg-[#15588F] text-white hover:bg-[#10456f] transition">

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

        <div class="pagination" aria-label="Facility pages">
            <a class="pagination-btn" href="<?= e($facilityPageUrl(max(1, $facilityPage['page'] - 1))) ?>" <?= $facilityPage['page'] <= 1 ? 'aria-disabled="true" tabindex="-1" style="opacity:.5;pointer-events:none"' : '' ?>>Previous</a>
            <span>Page <?= (int)$facilityPage['page'] ?> of <?= (int)$facilityPage['total_pages'] ?> · <?= (int)$facilityPage['total'] ?> facilities</span>
            <a class="pagination-btn" href="<?= e($facilityPageUrl(min($facilityPage['total_pages'], $facilityPage['page'] + 1))) ?>" <?= $facilityPage['page'] >= $facilityPage['total_pages'] ? 'aria-disabled="true" tabindex="-1" style="opacity:.5;pointer-events:none"' : '' ?>>Next</a>
        </div>

    </main>

    <?php if ($showFacilityAddedToast): ?>

        <div
            id="facility-success-toast"
            class="fixed right-5 top-5 z-[100] flex w-full max-w-sm items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 shadow-lg transition-all duration-300 ease-out"
            role="status"
            aria-live="polite">

            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
                <i class="fa-solid fa-circle-check text-lg"></i>
            </div>

            <div class="ml-3 flex-1 text-sm font-medium">
                Facility added successfully!
            </div>

            <button
                type="button"
                id="close-facility-toast"
                class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                aria-label="Close success notification">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const searchInput = document.getElementById('facilitySearch');
            const conditionFilter = document.getElementById('conditionFilter');
            const floorFilter = document.getElementById('floorFilter');
            const clearFilters = document.getElementById('clearFilters');

            const submitFilters = function() {
                const params = new URLSearchParams();
                if (searchInput.value.trim()) params.set('search', searchInput.value.trim());
                if (conditionFilter.value !== 'all') params.set('condition', conditionFilter.value);
                if (floorFilter.value !== 'all') params.set('floor', floorFilter.value);
                window.location.search = params.toString();
            };
            let searchTimer;
            searchInput.addEventListener('input', function() { clearTimeout(searchTimer); searchTimer = setTimeout(submitFilters, 300); });
            conditionFilter.addEventListener('change', submitFilters);
            floorFilter.addEventListener('change', submitFilters);

            clearFilters.addEventListener('click', function() {

                searchInput.value = '';
                floorFilter.value = 'all';
                conditionFilter.value = 'all';

                submitFilters();

            });

            const successToast =
                document.getElementById('facility-success-toast');

            const closeToastButton =
                document.getElementById('close-facility-toast');

            if (successToast && closeToastButton) {

                requestAnimationFrame(function() {
                    successToast.classList.add('toast-visible');
                });

                const toastTimer = setTimeout(function() {
                    successToast.classList.remove('toast-visible');
                }, 3000);

                closeToastButton.addEventListener('click', function() {

                    clearTimeout(toastTimer);

                    successToast.classList.remove('toast-visible');

                });
            }

        });
    </script>

</body>

</html>
