<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$facilityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$sampleFacilities = [
    1 => [
        'id' => 1,
        'name' => 'Reception Hall',
        'image' => '../img/logo.png',
        'location' => 'Ground Floor',
        'year' => 2022,
        'year_estimated' => true,
        'status' => 'Good',
        'remarks' => 'Facility is in good condition.',
    ],
];

$facility = $sampleFacilities[$facilityId] ?? null;

if (!$facility) {
    http_response_code(404);
}

function escapeHtml($value): string
{
    $value = ($value === null || $value === '')
        ? 'N/A'
        : $value;

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$status = strtolower($facility['status'] ?? '');

$statusColors = [
    'good' => 'bg-green-100 text-green-700',
    'fair' => 'bg-yellow-100 text-yellow-700',
    'poor' => 'bg-red-100 text-red-700',
];

$statusClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-700';

$acquisitionYear = $facility['year'] ?? null;

$facilityAge = $acquisitionYear
    ? max(0, (int) date('Y') - (int) $acquisitionYear) . ' year(s)'
    : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Profile / Information</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>

    <?php include('sidebar.php'); ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">
            Facility Profile / Information
        </h1>
        <p class="mt-2 mb-3 text-gray-500"> View detailed information about this facility.</p>

        <a href="facilities_list.php"
            class="flex items-center font-bold text-[#103F6C]"
            style="text-decoration: none;">
            <i class="fa-sharp fa-solid fa-arrow-left px-3"></i>
            Back to Facilities List
        </a>

        <?php if (!$facility): ?>

            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-6 text-red-700">
                Facility not found.
            </div>

        <?php else: ?>

            <div class="ml-5 min-w-0 p-5">

                <div class="mt-2 w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-5 flex flex-wrap items-start gap-4">

                        <img
                            src="<?= escapeHtml($facility['image'] ?? '../img/logo.png') ?>"
                            alt="<?= escapeHtml($facility['name']) ?>"
                            class="h-28 w-38 shrink-0 rounded-md border border-gray-200 object-contain p-2 shadow-sm">

                        <div class="min-w-0 flex-1">

                            <h2 class="text-2xl font-extrabold">
                                <?= escapeHtml($facility['name']) ?>
                            </h2>

                            <p class="mt-1 text-sm font-bold">
                                <?= str_pad((string) $facility['id'], 4, '0', STR_PAD_LEFT) ?>
                            </p>

                            <span class="mt-2 inline-flex items-center gap-2 rounded-md px-3 py-1 text-sm font-bold <?= $statusClass ?>">
                                <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                                <?= escapeHtml($facility['status'] ?: 'Not assessed') ?>
                            </span>

                        </div>

                        <div class="flex flex-wrap gap-2">

                            <a href="edit_facility.php?id=<?= (int) $facility['id'] ?>"
                                class="inline-flex items-center gap-2 rounded-md bg-[#155B92] px-4 py-2 text-sm font-semibold text-white hover:bg-[#103F6C]">
                                <i class="fa-solid fa-pen"></i>
                                Edit Information
                            </a>

                            <a href="facility_logs.php?id=<?= (int) $facility['id'] ?>"
                                class="inline-flex items-center gap-2 rounded-md bg-[#155B92] px-4 py-2 text-sm font-semibold text-white hover:bg-[#103F6C]">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                Condition History
                            </a>
                        </div>
                    </div>

                    <section class="mb-3 rounded-md border border-slate-300 bg-white p-4">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                            <i class="fa-solid fa-circle-info"></i>
                            Facility Information
                        </h3>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="space-y-2 border-gray-200 md:border-r md:pr-6">
                                <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                    <span class="text-gray-500">Facility Name</span>
                                    <span class="text-gray-400">:</span>
                                    <span><?= escapeHtml($facility['name']) ?></span>
                                </div>

                                <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                    <span class="text-gray-500">Assigned Floor</span>
                                    <span class="text-gray-400">:</span>
                                    <span><?= escapeHtml($facility['location']) ?></span>
                                </div>

                
                            </div>

                            <div class="space-y-2">
                                <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                    <span class="text-gray-500">Current Status</span>
                                    <span class="text-gray-400">:</span>
                                    <span>
                                        <span class="inline-flex items-center gap-2 rounded-md px-3 py-1 text-xs font-bold <?= $statusClass ?>">
                                            <span class="h-2 w-2 rounded-full bg-current"></span>
                                            <?= escapeHtml($facility['status'] ?: 'Not assessed') ?>
                                        </span>
                                    </span>
                                </div>

                                <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                    <span class="text-gray-500">Facility Age</span>
                                    <span class="text-gray-400">:</span>
                                    <span><?= escapeHtml($facilityAge) ?></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="mb-3 grid grid-cols-1 items-stretch gap-3 lg:grid-cols-2">
                        <section class="min-w-0 rounded-md border border-slate-300 bg-white p-4">
                            <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                                <i class="fa-solid fa-calendar-days"></i>
                                Acquisition Information
                            </h3>

                            <div class="space-y-2">
                                <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                    <span class="text-gray-500">Acquisition Year</span>
                                    <span class="text-gray-400">:</span>
                                    <span><?= escapeHtml($acquisitionYear) ?></span>
                                </div>

                                <div class="grid grid-cols-[minmax(0,1fr)_12px_minmax(0,1.2fr)] gap-2 text-sm">
                                    <span class="text-gray-500">Estimated Year</span>
                                    <span class="text-gray-400">:</span>
                                    <span>
                                        <?= !empty($facility['year_estimated']) ? 'Yes' : 'No' ?>
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section class="min-w-0 rounded-md border border-slate-300 bg-white p-4">
                           <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#103F6C]">
                                <i class="fa-solid fa-note-sticky"></i>
                                Remarks
                            </h3>
                            <div class="min-h-28 flex-1 whitespace-pre-wrap rounded-md border border-gray-200 bg-gray-50 p-3 text-sm leading-relaxed text-gray-600"><?= escapeHtml($facility['remarks'] ?: 'No additional remarks recorded for this facility.') ?></div>
                        </section>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

</body>

</html>
