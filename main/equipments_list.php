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

$showSuccessToast = !empty($_SESSION['equipmentAddedToast']);
unset($_SESSION['equipmentAddedToast']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment List</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/equipments_list.css">
</head>

<body>

<?php
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name = trim((string)($_POST['name'] ?? ''));
            $location = trim((string)($_POST['location'] ?? ''));
            $condition = (string)($_POST['condition'] ?? '');

            if (
                $name === '' ||
                $location === '' ||
                !in_array($condition, ['Good', 'Fair', 'Poor'], true)
            ) {
                throw new RuntimeException(
                    'Name, location and condition are required.'
                );
            }

            save_equipment($pdo, [
                'name' => $name,
                'brand' => $_POST['brand'] ?? '',
                'model' => $_POST['model'] ?? '',
                'category' => $_POST['category'] ?? 'equipment',
                'location' => $location,
                'date_acquired' => $_POST['date_acquired'] ?? '',
                'condition' => $condition,
                'details_name' => $_POST['details_name'] ?? '',
                'remarks' => $_POST['remarks'] ?? '',
            ]);

            $message = 'Equipment Name added successfully.';
        }

    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$filterCondition = trim((string)($_GET['condition'] ?? ''));

$equipmentRows = fetch_equipment(
    $pdo,
    $search,
    $filterCondition
);
?>

<?php include 'sidebar.php'; ?>

