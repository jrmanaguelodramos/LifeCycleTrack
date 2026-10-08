<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['access_token'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get equipment ID from the URL
$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$equipmentId) {
    header('Location: equipments_list.php');
    exit;
}

// Retrieve equipment information from PostgreSQL
$stmt = $pdo->prepare(
    'SELECT id, name, asset_type, location, "condition",
            brand, model, date_acquired, remarks
     FROM assets
     WHERE id = :id
       AND asset_type = :asset_type'
);

$stmt->execute([
    'id' => $equipmentId,
    'asset_type' => 'equipment'
]);

$equipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipment) {
    http_response_code(404);
}

function escapeHtml($value): string
{
    return htmlspecialchars((string) ($value ?? 'N/A'), ENT_QUOTES, 'UTF-8');
}

$condition = strtolower($equipment['condition'] ?? '');

$conditionColors = [
    'good' => 'bg-green-100 text-green-700',
    'fair' => 'bg-yellow-100 text-yellow-700',
    'poor' => 'bg-red-100 text-red-700'
];

$conditionClass = $conditionColors[$condition] ?? 'bg-gray-100 text-gray-700';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Profile / Information</title>
    <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>

    <?php include('sidebar.php'); ?>

    <main class ="ml-64 p-8" >
        <h1 class="text-3xl font-bold ">
        Equipment Profile / Information
        </h1>

        <p class="mt-2 mb-3 text-gray-500">
            View detailed information about this equipment.
        </p>
        <a href="equipments_list.php" class="text-[#103F6C] font-bold flex items-center style='text-decoration: none;'">
            <i class="fa-sharp fa-solid fa-arrow-left px-3"></i>Back to Equipments List
        </a>
        
        <div class="ml-10 min-w-0 p-5">

        <div class="mt-2 w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-5 flex flex-wrap items-start gap-4">
                <img
                    src="../img/logo.png"
                    alt="Printing Machine"
                    class="h-28 w-38 shrink-0 rounded-md border border-gray-200 object-contain p-2 shadow-sm">

                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-extrabold">
                        Printer
                    </h2>

                    <p class="mt-1 text-sm font-bold">
                        0001
                    </p>

                    <span class="mt-2 inline-flex items-center gap-2 rounded-md bg-green-100 px-3 py-1 text-sm font-bold text-green-800">
                        <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                        Good
                    </span>

                    <p class="mt-2 text-sm text-gray-600">
                        Printer for document printing
                    </p>

                </div>

                <div class="flex flex-wrap gap-2">

                    <button
                        type="button"
                        id="openFacilityModal"
                        class="inline-flex items-center gap-2 rounded-md bg-[#155B92] px-4 py-2 text-sm font-semibold text-white hover:bg-[#103F6C]">
                        <i class="fa-solid fa-pen"></i>
                        Edit Information
                    </button>

                    <button
                        type="button"
                        onclick="viewConditionHistory()"
                        class="inline-flex items-center gap-2 rounded-md bg-[#155B92] px-4 py-2 text-sm font-semibold text-white hover:bg-[#103F6C]">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Condition History
                    </button>

                </div>

            </div>

            <section class="mb-3 rounded-md border border-slate-300 bg-white p-4">

                <h3 class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <i class="fa-solid fa-building-columns"></i>
                    Basic Information
                </h3>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="space-y-2 border-gray-200 md:border-r md:pr-6">

                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Category</span>
                            <span class="text-gray-400">:</span>
                            <span>Facility</span>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Brand</span>
                            <span class="text-gray-400">:</span>
                            <span>Buckshot</span>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Model</span>
                            <span class="text-gray-400">:</span>
                            <span>420</span>
                        </div>

                    </div>

                    <div class="space-y-2">
                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Located at</span>
                            <span class="text-gray-400">:</span>
                            <span>Front Office</span>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Date Acquired</span>
                            <span class="text-gray-400">:</span>
                            <span>September 10, 2022</span>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Warranty</span>
                            <span class="text-gray-400">:</span>
                            <span>September 10, 2027</span>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                            <span class="text-gray-500">Assigned to</span>
                            <span class="text-gray-400">:</span>
                            <span>Front Office</span>
                        </div>

                    </div>

                </div>
            </section>

            <div class="grid grid-cols-1 items-stretch gap-3 lg:grid-cols-2">
                <section class="min-w-0 rounded-md border border-slate-300 bg-white p-4">

                    <h3 class="mb-4 flex items-center gap-2 text-lg font-bold">
                        <i class="fa-solid fa-gear"></i>
                        Additional Details
                    </h3>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="space-y-2 border-gray-200 sm:border-r sm:pr-4">

                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1fr)] gap-2 text-sm">
                                <span class="text-gray-500">Condition</span>
                                <span class="text-gray-400">:</span>
                                <span>
                                    <span class="rounded bg-green-100 px-2 py-1 text-xs font-bold text-green-800">
                                        Good
                                    </span>
                                </span>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1fr)] gap-2 text-sm">
                                <span class="text-gray-500">Last Maintenance</span>
                                <span class="text-gray-400">:</span>
                                <span>May 15, 2026</span>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1fr)] gap-2 text-sm">
                                <span class="text-gray-500">Remarks</span>
                                <span class="text-gray-400">:</span>
                                <span>None</span>
                            </div>

                        </div>

                        <div class="space-y-2">
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1fr)] gap-2 text-sm">
                                <span class="text-gray-500">Supplier</span>
                                <span class="text-gray-400">:</span>
                                <span>Spigol</span>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1fr)] gap-2 text-sm">
                                <span class="text-gray-500">ID Number</span>
                                <span class="text-gray-400">:</span>
                                <span>011</span>
                            </div>

                        </div>

                    </div>
                </section>

                <section class="flex min-w-0 flex-col rounded-md border border-slate-300 bg-white p-4">
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-bold">
                        <i class="fa-solid fa-note-sticky"></i>
                        Remarks
                    </h3>

                    <div class="min-h-28 flex-1 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm leading-relaxed text-gray-600">
                        No additional remarks recorded for this equipment.
                    </div>
                </section>
            </div>
        </div>
