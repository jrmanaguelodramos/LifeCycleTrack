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

$facilities = facilities_list(
    $pdo,
    $search,
    $filterCondition
);
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
                        class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow"
                        placeholder="Search by name or id..." />

                </div>
            </div>

            <select
                id="floorFilter"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all">All Floors</option>
                <option value="ground floor">Ground Floor</option>
                <option value="1st floor">1st Floor</option>
                <option value="2nd floor">2nd Floor</option>
                <option value="3rd floor">3rd Floor</option>
                <option value="4th floor">4th Floor</option>

            </select>

            <div class="equipment-table-container">

                <select
                    id="conditionFilter"
                    class="w-28 h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                    <option value="all">All Conditions</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>

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

        <div class="pagination">

            <button type="button" class="pagination-btn">
                <i class="fa-solid fa-chevron-left"></i>
                Previous
            </button>

            <button type="button" class="pagination-number active">
                1
            </button>

            <button type="button" class="pagination-btn">
                Next
                <i class="fa-solid fa-chevron-right"></i>
            </button>

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

            const ROWS_PER_PAGE = 10;

            const searchInput = document.getElementById('facilitySearch');
            const conditionFilter = document.getElementById('conditionFilter');
            const floorFilter = document.getElementById('floorFilter');
            const clearFilters = document.getElementById('clearFilters');

            const tbody = document.querySelector('.facility-table tbody');
            const pagination = document.querySelector('.pagination');

            const [prevBtn, nextBtn] =
                pagination.querySelectorAll('.pagination-btn');

            const allRows = Array.from(
                tbody.querySelectorAll('tr')
            );

            let filteredRows = allRows.slice();
            let currentPage = 1;

            const emptyRow = document.createElement('tr');

            emptyRow.innerHTML =
                '<td colspan="5" style="text-align:center;padding:1rem;">No facilities found.</td>';

            emptyRow.style.display = 'none';

            tbody.appendChild(emptyRow);

            function applyFilters() {

                const query = searchInput.value.trim().toLowerCase();
                const condition = conditionFilter.value.toLowerCase();
                const floor = floorFilter.value.toLowerCase();

                filteredRows = allRows.filter(function(row) {

                    const id = row.cells[0].textContent.trim().toLowerCase();
                    const name = row.cells[1].textContent.trim().toLowerCase();

                    const rowFloor = (
                        row.dataset.floor || row.cells[2].textContent
                    ).trim().toLowerCase();

                    const cond = row.querySelector('.condition');

                    return (
                        (id.includes(query) || name.includes(query)) &&
                        (
                            condition === 'all' ||
                            (cond && cond.classList.contains(condition))
                        ) &&
                        (
                            floor === 'all' ||
                            rowFloor === floor
                        )
                    );
                });

                currentPage = 1;

                render();
            }

            function render() {

                const totalPages = Math.max(
                    1,
                    Math.ceil(filteredRows.length / ROWS_PER_PAGE)
                );

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                const start = (currentPage - 1) * ROWS_PER_PAGE;

                allRows.forEach(function(row) {
                    row.style.display = 'none';
                });

                filteredRows
                    .slice(start, start + ROWS_PER_PAGE)
                    .forEach(function(row) {
                        row.style.display = '';
                    });

                emptyRow.style.display =
                    filteredRows.length === 0 ? '' : 'none';

                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {

                pagination
                    .querySelectorAll('.pagination-number')
                    .forEach(function(button) {
                        button.remove();
                    });

                for (let i = 1; i <= totalPages; i++) {

                    const button = document.createElement('button');

                    button.type = 'button';

                    button.className =
                        'pagination-number' +
                        (i === currentPage ? ' active' : '');

                    button.textContent = i;

                    button.addEventListener('click', function() {
                        currentPage = i;
                        render();
                    });

                    pagination.insertBefore(button, nextBtn);
                }

                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = currentPage === totalPages;

                prevBtn.style.opacity =
                    prevBtn.disabled ? '0.5' : '';

                nextBtn.style.opacity =
                    nextBtn.disabled ? '0.5' : '';

                prevBtn.style.cursor =
                    prevBtn.disabled ? 'not-allowed' : '';

                nextBtn.style.cursor =
                    nextBtn.disabled ? 'not-allowed' : '';
            }

            prevBtn.addEventListener('click', function() {

                if (currentPage > 1) {
                    currentPage--;
                    render();
                }

            });

            nextBtn.addEventListener('click', function() {

                const totalPages = Math.max(
                    1,
                    Math.ceil(filteredRows.length / ROWS_PER_PAGE)
                );

                if (currentPage < totalPages) {
                    currentPage++;
                    render();
                }

            });

            searchInput.addEventListener('input', applyFilters);
            conditionFilter.addEventListener('change', applyFilters);
            floorFilter.addEventListener('change', applyFilters);

            clearFilters.addEventListener('click', function() {

                searchInput.value = '';
                floorFilter.value = 'all';
                conditionFilter.value = 'all';

                applyFilters();

            });

            render();

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
