<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

//sample data lang to
$sampleEquipments = [
    1 => [
        'id' => 1,
        'name' => 'Printer',
        'asset_type' => 'Fixed Asset',
        'category' => 'IT Equipment',
        'quantity' => 1,
        'brand' => 'Canon',
        'model' => 'IP2770',
        'location' => '2nd Floor - Admin Room',
        'acquisition_year' => 2022,
        'is_estimated_year' => true,
        'cost_type' => 'Original cost',
        'amount' => 4500,
        'usage_schedule' => 'Daily',
        'hours_per_day' => 6,
        'condition' => 'Good',
        'date_acquired' => null,
        'remarks' => '',
    ],
];

$equipment = $sampleEquipments[$equipmentId] ?? null;

if (!$equipment) {
    http_response_code(404);
}

function escapeHtml($value): string
{
    $value = ($value === null || $value === '') ? 'N/A' : $value;
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>

    <?php include('sidebar.php'); ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">Equipment Profile / Information</h1>

        <p class="mt-2 mb-3 text-gray-500">
            View detailed information about this equipment.
        </p>

        <a href="equipments_list.php" class="flex items-center font-bold text-[#103F6C]" style="text-decoration: none;">
            <i class="fa-sharp fa-solid fa-arrow-left px-3"></i>Back to Equipments List
        </a>

        <?php if (!$equipment): ?>
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-6 text-red-700">
                Equipment not found.
            </div>
        <?php else:
            $acqYear = $equipment['acquisition_year']
                ?? (!empty($equipment['date_acquired']) ? date('Y', strtotime($equipment['date_acquired'])) : null);
            $assetAge = $acqYear ? max(0, (int) date('Y') - (int) $acqYear) . ' year(s)' : null;
            $amount = isset($equipment['amount']) && $equipment['amount'] !== ''
                ? '₱' . number_format((float) $equipment['amount'], 2)
                : null;
            $hours = isset($equipment['hours_per_day']) && $equipment['hours_per_day'] !== ''
                ? $equipment['hours_per_day'] . ' hrs'
                : null;
        ?>

        <div class="ml-5 min-w-0 p-5">
            <div class="mt-2 w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-5 flex flex-wrap items-start gap-4">
                    <img src="../img/logo.png" alt="<?= escapeHtml($equipment['name']) ?>"
                         class="h-28 w-38 shrink-0 rounded-md border border-gray-200 object-contain p-2 shadow-sm">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-2xl font-extrabold"><?= escapeHtml($equipment['name']) ?></h2>
                        <p class="mt-1 text-sm font-bold"><?= str_pad((string) $equipment['id'], 4, '0', STR_PAD_LEFT) ?></p>

                        <span class="mt-2 inline-flex items-center gap-2 rounded-md px-3 py-1 text-sm font-bold <?= $conditionClass ?>">
                            <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                            <?= escapeHtml($equipment['condition'] ?: 'Not assessed') ?>
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="edit_equipment.php" 
                                class="inline-flex items-center gap-2 rounded-md bg-[#155B92] px-4 py-2 text-sm font-semibold text-white hover:bg-[#103F6C]">
                            <i class="fa-solid fa-pen"></i> Edit Information
                        </a>
                        <a href="equipment_logs.php"
                                class="inline-flex items-center gap-2 rounded-md bg-[#155B92] px-4 py-2 text-sm font-semibold text-white hover:bg-[#103F6C]">
                            <i class="fa-solid fa-clock-rotate-left"></i> Condition History
                        </a>
                    </div>
                </div>

                <section class="mb-3 rounded-md border border-slate-300 bg-white p-4">
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                        <i class="fa-solid fa-circle-info"></i> Equipment Information
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-2 border-gray-200 md:border-r md:pr-6">
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Equipment Name</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['name']) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Asset Type</span><span class="text-gray-400">:</span>
                                <span class="capitalize"><?= escapeHtml($equipment['asset_type']) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Category</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['category'] ?? null) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Quantity</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['quantity'] ?? 1) ?></span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Brand</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['brand']) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Model</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['model']) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Assigned Office / Location</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['location']) ?></span>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="mb-3 grid grid-cols-1 items-stretch gap-3 lg:grid-cols-2">
                    <section class="min-w-0 rounded-md border border-slate-300 bg-white p-4">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                            <i class="fa-solid fa-peso-sign"></i> Acquisition and Financial Information
                        </h3>
                        <div class="space-y-2">
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Acquisition Year</span><span class="text-gray-400">:</span>
                                <span>
                                    <?= escapeHtml($acqYear) ?>
                                    <?php if (!empty($equipment['is_estimated_year'])): ?>
                                        <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Estimated</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Cost Type</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['cost_type'] ?? null) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Amount</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($amount) ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="min-w-0 rounded-md border border-slate-300 bg-white p-4">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                            <i class="fa-solid fa-gear"></i> Usage
                        </h3>
                        <div class="space-y-2">
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Usage Schedule</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($equipment['usage_schedule'] ?? null) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Hours of Use per Day</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($hours) ?></span>
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                <span class="text-gray-500">Current Asset Age</span><span class="text-gray-400">:</span>
                                <span><?= escapeHtml($assetAge) ?></span>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="grid grid-cols-1 items-stretch gap-3">
                    <section class="flex min-w-0 flex-col rounded-md border border-slate-300 bg-white p-4">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                            <i class="fa-solid fa-note-sticky"></i> Remarks
                        </h3>
                        <div class="min-h-28 flex-1 whitespace-pre-wrap rounded-md border border-gray-200 bg-gray-50 p-3 text-sm leading-relaxed text-gray-600"><?= escapeHtml($equipment['remarks'] ?: 'No additional remarks recorded for this equipment.') ?></div>
                    </section>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

</body>
</html>