</div>


        <?php
            $modal = __DIR__ . '/edit_equipment.php';

            if (file_exists($modal)) {
                include $modal;
            } else {
                echo "File NOT found: " . $modal;
        }?>

            <!-- Equipment Header -->
            <section class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-blue-50 text-[#103F6C]">
                            <i class="fa-solid fa-toolbox text-3xl"></i>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">
                                Equipment ID: #<?= escapeHtml($equipment['id']) ?>
                            </p>
                            <h2 class="text-2xl font-bold text-gray-800">
                                <?= escapeHtml($equipment['name']) ?>
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                <?= escapeHtml($equipment['brand']) ?>
                                <?= escapeHtml($equipment['model']) ?>
                            </p>
                        </div>
                    </div>

                    <div>
                        <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold <?= $conditionClass ?>">
                            <?= escapeHtml($equipment['condition'] ?: 'Not assessed') ?>
                        </span>
                    </div>
                </div>
            </section>

            <!-- Equipment Information -->
            <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-xl font-bold text-gray-800">
                    <i class="fa-solid fa-circle-info mr-2 text-[#103F6C]"></i>
                    Equipment Information
                </h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                    <div>
                        <p class="text-sm text-gray-500">Equipment Name</p>
                        <p class="mt-1 font-semibold text-gray-800">
                            <?= escapeHtml($equipment['name']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Equipment ID</p>
                        <p class="mt-1 font-semibold text-gray-800">
                            <?= escapeHtml($equipment['id']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Category</p>
                        <p class="mt-1 font-semibold capitalize text-gray-800">
                            <?= escapeHtml($equipment['asset_type']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Brand</p>
                        <p class="mt-1 font-semibold text-gray-800">
                            <?= escapeHtml($equipment['brand']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Model</p>
                        <p class="mt-1 font-semibold text-gray-800">
                            <?= escapeHtml($equipment['model']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Location</p>
                        <p class="mt-1 font-semibold text-gray-800">
                            <?= escapeHtml($equipment['location']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Date Acquired</p>
                        <p class="mt-1 font-semibold text-gray-800">
                            <?= escapeHtml($equipment['date_acquired']) ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Current Condition</p>
                        <p class="mt-1 font-semibold capitalize text-gray-800">
                            <?= escapeHtml($equipment['condition'] ?: 'Not assessed') ?>
                        </p>
                    </div>

                </div>
            </section>

            <!-- Remarks -->
            <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-3 text-xl font-bold text-gray-800">
                    <i class="fa-solid fa-clipboard-list mr-2 text-[#103F6C]"></i>
                    Remarks / Additional Information
                </h3>

                <p class="whitespace-pre-wrap text-gray-600"><?= escapeHtml($equipment['remarks'] ?: 'No remarks available.') ?></p>
            </section>

    </main>

</body>
</html>
