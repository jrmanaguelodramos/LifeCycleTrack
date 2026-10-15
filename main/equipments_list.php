<?php
require_once __DIR__ . '/_app.php';
require_login();

session_start();

if (!isset($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}
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
    <style>
       
    </style>
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
                    throw new RuntimeException('Name, location and condition are required.');
                }

                save_asset($pdo, 'equipment', [
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
    $filterCondition = (string)($_GET['condition'] ?? '');
    $assets = fetch_assets($pdo, 'equipment', $search, $filterCondition);
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
                            placeholder="Search by name or id..."
                        />
                    </div>
                </div>

                <select id="locationFilter" class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">
                    <option value="all">All Locations</option>
                    <option value="main building">Main Building</option>
                    <option value="building a">Building A</option>
                    <option value="building b">Building B</option>
                </select>

                <select id="floorFilter" class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">
                    <option value="all">All Floors</option>
                    <option value="ground floor">Ground Floor</option>
                    <option value="basement">Basement</option>
                    <option value="1st floor">1st Floor</option>
                    <option value="2nd floor">2nd Floor</option>
                    <option value="3rd floor">3rd Floor</option>
                    <option value="4th floor">4th Floor</option>
                </select>

                <select id="conditionFilter" class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">
                    <option value="all">All Conditions</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </select>

                <button type="button" id="clearFilters" class="clear-filters-btn">
                    Clear Filters
                </button>
            </div>

            <a href="addequipment.php" class="add-equipment-button border-[#15588F] p-2 rounded-[10px] bg-[#15588F] pr-3 text-white cursor-pointer">
                <i class="fa-solid fa-plus mr-3 ml-2 text-white"></i> Add Equipment
            </a>
        </div>

        <div class="equipment-table-container">
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-id-card"></i> ID</th>
                        <th><i class="fa-solid fa-tag"></i> Name</th>
                        <th><i class="fa-solid fa-location-dot"></i> Location</th>
                        <th><i class="fa-solid fa-building"></i> Floor</th>
                        <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                        <th><i class="fa-solid fa-gear"></i> Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>0001</td>
                        <td>Printer</td>
                        <td>Main Building</td>
                        <td>Ground Floor</td>
                        <td><span class="condition good"><span></span>Good</span></td>
                        <td><a href="viewequip.php?id=1"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>

                    <tr>
                        <td>0002</td>
                        <td>Airconditioner</td>
                        <td>Building A</td>
                        <td>Ground Floor</td>
                        <td><span class="condition good"><span></span>Good</span></td>
                        <td><a href="viewequip.php?id=2"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>

                    <tr>
                        <td>0003</td>
                        <td>Generator</td>
                        <td>Building B</td>
                        <td>Basement</td>
                        <td><span class="condition fair"><span></span>Fair</span></td>
                        <td><a href="viewequip.php?id=3"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>

                    <tr>
                        <td>0004</td>
                        <td>Water Dispenser</td>
                        <td>Main Building</td>
                        <td>Ground Floor</td>
                        <td><span class="condition good"><span></span>Good</span></td>
                        <td><a href="viewequip.php?id=4"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>

                    <tr>
                        <td>0005</td>
                        <td>Grass Cutter</td>
                        <td>Building A</td>
                        <td>Ground Floor</td>
                        <td><span class="condition poor"><span></span>Poor</span></td>
                        <td><a href="viewequip.php?id=5"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>

                    <tr>
                        <td>0006</td>
                        <td>Computer</td>
                        <td>Building B</td>
                        <td>2nd Floor</td>
                        <td><span class="condition good"><span></span>Good</span></td>
                        <td><a href="viewequip.php?id=6"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>

                    <tr>
                        <td>0007</td>
                        <td>Wheel Barrow</td>
                        <td>Building A</td>
                        <td>Ground Floor</td>
                        <td><span class="condition good"><span></span>Good</span></td>
                        <td><a href="viewequip.php?id=7"><button class="view-button"><i class="fa-solid fa-eye"></i> View</button></a></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <button type="button" class="pagination-btn"><i class="fa-solid fa-chevron-left"></i> Previous</button>
            <button type="button" class="pagination-number active">1</button>
            <button type="button" class="pagination-btn">Next <i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ROWS_PER_PAGE = 10;
            const searchInput = document.getElementById('equipmentSearch');
            const conditionFilter = document.getElementById('conditionFilter');
            const locationFilter = document.getElementById('locationFilter');
            const floorFilter = document.getElementById('floorFilter');
            const clearFilters = document.getElementById('clearFilters');
            const tbody = document.querySelector('.equipment-table tbody');
            const pagination = document.querySelector('.pagination');
            const [prevBtn, nextBtn] = pagination.querySelectorAll('.pagination-btn');

            const allRows = Array.from(tbody.querySelectorAll('tr'));
            let filteredRows = [...allRows];
            let currentPage = 1;

            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="6" style="text-align:center;padding:1rem;">No equipment found.</td>';
            emptyRow.style.display = 'none';
            tbody.appendChild(emptyRow);

            function applyFilters() {
                const query = searchInput.value.trim().toLowerCase();
                const condition = conditionFilter.value.toLowerCase();
                const location = locationFilter.value.toLowerCase();
                const floor = floorFilter.value.toLowerCase();

                filteredRows = allRows.filter(function (row) {
                    const id = row.cells[0].textContent.trim().toLowerCase();
                    const name = row.cells[1].textContent.trim().toLowerCase();
                    const rowLocation = row.cells[2].textContent.trim().toLowerCase();
                    const rowFloor = row.cells[3].textContent.trim().toLowerCase();
                    const conditionElement = row.querySelector('.condition');

                    const rowCondition = conditionElement
                        ? Array.from(conditionElement.classList).find(function (className) {
                            return ['good', 'fair', 'poor'].includes(className);
                        }) || ''
                        : '';

                    const matchesSearch = [id, name, rowLocation, rowFloor].some(function (value) {
                        return value.includes(query);
                    });

                    const matchesCondition = condition === 'all' || rowCondition === condition;
                    const matchesLocation = location === 'all' || rowLocation === location;
                    const matchesFloor = floor === 'all' || rowFloor === floor;

                    return matchesSearch && matchesCondition && matchesLocation && matchesFloor;
                });

                currentPage = 1;
                render();
            }

            function render() {
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
                currentPage = Math.min(currentPage, totalPages);

                const start = (currentPage - 1) * ROWS_PER_PAGE;

                allRows.forEach(function (row) {
                    row.style.display = 'none';
                });

                filteredRows.slice(start, start + ROWS_PER_PAGE).forEach(function (row) {
                    row.style.display = '';
                });

                emptyRow.style.display = filteredRows.length === 0 ? '' : 'none';
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                pagination.querySelectorAll('.pagination-number').forEach(function (button) {
                    button.remove();
                });

                for (let i = 1; i <= totalPages; i++) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'pagination-number' + (i === currentPage ? ' active' : '');
                    button.textContent = i;
                    button.setAttribute('aria-current', i === currentPage ? 'page' : 'false');

                    button.addEventListener('click', function () {
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

            prevBtn.addEventListener('click', function () {
                if (currentPage > 1) {
                    currentPage--;
                    render();
                }
            });

            nextBtn.addEventListener('click', function () {
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));

                if (currentPage < totalPages) {
                    currentPage++;
                    render();
                }
            });

            searchInput.addEventListener('input', applyFilters);
            conditionFilter.addEventListener('change', applyFilters);
            locationFilter.addEventListener('change', applyFilters);
            floorFilter.addEventListener('change', applyFilters);

            clearFilters.addEventListener('click', function () {
                searchInput.value = '';
                locationFilter.value = 'all';
                floorFilter.value = 'all';
                conditionFilter.value = 'all';
                applyFilters();
            });

            render();
        });
    </script>
</body>
</html>
