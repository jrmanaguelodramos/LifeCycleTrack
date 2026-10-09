<?php
require_once __DIR__ . '/_app.php';
require_login();
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
                if ($name === '' || $location === '' || !in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
                    throw new RuntimeException('Name, location and condition are required.');
                }
                save_asset($pdo, 'equipment', [
                    'name' => $name,
                    'brand' => $_POST['brand'] ?? '',
                    'model' => $_POST['model'] ?? '',
                    'category' => $_POST['category'] ?? ('equipment'),
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
        <h1 class="text-3xl font-bold ">
            Equipments List
        </h1>
        <p class="mt-2 text-black-100 mb-8">
            View and manage all registered equipments and their current status
        </p>


        <div class="flex items-end gap-4 mb-5">

        <!--eto yung search bar-->
        <div class="w-full max-w-sm min-w-50">
            <div class="relative flex items-center">
                <i class="fa-solid fa-magnifying-glass absolute w-5 h-5 top-2.5 left-2.5 text-slate-600"></i>

                <input
                    id="facilitySearch"
                    class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow"
                    placeholder="Search by name or id..."/>
            </div>

        </div>

        <div class="equipment-table-container">

            <select id="conditionFilter" class="w-28 h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer mr-120">
                <!--base nalang ditor-->
                <option value="all">All</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
            </select>
        </div>

        <div class="flex flex-col ">
            <button id="openEquipmentModal" class="border-[#15588F] p-2 rounded-[10px] bg-[#15588F] pr-3 text-white cursor-pointer">
                <i class="fa-solid fa-circle-plus mr-3 ml-2 text-white"></i> Add Equipment
            </button>
        </div>
        
    </div>

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
                <!-- sample data lang naman to -->
                 <tr>
                    <td>0001</td>
                    <td>Printer</td>
                    <td>Admin Room</td>
                    <td>
                        <span class="condition good">
                            <span></span>
                            Good
                        </span>
                    </td>

                    <td>
                       <a href="viewequip.php?id=1">
                            <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button></a>  
                    </td>
                </tr>

                <tr>
                    <td>0002</td>
                    <td>Airconditioner</td>
                    <td>Captain’s Office</td>
                    <td>
                        <span class="condition good">
                            <span></span>
                            Good
                        </span>
                    </td>
                    <td>
                        <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>
    
                <tr>
                    <td>0003</td>
                    <td>Generator</td>
                    <td>Basement</td>
                    <td>
                        <span class="condition fair">
                            <span></span>
                            Fair
                        </span>
                    </td>
                    <td>
                        <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>

                <tr>
                    <td>0004</td>
                    <td>Water Dispenser</td>
                    <td>Lobby</td>
                    <td>
                        <span class="condition good">
                            <span></span>
                            Good
                        </span>
                    </td>
                    <td>
                        <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>

                <tr>
                    <td>0005</td>
                    <td>Grass Cutter</td>
                    <td>Storage Room</td>
                    <td>
                        <span class="condition poor">
                            <span></span>
                            Poor
                        </span>
                    </td>
                    <td>
                        <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>

                <tr>
                    <td>0006</td>
                    <td>Computer</td>
                    <td>Captain’s Office</td>
                    <td>
                        <span class="condition good">
                            <span></span>
                            Good
                        </span>
                    </td>
                    <td>
                        <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>

                <tr>
                    <td>0007</td>
                    <td>Wheel Barrow</td>
                    <td>Storage Room</td>
                    <td>
                        <span class="condition good">
                            <span></span>
                            Good
                        </span>
                    </td>
                    <td>
                        <button class="view-button">
                            <i class="fa-solid fa-eye"></i>
                            View
                        </button>
                    </td>
                </tr>
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
            $modal = __DIR__ . '/addequipmodal.php';

            if (file_exists($modal)) {
                include $modal;
            } else {
                echo "File NOT found: " . $modal;
        }?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ROWS_PER_PAGE = 10; 

        const searchInput  = document.getElementById('facilitySearch');
        const filterSelect = document.getElementById('conditionFilter');
        const tbody        = document.querySelector('.facility-table tbody');
        const pagination   = document.querySelector('.pagination');
        const [prevBtn, nextBtn] = pagination.querySelectorAll('.pagination-btn');

        const allRows = Array.from(tbody.querySelectorAll('tr'));
        let filteredRows = allRows.slice();
        let currentPage = 1;

        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="5" style="text-align:center;padding:1rem;">No facilities found.</td>';
        emptyRow.style.display = 'none';
        tbody.appendChild(emptyRow);

        function applyFilters() {
            const query     = searchInput.value.trim().toLowerCase();
            const condition = filterSelect.value;

            filteredRows = allRows.filter(function (row) {
                const id   = row.cells[0].textContent.trim().toLowerCase();
                const name = row.cells[1].textContent.trim().toLowerCase();
                const cond = row.querySelector('.condition');

                const matchesSearch = id.includes(query) || name.includes(query);
                const matchesCond   = condition === 'all' || (cond && cond.classList.contains(condition));

                return matchesSearch && matchesCond;
            });

            currentPage = 1;
            render();
        }

        function render() {
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * ROWS_PER_PAGE;
            const end   = start + ROWS_PER_PAGE;

            allRows.forEach(function (row) { row.style.display = 'none'; });
            filteredRows.slice(start, end).forEach(function (row) { row.style.display = ''; });
            emptyRow.style.display = filteredRows.length === 0 ? '' : 'none';

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            pagination.querySelectorAll('.pagination-number').forEach(function (b) { b.remove(); });

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.className = 'pagination-number' + (i === currentPage ? ' active' : '');
                btn.textContent = i;
                btn.addEventListener('click', function () {
                    currentPage = i;
                    render();
                });
                pagination.insertBefore(btn, nextBtn);
            }

            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;
            prevBtn.style.opacity = prevBtn.disabled ? '0.5' : '';
            nextBtn.style.opacity = nextBtn.disabled ? '0.5' : '';
            prevBtn.style.cursor  = prevBtn.disabled ? 'not-allowed' : '';
            nextBtn.style.cursor  = nextBtn.disabled ? 'not-allowed' : '';
        }

        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) { currentPage--; render(); }
        });

        nextBtn.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
            if (currentPage < totalPages) { currentPage++; render(); }
        });

        searchInput.addEventListener('input', applyFilters);
        filterSelect.addEventListener('change', applyFilters);

        render(); 
    });
    </script>
</body>

</html>