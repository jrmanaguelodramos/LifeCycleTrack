<?php
require_once __DIR__ . '/_app.php';
require_login();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';

if (isset($_GET['fetch_rooms'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        require_login();

        $floor = trim($_GET['floor'] ?? '');

        $allowedFloors = [
            'Ground',
            '2nd Floor',
            '3rd Floor',
            '4th Floor'
        ];

        if (!in_array($floor, $allowedFloors, true)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid floor selected.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT DISTINCT name
             FROM public.facilities
             WHERE location = :floor
             ORDER BY name'
        );

        $stmt->execute(['floor' => $floor]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        error_log('Room lookup failed: ' . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'error' => 'Unable to fetch rooms from the database.'
        ]);
    }

    exit;
}

$error = '';
$showSuccessToast = !empty($_SESSION['equipmentAddedToast']);
unset($_SESSION['equipmentAddedToast']);

function ev($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$values = [
    'name' => '',
    'kind' => '',
    'category' => '',
    'brand' => '',
    'model' => '',
    'location' => '',
    'quantity' => '1',
    'year' => '',
    'basis' => 'Original',
    'cost' => '',
    'sched' => '',
    'hours_per_day' => '',
    'status' => 'Good',
    'remarks' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagePath = null;

    foreach ($values as $key => $value) {
        if (isset($_POST[$key])) $values[$key] = trim((string)$_POST[$key]);
    }

    try {
        if (function_exists('verify_csrf')) verify_csrf();

        extract($values, EXTR_SKIP);

        if ($name === '' || mb_strlen($name) > 255)
            throw new RuntimeException('Enter an equipment name with no more than 255 characters.');

        if (!in_array($kind, ['Movable', 'Fixed'], true))
            throw new RuntimeException('Select a valid asset type.');

        if (!in_array($category, [
            'Fire equipment',
            'IT equipment',
            'Office, facility or utility equipment'
        ], true))
            throw new RuntimeException('Select a valid equipment category.');

        if ($location === '')
            throw new RuntimeException('Select an assigned office or location.');

        if (
            $year === '' || !ctype_digit($year) ||
            (int)$year < 1950 || (int)$year > (int)date('Y')
        )
            throw new RuntimeException('Enter a valid acquisition year.');

        if ($quantity === '' || !ctype_digit($quantity) || (int)$quantity < 1)
            throw new RuntimeException('Quantity must be at least 1.');

        if (!in_array($basis, ['Original', 'Current'], true))
            throw new RuntimeException('Select a valid cost type.');

        if ($cost !== '' && (!is_numeric($cost) || (float)$cost < 0))
            throw new RuntimeException('Enter a valid amount.');

        if (!in_array($sched, ['Standard', 'Continuous', 'Seasonal'], true))
            throw new RuntimeException('Select a valid usage schedule.');

        if (
            $hours_per_day !== '' &&
            (!is_numeric($hours_per_day) ||
                (float)$hours_per_day < 0 ||
                (float)$hours_per_day > 24)
        )
            throw new RuntimeException('Hours of use must be between 0 and 24.');

        if (!in_array($status, ['Good', 'Fair', 'Poor'], true))
            throw new RuntimeException('Select a valid condition.');

        $imageUrl = null;

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK)
                throw new RuntimeException('Image upload failed.');

            if ($file['size'] > 5 * 1024 * 1024)
                throw new RuntimeException('The image must not exceed 5 MB.');

            if (!is_uploaded_file($file['tmp_name']))
                throw new RuntimeException('Invalid image upload.');

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($types[$mime]))
                throw new RuntimeException('Only JPG, PNG, and WebP images are allowed.');

            $directory = dirname(__DIR__) . '/uploads/equipment';

            if (
                !is_dir($directory) &&
                !mkdir($directory, 0755, true) &&
                !is_dir($directory)
            )
                throw new RuntimeException('Unable to create the image directory.');

            if (!is_writable($directory))
                throw new RuntimeException('The image directory is not writable.');

            $fileName = bin2hex(random_bytes(16)) . '.' . $types[$mime];
            $imagePath = $directory . '/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $imagePath)) {
                $imagePath = null;
                throw new RuntimeException('Unable to save the equipment image.');
            }

            $imageUrl = '../uploads/equipment/' . $fileName;
        }

        $year = (int)$year;
        $quantity = (int)$quantity;
        $cost = $cost !== '' ? (float)$cost : null;
        $hours = $hours_per_day !== '' ? (float)$hours_per_day : null;

        $assetTag = 'EQ-' . strtoupper(bin2hex(random_bytes(5)));

        $lifecycleStatus = $status === 'Poor'
            ? 'Unserviceable'
            : 'Serviceable';

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO public.equipment (
                asset_tag,
                name,
                brand,
                model,
                category,
                location,
                date_acquired,
                acquisition_year,
                is_estimated_year,
                condition,
                status,
                acquisition_cost,
                cost_type,
                usage_schedule,
                hours_per_day,
                quantity,
                details_name,
                remarks,
                image_url
            ) VALUES (
                :asset_tag,
                :name,
                :brand,
                :model,
                :category,
                :location,
                :date_acquired,
                :acquisition_year,
                :is_estimated_year,
                :condition,
                :status,
                :acquisition_cost,
                :cost_type,
                :usage_schedule,
                :hours_per_day,
                :quantity,
                :details_name,
                :remarks,
                :image_url
            )'
        );

        $stmt->execute([
            'asset_tag' => $assetTag,
            'name' => $name,
            'brand' => $brand !== '' ? $brand : null,
            'model' => $model !== '' ? $model : null,
            'category' => $category,
            'location' => $location,
            'date_acquired' => sprintf('%04d-01-01', $year),
            'acquisition_year' => $year,
            'is_estimated_year' => !empty($_POST['year_estimated']) ? 'true' : 'false',
            'condition' => $status,
            'status' => $lifecycleStatus,
            'acquisition_cost' => $cost,
            'cost_type' => $basis,
            'usage_schedule' => $sched,
            'hours_per_day' => $hours,
            'quantity' => $quantity,
            'details_name' => $name,
            'remarks' => $remarks !== '' ? $remarks : null,
            'image_url' => $imageUrl
        ]);

        $pdo->commit();

        $_SESSION['equipmentAddedToast'] = true;
        header('Location: equipments_list.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($imagePath && is_file($imagePath)) unlink($imagePath);

        error_log('Equipment insertion failed: ' . $e->getMessage());

        $error = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to add equipment. Check the database columns and configuration.';
    }
}

