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
$filterFloor = trim((string)($_GET['floor'] ?? ''));
$filterLocation = trim((string)($_GET['location'] ?? ''));
$filterPower = trim((string)($_GET['power'] ?? ''));
$equipmentPage = fetch_equipment_page($pdo, $search, $filterCondition, $filterFloor, $filterLocation, $filterPower, (int)($_GET['page'] ?? 1));
$equipmentRows = $equipmentPage['rows'];
$equipmentQuery = ['search' => $search, 'condition' => $filterCondition, 'floor' => $filterFloor, 'location' => $filterLocation, 'power' => $filterPower];
$equipmentPageUrl = static function (int $page) use ($equipmentQuery): string {
    return '?' . http_build_query(array_filter($equipmentQuery, static fn($value) => $value !== '') + ['page' => $page]);
};

if (!function_exists('equipment_split_location')) {
    function equipment_split_location(string $location, array $floors): array
    {
        foreach ($floors as $floor) {
            $prefix = $floor . ' - ';
            if (strncmp($location, $prefix, strlen($prefix)) === 0) {
                return [$floor, substr($location, strlen($prefix))];
            }
        }

        return ['', ''];
    }
}

$floorList = ['Ground', '2nd Floor', '3rd Floor', '4th Floor'];

$tableRows = [];
$roomOptions = [];
$roomQuery = $pdo->query(
    "SELECT DISTINCT
        CASE
            WHEN location ILIKE 'Ground - %' OR location ILIKE '2nd Floor - %'
              OR location ILIKE '3rd Floor - %' OR location ILIKE '4th Floor - %'
            THEN split_part(location, ' - ', 2)
            ELSE location
        END AS room
     FROM public.equipment
     WHERE NULLIF(BTRIM(location), '') IS NOT NULL
     ORDER BY room"
);
foreach ($roomQuery->fetchAll(PDO::FETCH_COLUMN) as $room) {
    $room = trim((string) $room);
    if ($room !== '') $roomOptions[strtolower($room)] = $room;
}

foreach ($equipmentRows as $asset) {
    $rawLocation = (string)($asset['location'] ?? '');
    [$floor, $room] = equipment_split_location($rawLocation, $floorList);

    $floorLabel = $floor === 'Ground' ? 'Ground Floor' : $floor;
    $locationLabel = $floor !== '' ? $room : $rawLocation;

    $powerSource = $asset['power_source'] ?? null;

    $tableRows[] = [
        'id' => (int)$asset['id'],
        'name' => (string)($asset['name'] ?? ''),
        'floor' => $floorLabel !== '' ? $floorLabel : 'N/A',
        'location' => $locationLabel !== '' ? $locationLabel : 'N/A',
        'power_source' => ($powerSource !== null && $powerSource !== '') ? (string)$powerSource : 'N/A',
        'condition' => (string)($asset['condition'] ?? 'Unknown'),
    ];
}

asort($roomOptions, SORT_NATURAL | SORT_FLAG_CASE);

