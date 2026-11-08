<?php
require_once __DIR__ . '/_app.php';
require_login();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/central_sync.php';

$allowedFloors = [
    'Ground',
    '2nd Floor',
    '3rd Floor',
    '4th Floor'
];

if (isset($_GET['fetch_rooms'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $floor = trim($_GET['floor'] ?? '');

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
$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$equipmentId || $equipmentId < 1) {
    http_response_code(400);
    exit('Invalid equipment ID.');
}

if (!function_exists('ev')) {
    function ev($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sel')) {
    function sel($saved, $option): string
    {
        return (string) $saved === (string) $option ? ' selected' : '';
    }
}

/**
 * Splits a saved location such as "2nd Floor - Storage Room" into [floor, room].
 * Returns ['', ''] when the value is not in "Floor - Room" format.
 */
function split_location(string $location, array $floors): array
{
    foreach ($floors as $floor) {
        $prefix = $floor . ' - ';
        if (strncmp($location, $prefix, strlen($prefix)) === 0) {
            return [$floor, substr($location, strlen($prefix))];
        }
    }

    return ['', ''];
}

$categories = [
    'Fire equipment',
    'IT equipment',
    'Office, facility or utility equipment',
];

$assetTypes = [
    'Movable Asset' => 'Movable asset',
    'Fixed Asset' => 'Fixed asset',
];

$powerSources = [
    'Electric' => 'Electric',
    'Non-electric' => 'Non-electric',
];

$schedules = [
    'Standard' => 'Standard (8 hours)',
    'Continuous' => 'Continuous (24 hours)',
    'Seasonal' => 'Emergency / Seasonal',
];

$equipment = [
    'id' => $equipmentId,
    'name' => '',
    'category' => '',
    'asset_type' => '',
    'power_source' => '',
    'brand' => '',
    'model' => '',
    'location' => '',
    'floor' => '',
    'room' => '',
    'quantity' => 1,
    'year' => '',
    'year_estimated' => 0,
    'basis' => '',
    'cost' => '',
    'sched' => '',
    'hours_per_day' => '',
    'status' => 'Good',
    'remarks' => '',
    'image_url' => ''
];

try {
    $stmt = $pdo->prepare(
        'SELECT id, name, category, asset_type, power_source, brand, model, location, quantity,
                acquisition_year, is_estimated_year, cost_type, acquisition_cost,
                usage_schedule, hours_per_day, condition, remarks, image_url
         FROM public.equipment WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $equipmentId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(404);
        exit('Equipment not found.');
    }

    [$savedFloor, $savedRoom] = split_location((string) ($record['location'] ?? ''), $allowedFloors);

    $equipment = [
        'id' => (int) $record['id'],
        'name' => $record['name'] ?? '',
        'category' => $record['category'] ?? '',
        'asset_type' => $record['asset_type'] ?? '',
        'power_source' => $record['power_source'] ?? '',
        'brand' => $record['brand'] ?? '',
        'model' => $record['model'] ?? '',
        'location' => $record['location'] ?? '',
        'floor' => $savedFloor,
        'room' => $savedRoom,
        'quantity' => $record['quantity'] ?? 1,
        'year' => $record['acquisition_year'] ?? '',
        'year_estimated' => in_array($record['is_estimated_year'], [true, 1, '1', 't', 'true'], true) ? 1 : 0,
        'basis' => $record['cost_type'] ?? '',
        'cost' => $record['acquisition_cost'] ?? '',
        'sched' => $record['usage_schedule'] ?? '',
        'hours_per_day' => $record['hours_per_day'] ?? '',
        'status' => $record['condition'] ?? 'Good',
        'remarks' => $record['remarks'] ?? '',
        'image_url' => $record['image_url'] ?? ''
    ];
} catch (Throwable $e) {
    error_log('Equipment retrieval failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load equipment information. Check your database configuration.');
}

$legacyLocation = ($equipment['location'] !== '' && $equipment['floor'] === '')
    ? $equipment['location']
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newImagePath = null;

    try {
        if (function_exists('verify_csrf')) verify_csrf();

        $postedId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$postedId || $postedId !== $equipmentId) {
            throw new RuntimeException('Invalid equipment ID.');
        }

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $assetType = trim($_POST['asset_type'] ?? '');
        $powerSource = trim($_POST['power_source'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $floor = trim($_POST['floor'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
        $sched = trim($_POST['sched'] ?? '');
        $hoursInput = trim((string) ($_POST['hours_per_day'] ?? ''));
        $remarks = trim($_POST['remarks'] ?? '');

        if ($name === '' || mb_strlen($name) > 255) {
            throw new RuntimeException($name === ''
                ? 'Equipment name is required.'
                : 'Equipment name must not exceed 255 characters.');
        }

        if (!in_array($category, $categories, true)) {
            throw new RuntimeException('Please select a valid equipment category.');
        }

        if (!isset($assetTypes[$assetType])) {
            throw new RuntimeException('Please select a valid asset type.');
        }

        if (!isset($powerSources[$powerSource])) {
            throw new RuntimeException('Please select a valid power source.');
        }

        if (!in_array($floor, $allowedFloors, true)) {
            throw new RuntimeException('Select a valid assigned floor.');
        }

        if ($room === '') {
            throw new RuntimeException('Select an assigned room.');
        }

        $location = $floor . ' - ' . $room;

        if ($quantity === false || $quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        if (!isset($schedules[$sched])) {
            throw new RuntimeException('Please select a valid usage schedule.');
        }

        $hoursPerDay = null;
        if ($hoursInput !== '') {
            $hoursPerDay = filter_var($hoursInput, FILTER_VALIDATE_FLOAT);
            if ($hoursPerDay === false || $hoursPerDay < 0 || $hoursPerDay > 24) {
                throw new RuntimeException('Hours of use must be between 0 and 24.');
            }
        }

        $currentStatusStmt = $pdo->prepare(
            'SELECT condition FROM public.equipment WHERE id = :id LIMIT 1'
        );
        $currentStatusStmt->execute(['id' => $equipmentId]);
        $currentStatus = $currentStatusStmt->fetchColumn();

        if ($currentStatus === false) {
            throw new RuntimeException('Equipment not found.');
        }

        $status = (string) $currentStatus;

        if (!in_array($status, ['Good', 'Fair', 'Poor'], true)) {
            throw new RuntimeException('The existing equipment condition is invalid. Please check the database.');
        }

        $imageUrl = $equipment['image_url'];
        $oldImageUrl = $equipment['image_url'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Image upload failed. Please try again.');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException('The image must not exceed 5 MB.');
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('Invalid image upload.');
            }

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (!isset($types[$mime])) {
                throw new RuntimeException('Only JPG, PNG, and WebP images are allowed.');
            }

            $directory = dirname(__DIR__) . '/uploads/equipment';

            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create the image directory.');
            }
            if (!is_writable($directory)) {
                throw new RuntimeException('The image directory is not writable.');
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $types[$mime];
            $newImagePath = $directory . '/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $newImagePath)) {
                $newImagePath = null;
                throw new RuntimeException('Unable to save the uploaded image.');
            }

            $imageUrl = '../uploads/equipment/' . $fileName;
        }

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare(
                'UPDATE public.equipment SET
                    name = :name, category = :category,
                    asset_type = :asset_type, power_source = :power_source,
                    brand = :brand, model = :model,
                    location = :location, quantity = :quantity,
                    usage_schedule = :sched, hours_per_day = :hours,
                    condition = :condition, remarks = :remarks, image_url = :image,
                    updated_at = now()
                 WHERE id = :id'
            );

            $update->execute([
                'name' => $name,
                'category' => $category,
                'asset_type' => $assetType,
                'power_source' => $powerSource,
                'brand' => $brand !== '' ? $brand : null,
                'model' => $model !== '' ? $model : null,
                'location' => $location,
                'quantity' => $quantity,
                'sched' => $sched,
                'hours' => $hoursPerDay,
                'condition' => $status,
                'remarks' => $remarks !== '' ? $remarks : null,
                'image' => $imageUrl !== '' ? $imageUrl : null,
                'id' => $equipmentId
            ]);

            if ($update->rowCount() === 0) {
                $check = $pdo->prepare('SELECT id FROM public.equipment WHERE id = :id');
                $check->execute(['id' => $equipmentId]);
                if (!$check->fetchColumn()) throw new RuntimeException('Equipment not found.');
            }

            $syncStmt = $pdo->prepare('SELECT id, asset_tag, name, brand, model, category, asset_type, power_source, location, date_acquired, acquisition_year, is_estimated_year, condition, status, acquisition_cost, cost_type, usage_schedule, hours_per_day, quantity, updated_at FROM public.equipment WHERE id = :id');
            $syncStmt->execute(['id' => $equipmentId]);
            $syncRow = $syncStmt->fetch();
            if (is_array($syncRow)) lifecycle_central_enqueue($pdo, lifecycle_central_payload($syncRow, 'equipment'));

            $pdo->commit();
            lifecycle_central_flush($pdo);

            if ($newImagePath && $oldImageUrl !== '' && strpos($oldImageUrl, '../uploads/equipment/') === 0) {
                $oldFile = dirname(__DIR__) . '/uploads/equipment/' . basename($oldImageUrl);
                if (is_file($oldFile)) unlink($oldFile);
            }

            $_SESSION['equipmentUpdatedToast'] = true;
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?id=' . $equipmentId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($newImagePath && is_file($newImagePath)) unlink($newImagePath);
            throw $e;
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();

        foreach (['name', 'category', 'asset_type', 'power_source', 'brand', 'model', 'floor', 'room', 'sched', 'remarks'] as $key) {
            if (isset($_POST[$key])) $equipment[$key] = trim($_POST[$key]);
        }

        if (isset($_POST['quantity'])) $equipment['quantity'] = trim($_POST['quantity']);
        if (isset($_POST['hours_per_day'])) $equipment['hours_per_day'] = trim($_POST['hours_per_day']);
    } catch (Throwable $e) {
        error_log('Equipment update failed: ' . $e->getMessage());
        $error = 'Unable to save equipment information. Please check the database configuration and try again.';
    }
}

$assetAge = $equipment['year'] !== ''
    ? max(0, (int) date('Y') - (int) $equipment['year']) . ' year(s)'
    : 'N/A';

$lockedInput = 'w-full cursor-not-allowed rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500';
$showSuccessToast = !empty($_SESSION['equipmentUpdatedToast']);
unset($_SESSION['equipmentUpdatedToast']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment</title>
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
                <li><a href="equipments_list.php" class="no-underline"><i class="fa-solid fa-wrench mr-2"></i>Equipments List</a></li>
                <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right"></i></li>
                <li><a href="viewequip.php?id=<?= (int) $equipment['id'] ?>" class="no-underline">Information</a></li>
                <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right"></i></li>
                <li aria-current="page" class="font-medium text-blue-900">Edit Information</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold">Edit Equipment</h1>
        <p class="mt-2 mb-3 text-gray-600">Update equipment information</p>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= ev($error) ?></div>
        <?php endif; ?>

        <?php if ($legacyLocation !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="mb-5 rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                The saved location "<?= ev($legacyLocation) ?>" is not in the Floor - Room format. Select an assigned floor and room to update it.
            </div>
        <?php endif; ?>

        <form id="equipmentForm" method="POST" enctype="multipart/form-data" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int) $equipment['id'] ?>">
            <?php
            if (function_exists('csrf_field')) echo csrf_field();
            elseif (function_exists('csrf_token')) echo '<input type="hidden" name="csrf_token" value="' . ev(csrf_token()) . '">';
            ?>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Equipment Information</h2>
            <p class="mb-4 flex items-center gap-2 text-sm text-gray-500"><i class="fa-solid fa-circle-info text-[#155B92]"></i>Fields marked with <span class="text-red-500">*</span> are required. Some fields are blocked and cannot be edited.</p>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" value="<?= ev($equipment['name']) ?>" placeholder="e.g. Computer, Printer" maxlength="255" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Equipment Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $categoryOption): ?>
                            <option value="<?= ev($categoryOption) ?>" <?= sel($equipment['category'], $categoryOption) ?>><?= ev($categoryOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="brand" class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                    <input id="brand" name="brand" type="text" value="<?= ev($equipment['brand']) ?>" placeholder="e.g. Canon" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="model" class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                    <input id="model" name="model" type="text" value="<?= ev($equipment['model']) ?>" placeholder="e.g. IP2770" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Equipment Image</label>
                    <div style="position: relative;">
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" style="padding-right: 84px;" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <button type="button" id="viewImageBtn" style="position: absolute; top: 50%; right: 8px; transform: translateY(-50%); white-space: nowrap; line-height: 1.25;" class="<?= $equipment['image_url'] === '' ? 'hidden ' : '' ?>cursor-pointer rounded-md bg-[#155B92] px-3 py-1 text-xs font-semibold text-white hover:bg-[#103F6C]">
                            <i class="fa-solid fa-eye mr-1"></i>View
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP. Maximum 5 MB.</p>
                </div>

                <div>
                    <label for="floor" class="mb-1 block text-sm font-medium text-gray-700">Assigned Floor <span class="text-red-500">*</span></label>
                    <select id="floor" name="floor" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select Floor</option>
                        <option value="Ground" <?= sel($equipment['floor'], 'Ground') ?>>Ground Floor</option>
                        <option value="2nd Floor" <?= sel($equipment['floor'], '2nd Floor') ?>>2nd Floor</option>
                        <option value="3rd Floor" <?= sel($equipment['floor'], '3rd Floor') ?>>3rd Floor</option>
                        <option value="4th Floor" <?= sel($equipment['floor'], '4th Floor') ?>>4th Floor</option>
                    </select>
                </div>

                <div>
                    <label for="room" class="mb-1 block text-sm font-medium text-gray-700">Assigned Room <span class="text-red-500">*</span></label>
                    <select id="room" name="room" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select a floor first</option>
                    </select>
                    <p id="roomMessage" class="mt-1 text-xs text-gray-500">Choose a floor to load available rooms.</p>
                </div>

                <div>
                    <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="<?= ev($equipment['quantity']) ?>" placeholder="e.g. 30" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">For multiple identical items.</p>
                </div>

                <div>
                    <label for="asset_type" class="mb-1 block text-sm font-medium text-gray-700">Asset Type <span class="text-red-500">*</span></label>
                    <select id="asset_type" name="asset_type" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select asset type</option>
                        <?php foreach ($assetTypes as $value => $label): ?>
                            <option value="<?= ev($value) ?>" <?= sel($equipment['asset_type'], $value) ?>><?= ev($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="power_source" class="mb-1 block text-sm font-medium text-gray-700">Power Source <span class="text-red-500">*</span></label>
                    <select id="power_source" name="power_source" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select power source</option>
                        <?php foreach ($powerSources as $value => $label): ?>
                            <option value="<?= ev($value) ?>" <?= sel($equipment['power_source'], $value) ?>><?= ev($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition and Financial Information</h2>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year</label>
                    <input id="year" type="number" readonly value="<?= ev($equipment['year']) ?>" placeholder="YYYY" class="<?= $lockedInput ?>">
                    <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-500">
                        <input type="checkbox" disabled <?= !empty($equipment['year_estimated']) ? 'checked' : '' ?>>
                        Estimated year
                    </label>
                </div>

                <div>
                    <label for="basis" class="mb-1 block text-sm font-medium text-gray-700">Cost Type</label>
                    <select id="basis" disabled class="<?= $lockedInput ?>">
                        <option value="Original" <?= sel($equipment['basis'], 'Original') ?>>Original cost</option>
                        <option value="Current" <?= sel($equipment['basis'], 'Current') ?>>Current value (original unknown)</option>
                    </select>
                </div>

                <div>
                    <label for="cost" class="mb-1 block text-sm font-medium text-gray-700">Amount (₱)</label>
                    <input id="cost" type="number" readonly value="<?= ev($equipment['cost']) ?>" class="<?= $lockedInput ?>">
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
                                <?php foreach ($schedules as $value => $label): ?>
                                    <option value="<?= ev($value) ?>" <?= sel($equipment['sched'], $value) ?>><?= ev($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="hours" class="mb-1 block text-sm font-medium text-gray-700">Hours of Use per Day</label>
                            <input id="hours" name="hours_per_day" type="number" min="0" max="24" step="0.5" value="<?= ev($equipment['hours_per_day']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        </div>

                        <div>
                            <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Current Asset Age</label>
                            <input id="age" type="text" readonly value="<?= ev($assetAge) ?>" class="<?= $lockedInput ?>">
                        </div>
                    </div>
                </section>

                <section class="xl:col-span-2">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-2">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status</label>
                            <select id="status" class="<?= $lockedInput ?>" disabled>
                                <?php foreach (['Good', 'Fair', 'Poor'] as $statusOption): ?>
                                    <option value="<?= ev($statusOption) ?>" <?= sel($equipment['status'], $statusOption) ?>><?= ev($statusOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="md:col-span-2 xl:col-span-1">
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2" placeholder="Add remarks..." class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"><?= ev($equipment['remarks']) ?></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="viewequip.php?id=<?= (int) $equipment['id'] ?>" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Cancel</a>
                <button type="reset" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Reset Changes</button>
                <button type="submit" class="rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white hover:bg-[#103F6C]"><i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes</button>
            </div>
        </form>
    </main>

    <div id="imageModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/90 p-6">
        <button type="button" id="closeImageModal" class="absolute top-5 right-6 z-10 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-4xl text-white transition hover:bg-white/20 hover:text-gray-300" aria-label="Close image preview">&times;</button>
        <img id="modalImage" src="" alt="Equipment image" style="width:auto;height:auto;max-width:75vw;max-height:70vh;object-fit:contain;display:block;" class="rounded-lg shadow-2xl">
    </div>

    <div id="equipmentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100"><i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i></div>
            <h2 class="mb-2 text-xl font-bold text-gray-800">Confirm Changes</h2>
            <p class="mb-6 text-sm text-gray-500">Are you sure you want to save these equipment changes?</p>
            <div class="flex justify-center gap-3">
                <button type="button" id="cancelEquipment" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-xmark mr-1"></i>Cancel</button>
                <button type="button" id="confirmEquipment" class="rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]"><i class="fa-solid fa-check mr-1"></i>Yes, Save</button>
            </div>
        </div>
    </div>

    <?php foreach ([
        'warning' => ['red', 'circle-exclamation', 'Please check the required fields.'],
        'success' => ['green', 'circle-check', 'Equipment information saved successfully!']
    ] as $type => [$color, $icon, $defaultMessage]): ?>
        <div id="toast-<?= $type ?>" role="alert" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out border-<?= $color ?>-200">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-<?= $color ?>-100 text-<?= $color ?>-600"><i class="fa-solid fa-<?= $icon ?> text-lg"></i></div>
            <div <?= $type === 'warning' ? 'id="toast-message"' : '' ?> class="ml-3 flex-1 text-sm font-medium"><?= ev($defaultMessage) ?></div>
            <button type="button" data-close="<?= $type ?>" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endforeach; ?>

    <script>
        const form = document.getElementById('equipmentForm'),
            modal = document.getElementById('equipmentModal'),
            warningToast = document.getElementById('toast-warning'),
            successToast = document.getElementById('toast-success'),
            toastMessage = document.getElementById('toast-message'),
            confirmButton = document.getElementById('confirmEquipment'),
            floorSelect = document.getElementById('floor'),
            roomSelect = document.getElementById('room'),
            roomMessage = document.getElementById('roomMessage');

        const initialFloor = <?= json_encode($equipment['floor']) ?>;
        const initialRoom = <?= json_encode($equipment['room']) ?>;

        let warningTimer, successTimer;
        let roomRequestId = 0;

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

        function showSuccessToast() {
            toggleToast(successToast, true);
            clearTimeout(successTimer);
            successTimer = setTimeout(() => toggleToast(successToast, false), 3000);
        }

        document.querySelectorAll('[data-close]').forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.close;
                clearTimeout(type === 'warning' ? warningTimer : successTimer);
                toggleToast(document.getElementById(`toast-${type}`), false);
            });
        });

        <?php if ($showSuccessToast): ?>showSuccessToast();
        <?php endif; ?>

        roomSelect.parentElement.addEventListener('click', function() {
            if (!floorSelect.value) {
                floorSelect.classList.remove('border-slate-300');
                floorSelect.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                showToast('Please select an Assigned Floor first.');
            }
        });

        floorSelect.addEventListener('change', function() {
            if (floorSelect.value) {
                floorSelect.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                floorSelect.classList.add('border-slate-300');
            }
        });

        form.addEventListener('submit', event => {
            event.preventDefault();
            const invalid = form.querySelector(':invalid');

            if (invalid) {
                const label = invalid.id ? form.querySelector(`label[for="${invalid.id}"]`) : null;
                showToast(`Please check the ${label ? label.textContent.replace('*', '').trim() : 'required fields'} field.`);
                invalid.focus();
                return;
            }

            const image = document.getElementById('image').files[0];
            if (image) {
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(image.type)) {
                    showToast('Only JPG, PNG, and WebP images are allowed.');
                    return;
                }
                if (image.size > 5 * 1024 * 1024) {
                    showToast('The image must not exceed 5 MB.');
                    return;
                }
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

        async function loadRooms(selectedRoom = '') {
            const floor = floorSelect.value;
            const requestId = ++roomRequestId;

            roomSelect.replaceChildren();

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = floor ? 'Loading rooms...' : 'Select a floor first';
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
                    throw new Error(rooms.error || 'Unable to fetch rooms.');
                }

                if (selectedRoom && !rooms.includes(selectedRoom)) {
                    rooms.push(selectedRoom);
                }

                roomSelect.replaceChildren();

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = rooms.length ? 'Select Room' : 'No rooms found';
                roomSelect.appendChild(defaultOption);

                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room;
                    option.textContent = room;
                    if (room === selectedRoom) option.selected = true;
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

        floorSelect.addEventListener('change', () => loadRooms());

        const imageInput = document.getElementById('image');
        const viewImageBtn = document.getElementById('viewImageBtn');
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const existingImage = <?= json_encode($equipment['image_url']) ?>;
        let previewUrl = null;

        function currentImageSrc() {
            return previewUrl || existingImage;
        }

        function syncViewButton() {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            const file = imageInput.files[0];
            if (file) previewUrl = URL.createObjectURL(file);

            viewImageBtn.classList.toggle('hidden', !currentImageSrc());
        }

        function closeImageModal() {
            imageModal.classList.add('hidden');
            imageModal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        imageInput.addEventListener('change', syncViewButton);
        form.addEventListener('reset', () => setTimeout(syncViewButton, 0));

        viewImageBtn.addEventListener('click', () => {
            const src = currentImageSrc();
            if (!src) return;

            modalImage.src = src;
            imageModal.classList.remove('hidden');
            imageModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        });

        imageModal.addEventListener('click', event => {
            if (event.target === imageModal) closeImageModal();
        });
        document.getElementById('closeImageModal').addEventListener('click', closeImageModal);
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !imageModal.classList.contains('hidden')) closeImageModal();
        });

        if (floorSelect.value) {
            loadRooms(floorSelect.value === initialFloor ? initialRoom : '');
        }
    </script>
</body>

</html>