$yearNow = (int)date('Y');
$equipmentAge = !empty($values['year']) &&
    ctype_digit($values['year']) &&
    (int)$values['year'] <= $yearNow &&
    (int)$values['year'] >= 1950
    ? ($yearNow - (int)$values['year']) . ' year(s)'
    : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="flex items-center gap-2 text-sm text-slate-500 [&_a:hover]:text-blue-900 [&_a:hover]:underline [&_i.sep]:text-[10px] [&_i.sep]:text-slate-300">
                <li>
                    <a href="equipments_list.php" class="mr-2 block max-w-[200px] truncate no-underline">
                        <i class="fa-solid fa-wrench mr-2"></i>Equipments List
                    </a>
                </li>
                <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right"></i></li>
                <li aria-current="page" class="font-medium text-blue-900">Add Equipment</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold">Add Equipment</h1>
        <p class="mt-2 mb-3 text-gray-600">Register new equipment</p>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= ev($error) ?>
            </div>
        <?php endif; ?>

        <form id="equipmentForm" method="POST" enctype="multipart/form-data" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="add">
            <?php
            if (function_exists('csrf_field')) echo csrf_field();
            elseif (function_exists('csrf_token'))
                echo '<input type="hidden" name="csrf_token" value="' . ev(csrf_token()) . '">';
            ?>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">
                Equipment Information
            </h2>

            <p class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                Fields marked with <span class="text-red-500">*</span> are required.
            </p>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" value="<?= ev($values['name']) ?>" maxlength="255" placeholder="e.g. Computer, Printer" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="kind" class="mb-1 block text-sm font-medium text-gray-700">Asset Type <span class="text-red-500">*</span></label>
                    <select id="kind" name="kind" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select asset type</option>
                        <?php foreach (['Movable' => 'Movable asset', 'Fixed' => 'Fixed asset'] as $value => $label): ?>
                            <option value="<?= ev($value) ?>" <?= $values['kind'] === $value ? ' selected' : '' ?>><?= ev($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Equipment Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select category</option>
                        <?php foreach (['Fire equipment', 'IT equipment', 'Office, facility or utility equipment'] as $option): ?>
                            <option value="<?= ev($option) ?>" <?= $values['category'] === $option ? ' selected' : '' ?>><?= ev($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php foreach (['brand' => 'Brand', 'model' => 'Model'] as $field => $label): ?>
                    <div>
                        <label for="<?= $field ?>" class="mb-1 block text-sm font-medium text-gray-700"><?= $label ?></label>
                        <input id="<?= $field ?>" name="<?= $field ?>" value="<?= ev($values[$field]) ?>" placeholder="<?= $field === 'brand' ? 'e.g. Canon' : 'e.g. IP2770' ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    </div>
                <?php endforeach; ?>

                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Equipment Image</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="floor" class="mb-1 block text-sm font-medium text-gray-700">Assigned Floor <span class="text-red-500">*</span></label>
                    <select id="floor" name="floor" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select Floor</option>
                        <option value="Ground">Ground Floor</option>
                        <option value="2nd Floor">2nd Floor</option>
                        <option value="3rd Floor">3rd Floor</option>
                        <option value="4th Floor">4th Floor</option>
                    </select>
                </div>


                <div>
                    <label for="room" class="mb-1 block text-sm font-medium text-gray-700">
                        Assigned Room <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="room"
                        name="room"
                        required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select a floor first</option>
                    </select>

                    <p id="roomMessage" class="mt-1 text-xs text-gray-500">
                        Choose a floor to load available rooms.
                    </p>
                </div>


                <div>
                    <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="<?= ev($values['quantity']) ?>" placeholder="e.g. 30" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">For multiple identical items.</p>
                </div>
            </div>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">
                Acquisition and Financial Information
            </h2>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                    <input id="year" name="year" type="number" required min="1950" max="<?= $yearNow ?>" value="<?= ev($values['year']) ?>" placeholder="YYYY" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-600">
                        <input
                            id="yearEst"
                            name="year_estimated"
                            type="checkbox"
                            value="1">
                        Estimated year
                    </label>
                </div>

                <div>
                    <label for="basis" class="mb-1 block text-sm font-medium text-gray-700">Cost Type</label>
                    <select id="basis" name="basis" class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="Original" <?= $values['basis'] === 'Original' ? ' selected' : '' ?>>Original cost</option>
                        <option value="Current" <?= $values['basis'] === 'Current' ? ' selected' : '' ?>>Current value (original unknown)</option>
                    </select>
                </div>

                <div>
                    <label for="cost" class="mb-1 block text-sm font-medium text-gray-700">Amount (₱)</label>
                    <input id="cost" name="cost" type="number" min="0" step="0.01" value="<?= ev($values['cost']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">
                <section class="xl:col-span-3">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Usage</h2>
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0">
                        <div>
                            <label for="sched" class="mb-1 block text-sm font-medium text-gray-700">Usage Schedule <span class="text-red-500">*</span></label>
                            <select id="sched" name="sched" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <option value="">Select schedule</option>
                                <?php foreach (['Standard' => 'Standard (8 hours)', 'Continuous' => 'Continuous (24 hours)', 'Seasonal' => 'Emergency / Seasonal'] as $value => $label): ?>
                                    <option value="<?= ev($value) ?>" <?= $values['sched'] === $value ? ' selected' : '' ?>><?= ev($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="hours" class="mb-1 block text-sm font-medium text-gray-700">Hours of Use per Day</label>
                            <input id="hours" name="hours_per_day" type="number" min="0" max="24" step="0.5" value="<?= ev($values['hours_per_day']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        </div>

                        <div>
                            <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Current Asset Age</label>
                            <input id="age" value="<?= ev($equipmentAge) ?>" readonly class="w-full cursor-not-allowed rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500">
                        </div>
                    </div>
                </section>

                <section class="xl:col-span-2">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:mb-0">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <?php foreach (['Good', 'Fair', 'Poor'] as $option): ?>
                                    <option value="<?= ev($option) ?>" <?= $values['status'] === $option ? ' selected' : '' ?>><?= ev($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2" placeholder="Add remarks..." class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"><?= ev($values['remarks']) ?></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <button type="reset" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Clear</button>
                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white hover:bg-[#103F6C]">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Add Equipment
                </button>
            </div>
        </form>
    </main>

    <div id="equipmentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i>
            </div>
            <h2 class="mb-2 text-xl font-bold text-gray-800">Confirm Equipment</h2>
            <p class="mb-6 text-sm text-gray-500">Are you sure you want to add this equipment? Please check the information before proceeding.</p>
            <div class="flex justify-center gap-3">
                <button type="button" id="cancelEquipment" class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fa-solid fa-xmark mr-1"></i>Cancel
                </button>
                <button type="button" id="confirmEquipment" class="cursor-pointer rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]">
                    <i class="fa-solid fa-check mr-1"></i>Yes, Add Equipment
                </button>
            </div>
        </div>
    </div>

    <div id="toast-warning" role="alert" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600"><i class="fa-solid fa-circle-exclamation text-lg"></i></div>
        <div id="toast-message" class="ml-3 flex-1 text-sm font-medium">Please fill out the required fields.</div>
        <button type="button" data-close="warning" class="ml-3 flex h-8 w-8 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div id="toast-success" role="alert" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600"><i class="fa-solid fa-circle-check text-lg"></i></div>
        <div class="ml-3 flex-1 text-sm font-medium">Equipment added successfully!</div>
        <button type="button" data-close="success" class="ml-3 flex h-8 w-8 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <script>
        const form = document.getElementById('equipmentForm');
        const modal = document.getElementById('equipmentModal');
        const warningToast = document.getElementById('toast-warning');
        const successToast = document.getElementById('toast-success');
        const toastMessage = document.getElementById('toast-message');
        const confirmButton = document.getElementById('confirmEquipment');
        const floorSelect = document.getElementById('floor');
        const roomSelect = document.getElementById('room');
        const roomMessage = document.getElementById('roomMessage');

        let roomRequestId = 0;

        let warningTimer, successTimer;

        function toggleToast(toast, show) {
            toast.classList.toggle('translate-x-[120%]', !show);
            toast.classList.toggle('opacity-0', !show);
            toast.classList.toggle('translate-x-0', show);
            toast.classList.toggle('opacity-100', show);
        }

        function showToast(message) {
            toastMessage.textContent = message;
            toggleToast(warningToast, true);
            clearTimeout(warningTimer);
            warningTimer = setTimeout(() => toggleToast(warningToast, false), 3000);
        }

        roomSelect.parentElement.addEventListener('click', function() {
            if (!floorSelect.value) {
                // Highlight the floor selector
                floorSelect.classList.remove('border-slate-300');
                floorSelect.classList.add('border-red-500', 'ring-1', 'ring-red-500');

                // Show the existing warning toast
                showToast('Please select an Assigned Floor first.');
            }
        });

        floorSelect.addEventListener('change', function() {
            if (floorSelect.value) {
                floorSelect.classList.remove(
                    'border-red-500',
                    'ring-2',
                    'ring-red-200'
                );

                floorLabel.classList.remove('text-red-600');
                floorLabel.classList.add('text-gray-700');
            }
        });

        function showSuccessToast() {
            toggleToast(successToast, true);
            clearTimeout(successTimer);
            successTimer = setTimeout(() => toggleToast(successToast, false), 3000);
        }

        document.querySelectorAll('[data-close]').forEach(button => {
            button.addEventListener('click', () => {
                const toast = button.dataset.close === 'warning' ? warningToast : successToast;
                clearTimeout(button.dataset.close === 'warning' ? warningTimer : successTimer);
                toggleToast(toast, false);
            });
        });

        <?php if ($showSuccessToast): ?>
            showSuccessToast();
        <?php endif; ?>

        form.addEventListener('submit', event => {
            event.preventDefault();

            const invalid = form.querySelector(':invalid');

            if (invalid) {
                const label = invalid.id ?
                    form.querySelector(`label[for="${invalid.id}"]`) :
                    null;

                showToast(`Please check the ${label ? label.textContent.replace('*', '').trim() : 'required fields'} field.`);
                invalid.focus();
                return;
            }

            const year = Number(document.getElementById('year').value);
            if (year < 1950 || year > new Date().getFullYear()) {
                showToast('Enter a valid acquisition year.');
                document.getElementById('year').focus();
                return;
            }

            const image = document.getElementById('image').files[0];

            if (image && !['image/jpeg', 'image/png', 'image/webp'].includes(image.type)) {
                showToast('Only JPG, PNG, and WebP images are allowed.');
                return;
            }

            if (image && image.size > 5 * 1024 * 1024) {
                showToast('The image must not exceed 5 MB.');
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('cancelEquipment').addEventListener('click', closeModal);

        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        confirmButton.addEventListener('click', () => {
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
            HTMLFormElement.prototype.submit.call(form);
        });

        form.addEventListener('reset', () => setTimeout(updateAge, 0));
        document.getElementById('year').addEventListener('input', updateAge);

        function updateAge() {
            const value = document.getElementById('year').value;
            const year = Number(value);

            document.getElementById('age').value =
                value && year >= 1950 && year <= new Date().getFullYear() ?
                `${new Date().getFullYear() - year} year(s)` :
                '';
        }
        async function loadRooms() {
            const floor = floorSelect.value;
            const requestId = ++roomRequestId;

            roomSelect.replaceChildren();

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = floor ?
                'Loading rooms...' :
                'Select a floor first';

            roomSelect.appendChild(placeholder);
            roomSelect.disabled = true;

            if (!floor) {
                roomMessage.textContent = 'Choose a floor first.';
                return;
            }

            roomMessage.textContent = 'Fetching rooms...';

            try {
                const url = new URL(window.location.href);
                url.search = '';
                url.searchParams.set('fetch_rooms', '1');
                url.searchParams.set('floor', floor);

                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json'
                    },
                    credentials: 'same-origin'
                });

                const rooms = await response.json();

                if (requestId !== roomRequestId) return;

                if (!response.ok) {
                    throw new Error(
                        rooms.error || 'Unable to fetch rooms.'
                    );
                }

                roomSelect.replaceChildren();

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = rooms.length ?
                    'Select Room' :
                    'No rooms found';

                roomSelect.appendChild(defaultOption);

                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room;
                    option.textContent = room;
                    roomSelect.appendChild(option);
                });

                roomSelect.disabled = rooms.length === 0;

                roomMessage.textContent = rooms.length ?
                    `${rooms.length} room(s) available.` :
                    'No rooms found for this floor.';

            } catch (error) {
                if (requestId !== roomRequestId) return;

                console.error('Room fetch failed:', error);
                roomSelect.replaceChildren();

                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Unable to load rooms';

                roomSelect.appendChild(option);
                roomMessage.textContent = error.message;
            }
        }

        floorSelect.addEventListener('change', loadRooms);
    </script>
</body>

</html>