<?php
require_once __DIR__ . '/_app.php';
require_login();

$message = '';
$error = '';

$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$equipment = [
    'id'            => $equipmentId,
    'name'          => 'Printer',
    'kind'          => 'Movable',
    'category'      => 'IT equipment',
    'brand'         => 'Canon',
    'model'         => 'IP2770',
    'location'      => 'Room 1',
    'quantity'      => 1,
    'year'          => 2022,
    'year_estimated'=> 1,
    'basis'         => 'Original',
    'cost'          => 4500,
    'sched'         => 'Standard',
    'hours_per_day' => 6,
    'status'        => 'Good',
    'remarks'       => '',
];

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

$assetAge = !empty($equipment['year'])
    ? max(0, (int) date('Y') - (int) $equipment['year']) . ' year(s)'
    : '';

$lockedInput = 'w-full cursor-not-allowed rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500';
$lockIcon = '<i class="fa-solid fa-lock ml-1 text-xs text-gray-400" title="Locked - cannot be edited"></i>';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">
            Edit Equipment
        </h1>

        <p class="mt-2 mb-3 text-gray-600">
            Update equipment information
        </p>
        <a href="viewequip.php?id=<?= ev($equipment['id']) ?>" class="text-[#103F6C] font-bold flex items-center mb-5">
            <i class="fa-solid fa-arrow-left px-3"></i>Back to Equipment Profile
        </a>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= ev($equipment['id']) ?>">
            <?php if (function_exists('csrf_field')) { echo csrf_field(); } elseif (function_exists('csrf_token')) { echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">'; } ?>

        <!--et oyung sa equip info-->
            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]"> Equipment Information </h2>
            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>
                    Fields marked with <span class="text-red-500">*</span> are required.
                    Fields with <i class="fa-solid fa-lock text-xs text-gray-400"></i> are locked and cannot be edited.
                </p>
            </div>
            
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" placeholder="e.g. Computer, Printer" required value="<?= ev($equipment['name']) ?>"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="kind" class="mb-1 block text-sm font-medium text-gray-700">Asset Type <?= $lockIcon ?></label>
                    <select id="kind" disabled class="<?= $lockedInput ?>">
                        <option value="">Select asset type</option>
                        <option value="Movable"<?= sel($equipment['kind'], 'Movable') ?>>Movable asset</option>
                        <option value="Fixed"<?= sel($equipment['kind'], 'Fixed') ?>>Fixed asset</option>
                    </select>
                    <input type="hidden" name="kind" value="<?= ev($equipment['kind']) ?>">
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Equipment Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select category</option>
                        <option<?= sel($equipment['category'], 'Fire equipment') ?>>Fire equipment</option>
                        <option<?= sel($equipment['category'], 'IT equipment') ?>>IT equipment</option>
                        <option<?= sel($equipment['category'], 'Office, facility or utility equipment') ?>>Office, facility or utility equipment</option>
                    </select>
                </div>

                <div>
                    <label for="brand" class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                    <input id="brand" name="brand" type="text" placeholder="e.g. Canon" value="<?= ev($equipment['brand']) ?>"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="model" class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                    <input id="model" name="model" type="text" placeholder="e.g. IP2770" value="<?= ev($equipment['model']) ?>"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Office / Location <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select location</option>
                        <!--eto di pako sure dito -->
                        <optgroup label="1st Floor">
                            <option<?= sel($equipment['location'], 'Room 1') ?>>Room 1</option>
                            <option<?= sel($equipment['location'], 'Room 2') ?>>Room 2</option>
                            <option<?= sel($equipment['location'], 'Room 3') ?>>Room 3</option>
                            <option<?= sel($equipment['location'], 'Room 4') ?>>Room 4</option>
                        </optgroup>
                        <optgroup label="2nd Floor">
                            <option>Room 1</option>
                            <option>Room 2</option>
                            <option>Room 3</option>
                            <option>Room 4</option>
                        </optgroup>
                         <optgroup label="3rd Floor">
                            <option>Room 1</option>
                            <option>Room 2</option>
                            <option>Room 3</option>
                            <option>Room 4</option>
                        </optgroup>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                        <input id="quantity" name="quantity" type="number" min="1" step="1" value="<?= ev($equipment['quantity']) ?>" placeholder="e.g. 30"
                            class="w-60 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500">For multiple identical items, such as chairs or tables.</p>
                </div>
            </div>

            <!-- eto yung sa  acquisiton -->
            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition and Financial Information <?= $lockIcon ?></h2>
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">

                <div>
                    <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year</label>
                    <input id="year" name="year" type="number" readonly value="<?= ev($equipment['year']) ?>" placeholder="YYYY"
                        class="<?= $lockedInput ?>">
                    <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-500">
                        <input id="yearEst" type="checkbox" disabled <?= !empty($equipment['year_estimated']) ? 'checked' : '' ?>>
                        <?php if (!empty($equipment['year_estimated'])): ?>
                            <input type="hidden" name="year_estimated" value="1">
                        <?php endif; ?>
                        Estimated year
                    </label>
                </div>

                <div>
                    <label for="basis" class="mb-1 block text-sm font-medium text-gray-700">Cost Type</label>
                    <select id="basis" disabled class="<?= $lockedInput ?>">
                        <option value="Original"<?= sel($equipment['basis'], 'Original') ?>>Original cost</option>
                        <option value="Current"<?= sel($equipment['basis'], 'Current') ?>>Current value (original unknown)</option>
                    </select>
                    <input type="hidden" name="basis" value="<?= ev($equipment['basis']) ?>">
                </div>

                <div>
                    <label for="cost" class="mb-1 block text-sm font-medium text-gray-700">Amount (₱)</label>
                    <input id="cost" name="cost" type="number" readonly value="<?= ev($equipment['cost']) ?>"
                        class="<?= $lockedInput ?>">
                </div>
            </div>

            <!-- usage -->
            <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">
            <div class="xl:col-span-3">
            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Usage</h2>
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0">

                <div>
                    <label for="sched" class="mb-1 block text-sm font-medium text-gray-700">Usage Schedule <span class="text-red-500">*</span></label>
                    <select id="sched" name="sched" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select schedule</option>
                        <option value="Standard"<?= sel($equipment['sched'], 'Standard') ?>>Standard (8 hours)</option>
                        <option value="Continuous"<?= sel($equipment['sched'], 'Continuous') ?>>Continuous (24 hours)</option>
                        <option value="Seasonal"<?= sel($equipment['sched'], 'Seasonal') ?>>Emergency / Seasonal</option>
                    </select>
                </div>

                <div>
                    <label for="hours" class="mb-1 block text-sm font-medium text-gray-700">Hours of Use per Day</label>
                    <input id="hours" name="hours_per_day" type="number" min="0" max="24" step="0.5" value="<?= ev($equipment['hours_per_day']) ?>"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Current Asset Age <?= $lockIcon ?></label>
                    <input id="age" type="text" disabled value="<?= ev($assetAge) ?>"
                        class="w-full rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500">
                </div>
            </div>
            </div>

            <div class="xl:col-span-2">
            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0 xl:grid-cols-2">

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status <span class="text-red-500">*</span></label>
                    <select id="status" name="status" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <optgroup label="Select Status">
                            <option<?= sel($equipment['status'], 'Good') ?>>Good</option>
                            <option<?= sel($equipment['status'], 'Fair') ?>>Fair</option>
                            <option<?= sel($equipment['status'], 'Poor') ?>>Poor</option>
                        </optgroup>
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-1">
                    <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="2" placeholder="Add  remarks.."
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"><?= ev($equipment['remarks']) ?></textarea>
                </div>
            </div>

            </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="equipment_profile.php?id=<?= ev($equipment['id']) ?>" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700 cursor-pointer">Cancel</a>
                <button type="reset" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700 cursor-pointer">Undo</button>
                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white"> <i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes</button>

                <!--magdadagdag pako ng modal for confirmation and toast-->
            </div>
        </form>
    </main>

</body>

</html>