<main class="ml-64 p-8">

    <h1 class="text-3xl font-bold">Equipments List</h1>

    <p class="mt-2 text-black-100 mb-8 text-gray-600">
        View and manage all registered equipments and their current status
    </p>

    <?php if ($message !== ''): ?>
        <div class="mb-4 rounded-md bg-green-100 p-3 text-sm text-green-800">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="mb-4 rounded-md bg-red-100 p-3 text-sm text-red-700">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="equipment-toolbar">

        <div class="equipment-filters">

            <div class="equipment-search">
                <div class="relative flex items-center">

                    <i class="fa-solid fa-magnifying-glass absolute w-5 h-5 top-2.5 left-2.5 text-slate-600"></i>

                    <input
                        id="equipmentSearch"
                        type="search"
                        class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow"
                        placeholder="Search by name or id..." />

                </div>
            </div>

            <select
                id="locationFilter"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all">All Locations</option>
                <option value="main building">Main Building</option>
                <option value="building a">Building A</option>
                <option value="building b">Building B</option>

            </select>

            <!-- comment ko lang to
                <select
                    id="floorFilter"
                    class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                    <option value="all">All Floors</option>
                    <option value="ground floor">Ground Floor</option>
                    <option value="basement">Basement</option>
                    <option value="1st floor">1st Floor</option>
                    <option value="2nd floor">2nd Floor</option>
                    <option value="3rd floor">3rd Floor</option>
                    <option value="4th floor">4th Floor</option>
                </select>
            -->

            <select
                id="conditionFilter"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all">All Conditions</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>

            </select>

            <button type="button" id="clearFilters" class="clear-filters-btn">
                Clear Filters
            </button>

        </div>

        <a
            href="addequipment.php"
            class="add-equipment-button border-[#15588F] p-2 rounded-[10px] bg-[#15588F] pr-3 text-white cursor-pointer">

            <i class="fa-solid fa-circle-plus mr-3 ml-2 text-white"></i>
            Add Equipment

        </a>

    </div>

    <div class="equipment-table-container">

        <table class="equipment-table">

            <thead>
                <tr>
                    <th><i class="fa-solid fa-id-card"></i> ID</th>
                    <th><i class="fa-solid fa-tag"></i> Name</th>
                    <th><i class="fa-solid fa-location-dot"></i> Location</th>
                    <!-- <th><i class="fa-solid fa-building"></i> Floor</th> comment ko lang to-->
                    <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                    <th><i class="fa-solid fa-gear"></i> Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($equipmentRows)): ?>

                    <tr>
                        <td colspan="100%" class="text-center py-6">
                            No equipment found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($equipmentRows as $asset): ?>

                        <tr>

                            <td>
                                <?= 'EQ-' . str_pad(
                                    (string)$asset['id'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string)($asset['name'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string)($asset['location'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                       <!-- comment ko lang to
                            <td>
                                <?= htmlspecialchars(
                                    (string)($asset['floor'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>
                        -->

                            <td>
                                <span class="condition <?= strtolower((string)($asset['condition'] ?? 'unknown')) ?>">
                                    <span></span>

                                    <?= htmlspecialchars(
                                        (string)($asset['condition'] ?? 'Unknown'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>
                            </td>

                            <td>

                                <a
                                    href="viewequip.php?id=<?= (int)$asset['id'] ?>"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ROWS_PER_PAGE = 10;

    const searchInput = document.getElementById('equipmentSearch');
    const conditionFilter = document.getElementById('conditionFilter');
    const locationFilter = document.getElementById('locationFilter');
    const clearFilters = document.getElementById('clearFilters');

    const tbody = document.querySelector('.equipment-table tbody');
    const pagination = document.querySelector('.pagination');

    const buttons = pagination.querySelectorAll('.pagination-btn');
    const prevBtn = buttons[0];
    const nextBtn = buttons[1];

    const allRows = Array.from(tbody.querySelectorAll('tr')).filter(function(row) {
        return !row.querySelector('td[colspan]');
    });

    let filteredRows = [...allRows];
    let currentPage = 1;

    const emptyRow = document.createElement('tr');

    emptyRow.innerHTML =
        '<td colspan="5" style="text-align:center;padding:1rem;">No equipment found.</td>';

    emptyRow.style.display = 'none';
    tbody.appendChild(emptyRow);

    function applyFilters() {
        const query = searchInput.value.trim().toLowerCase();
        const condition = conditionFilter.value.toLowerCase();
        const location = locationFilter.value.toLowerCase();

        filteredRows = allRows.filter(function(row) {
            const id = row.cells[0].textContent.trim().toLowerCase();
            const name = row.cells[1].textContent.trim().toLowerCase();
            const rowLocation = row.cells[2].textContent.trim().toLowerCase();

            const conditionElement = row.querySelector('.condition');

            const rowCondition = conditionElement
                ? Array.from(conditionElement.classList).find(function(className) {
                    return ['good', 'fair', 'poor'].includes(className);
                }) || ''
                : '';

            const matchesSearch = [id, name, rowLocation].some(function(value) {
                return value.includes(query);
            });

            const matchesCondition =
                condition === 'all' || rowCondition === condition;

            const matchesLocation =
                location === 'all' || rowLocation === location;

            return matchesSearch && matchesCondition && matchesLocation;
        });

        currentPage = 1;
        render();
    }

    function render() {
        const totalPages = Math.max(
            1,
            Math.ceil(filteredRows.length / ROWS_PER_PAGE)
        );

        currentPage = Math.min(currentPage, totalPages);

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
        pagination.querySelectorAll('.pagination-number').forEach(function(button) {
            button.remove();
        });

        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement('button');

            button.type = 'button';
            button.className =
                'pagination-number' + (i === currentPage ? ' active' : '');

            button.textContent = i;

            button.addEventListener('click', function() {
                currentPage = i;
                render();
            });

            pagination.insertBefore(button, nextBtn);
        }

        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages;

        prevBtn.style.opacity = prevBtn.disabled ? '0.5' : '';
        nextBtn.style.opacity = nextBtn.disabled ? '0.5' : '';

        prevBtn.style.cursor = prevBtn.disabled ? 'not-allowed' : 'pointer';
        nextBtn.style.cursor = nextBtn.disabled ? 'not-allowed' : 'pointer';
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
    locationFilter.addEventListener('change', applyFilters);

    clearFilters.addEventListener('click', function() {
        searchInput.value = '';
        locationFilter.value = 'all';
        conditionFilter.value = 'all';

        applyFilters();
    });

    render();
});
</script>


<div
    id="toast-success"
    class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out"
    role="alert">

    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
        <i class="fa-solid fa-circle-check text-lg"></i>
    </div>

    <div class="ml-3 flex-1 text-sm font-medium">
        Equipment added successfully!
    </div>

    <button
        type="button"
        id="close-success-toast"
        class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100"
        aria-label="Close">

        <i class="fa-solid fa-xmark"></i>

    </button>

</div>

<script>
const successToast = document.getElementById('toast-success');

const showSuccessToast =
    <?= !empty($showSuccessToast) ? 'true' : 'false' ?>;

let successTimer;

function toggleSuccessToast(show) {

    successToast.classList.toggle('translate-x-[120%]', !show);
    successToast.classList.toggle('opacity-0', !show);
    successToast.classList.toggle('translate-x-0', show);
    successToast.classList.toggle('opacity-100', show);

}

function displaySuccessToast() {

    toggleSuccessToast(true);

    clearTimeout(successTimer);

    successTimer = setTimeout(function() {
        toggleSuccessToast(false);
    }, 3000);

}

document
    .getElementById('close-success-toast')
    .addEventListener('click', function() {

        clearTimeout(successTimer);

        toggleSuccessToast(false);

    });

if (showSuccessToast) {
    displaySuccessToast();
}
</script>

</body>
</html>