if (!function_exists('lh')) {
    function lh($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
?>

<?php include 'sidebar.php'; ?>

<main class="ml-64 p-8">

    <h1 class="text-3xl font-bold">Equipments List</h1>

    <p class="mt-2 text-black-100 mb-8 text-gray-600">
        View and manage all registered equipments and their current status
    </p>

    <?php if ($message !== ''): ?>
        <div class="mb-4 rounded-md bg-green-100 p-3 text-sm text-green-800">
            <?= lh($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="mb-4 rounded-md bg-red-100 p-3 text-sm text-red-700">
            <?= lh($error) ?>
        </div>
    <?php endif; ?>

    <div class="equipment-toolbar">

        <div class="equipment-filters" style="display:flex;flex-wrap:nowrap;align-items:center;gap:8px;min-width:0;">

            <div class="equipment-search" style="flex:0 0 180px;width:180px;min-width:0;">
                <div class="relative flex items-center">

                    <i class="fa-solid fa-magnifying-glass absolute w-5 h-5 top-2.5 left-2.5 text-slate-600"></i>

                    <input
                        id="equipmentSearch"
                        type="search"
                        value="<?= lh($search) ?>"
                        class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow"
                        placeholder="Search by name or id..." />

                </div>
            </div>

            <select
                id="floorFilter"
                style="flex:0 0 auto;max-width:150px;"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all" <?= $filterFloor === '' ? 'selected' : '' ?>>All Floors</option>
                <option value="ground floor" <?= strtolower($filterFloor) === 'ground floor' ? 'selected' : '' ?>>Ground Floor</option>
                <option value="2nd floor" <?= strtolower($filterFloor) === '2nd floor' ? 'selected' : '' ?>>2nd Floor</option>
                <option value="3rd floor" <?= strtolower($filterFloor) === '3rd floor' ? 'selected' : '' ?>>3rd Floor</option>
                <option value="4th floor" <?= strtolower($filterFloor) === '4th floor' ? 'selected' : '' ?>>4th Floor</option>

            </select>

            <select
                id="locationFilter"
                style="flex:0 0 auto;max-width:150px;"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all" <?= $filterLocation === '' ? 'selected' : '' ?>>All Locations</option>
                <?php foreach ($roomOptions as $roomValue => $roomLabel): ?>
                    <option value="<?= lh($roomValue) ?>" <?= strtolower($filterLocation) === strtolower($roomValue) ? 'selected' : '' ?>><?= lh($roomLabel) ?></option>
                <?php endforeach; ?>

            </select>

            <select
                id="powerFilter"
                style="flex:0 0 auto;max-width:150px;"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all" <?= $filterPower === '' ? 'selected' : '' ?>>All Power Sources</option>
                <option value="electric" <?= strtolower($filterPower) === 'electric' ? 'selected' : '' ?>>Electric</option>
                <option value="non-electric" <?= strtolower($filterPower) === 'non-electric' ? 'selected' : '' ?>>Non-electric</option>

            </select>

            <select
                id="conditionFilter"
                style="flex:0 0 auto;max-width:150px;"
                class="h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">

                <option value="all" <?= $filterCondition === '' ? 'selected' : '' ?>>All Conditions</option>
                <option value="good" <?= strtolower($filterCondition) === 'good' ? 'selected' : '' ?>>Good</option>
                <option value="fair" <?= strtolower($filterCondition) === 'fair' ? 'selected' : '' ?>>Fair</option>
                <option value="poor" <?= strtolower($filterCondition) === 'poor' ? 'selected' : '' ?>>Poor</option>

            </select>

            <button type="button" id="clearFilters" class="clear-filters-btn" style="flex:0 0 auto;white-space:nowrap;">
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
                    <th><i class="fa-solid fa-building"></i> Floor</th>
                    <th><i class="fa-solid fa-location-dot"></i> Location</th>
                    <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                    <th><i class="fa-solid fa-gear"></i> Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($tableRows)): ?>

                    <tr>
                        <td colspan="100%" class="text-center py-6">
                            No equipment found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($tableRows as $asset): ?>

                        <tr data-power="<?= lh(strtolower($asset['power_source'])) ?>">

                            <td>
                                <?= 'EQ-' . str_pad((string)$asset['id'], 3, '0', STR_PAD_LEFT) ?>
                            </td>

                            <td><?= lh($asset['name']) ?></td>

                            <td><?= lh($asset['floor']) ?></td>

                            <td><?= lh($asset['location']) ?></td>

                            <td>
                                <span class="condition <?= lh(strtolower($asset['condition'])) ?>">
                                    <span></span>
                                    <?= lh($asset['condition']) ?>
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

    <div class="pagination" aria-label="Equipment pages">
        <a class="pagination-btn" href="<?= lh($equipmentPageUrl(max(1, $equipmentPage['page'] - 1))) ?>" <?= $equipmentPage['page'] <= 1 ? 'aria-disabled="true" tabindex="-1" style="opacity:.5;pointer-events:none"' : '' ?>>Previous</a>
        <span>Page <?= (int)$equipmentPage['page'] ?> of <?= (int)$equipmentPage['total_pages'] ?> · <?= (int)$equipmentPage['total'] ?> equipment</span>
        <a class="pagination-btn" href="<?= lh($equipmentPageUrl(min($equipmentPage['total_pages'], $equipmentPage['page'] + 1))) ?>" <?= $equipmentPage['page'] >= $equipmentPage['total_pages'] ? 'aria-disabled="true" tabindex="-1" style="opacity:.5;pointer-events:none"' : '' ?>>Next</a>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('equipmentSearch');
    const floorFilter = document.getElementById('floorFilter');
    const locationFilter = document.getElementById('locationFilter');
    const powerFilter = document.getElementById('powerFilter');
    const conditionFilter = document.getElementById('conditionFilter');
    const clearFilters = document.getElementById('clearFilters');

    const submitFilters = function() {
        const params = new URLSearchParams();
        const values = {search: searchInput.value.trim(), floor: floorFilter.value, location: locationFilter.value, power: powerFilter.value, condition: conditionFilter.value};
        Object.keys(values).forEach(function(key) { if (values[key] && values[key] !== 'all') params.set(key, values[key]); });
        window.location.search = params.toString();
    };
    let searchTimer;
    searchInput.addEventListener('input', function() { clearTimeout(searchTimer); searchTimer = setTimeout(submitFilters, 300); });
    [floorFilter, locationFilter, powerFilter, conditionFilter].forEach(function(filter) { filter.addEventListener('change', submitFilters); });

    clearFilters.addEventListener('click', function() {
        searchInput.value = '';
        floorFilter.value = 'all';
        locationFilter.value = 'all';
        powerFilter.value = 'all';
        conditionFilter.value = 'all';

        submitFilters();
    });

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
