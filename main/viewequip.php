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
    <title>Equipment Profile | LifeCycleTrack</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body class="bg-gray-50">
    <?php include('sidebar.php'); ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold text-gray-800">
            Equipment Profile / Information
        </h1>

        <p class="mt-2 mb-3 text-gray-500">
            View detailed information about this equipment.
        </p>

        <a href="equipments_list.php"
           class="inline-flex items-center font-bold text-[#103F6C] no-underline hover:underline">
            <i class="fa-solid fa-arrow-left mr-3"></i>
            Back to Equipment List
        </a>

        <?php if (!$equipment): ?>
            <div class="mt-8 rounded-lg border border-red-200 bg-red-50 p-6 text-red-700">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                Equipment not found. Please return to the equipment list and select an equipment item.
            </div>
        <?php else: ?>

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

        <?php endif; ?>
    </main>
</body>
</html>