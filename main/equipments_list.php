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
        <div>
            <h1 class="text-3xl font-bold ">Equipments List</h1>
            <p class="mt-2 text-black-100 mb-8">View and manage all registered equipment and their current status</p>
        </div>
        <?php if ($message): ?>
            <div class="notice success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="notice error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="flex items-end gap-4 mb-5">
            <!--eto yung search bar-->
            <form method="GET"
                class="flex flex-wrap items-end gap-4 mb-5">

                <!-- Search -->
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

                <!-- Condition -->
                <div>
                    <label for="condition" class="block text-xs font-medium text-gray-700 mb-1">
                        Condition
                    </label>

                    <select
                        id="condition"
                        name="condition"
                        class="w-32 h-10 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92]">

                        <option value="all" <?= $filterCondition === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="good" <?= $filterCondition === 'good' ? 'selected' : '' ?>>Good</option>
                        <option value="fair" <?= $filterCondition === 'fair' ? 'selected' : '' ?>>Fair</option>
                        <option value="poor" <?= $filterCondition === 'poor' ? 'selected' : '' ?>>Poor</option>
                    </select>
                </div>

                <!-- Apply Filter -->
                <button
                    type="submit"
                    class="h-10 px-4 rounded-md bg-[#155B92] text-white hover:bg-[#124b79]">
                    <i class="fa-solid fa-filter mr-1"></i>
                    Apply
                </button>

                <!-- Reset -->
                <a
                    href="equipments_list.php"
                    class="h-10 px-4 inline-flex items-center rounded-md border border-gray-300 bg-white hover:bg-gray-50">
                    <i class="fa-solid fa-rotate-left mr-1"></i>
                    Reset
                </a>

            </form>

            <div class="flex flex-col ">
                <button
                    id="openEquipmentModal"
                    type="button"
                    class="rounded-md bg-[#155B92] px-5 py-2
           font-semibold text-white hover:bg-[#124b79]">
                    <i class="fa-solid fa-circle-plus mr-2"></i>
                    Add Equipment
                </button>
            </div>

        </div>

        <div class="equipment-table-container">

            <table class="equipment-table">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-id-card"></i> ID</th>
                        <th><i class="fa-solid fa-tag"></i> Name</th>
                        <th><i class="fa-solid fa-location-dot"></i> Location</th>
                        <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                        <th><i class="fa-solid fa-calendar"></i> Acquired</th>
                        <th><i class="fa-solid fa-gear"></i> Actions</th>
                    </tr>
                </thead>


                <tbody>
                    <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                No records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                            <?php
                            $type_ = strtolower(
                                (string)($asset['asset_type'] ?? '')
                            );

                            $viewPage = $type_ === 'facility'
                                ? 'viewfacility.php'
                                : 'viewequip.php';
                            ?>

                            <tr>
                                <td>
                                    <?= e(str_pad((string)$asset['id'], 4, '0', STR_PAD_LEFT)) ?>
                                </td>

                                <td><?= e($asset['name'] ?? '') ?></td>

                                <td><?= e($asset['location'] ?? '—') ?></td>

                                <td>
                                    <?= condition_badge($asset['condition'] ?? '') ?>
                                </td>

                                <td>
                                    <?= !empty($asset['date_acquired'])
                                        ? e(date('m-d-Y', strtotime($asset['date_acquired'])))
                                        : '—' ?>
                                </td>

                                <td>
                                    <a
                                        class="view-button"
                                        href="<?= e($viewPage) ?>?id=<?= (int)$asset['id'] ?>">
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


    </main>

    <?php
    $modal = __DIR__ . '/addequipmentmodal.php';

    if (file_exists($modal)) {
        include $modal;
    } else {
        echo "File NOT found: " . $modal;
    } ?>

</body>

</html>