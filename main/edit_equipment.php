
<?php
require_once __DIR__ . '/_app.php';
require_login();

$message = '';
$error = '';

$equipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$equipment = [
    'id'             => $equipmentId,
    'name'           => 'Printer',
    'kind'           => 'Movable',
    'category'       => 'IT equipment',
    'brand'          => 'Canon',
    'model'          => 'IP2770',
    'location'       => 'Room 1',
    'quantity'       => 1,
    'year'           => 2022,
    'year_estimated' => 1,
    'basis'          => 'Original',
    'cost'           => 4500,
    'sched'          => 'Standard',
    'hours_per_day'  => 6,
    'status'         => 'Good',
    'remarks'        => '',
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

<body class="font-['Outfit']">
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">Edit Equipment</h1>
        <p class="mt-2 mb-3 text-gray-600">Update equipment information</p>

        <nav aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2" style="color:#0E3A63;font-size:18px;">
                <li><a href="equipments_list.php" class="font-medium no-underline hover:underline"><i class="fa-solid fa-list mr-1"></i> Back to List</a></li>
                <li><i class="fa-solid fa-chevron-right mx-2 text-xs"></i><a href="viewequip.php?id=<?= ev($equipment['id']) ?>" class="font-medium no-underline hover:underline">Profile/Information</a></li>
                <li><i class="fa-solid fa-chevron-right mx-2 text-xs"></i><a href="edit_equipment.php?id=<?= ev($equipment['id']) ?>" class="font-medium no-underline hover:underline">Edit Information</a></li>
            </ol>
        </nav>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= ev($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700"><?= ev($message) ?></div>
        <?php endif; ?>

        <form id="equipmentForm" method="POST" action="" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= ev($equipment['id']) ?>">

            <?php
            if (function_exists('csrf_field')) {
                echo csrf_field();
            } elseif (function_exists('csrf_token')) {
                echo '<input type="hidden" name="csrf_token" value="' . ev(csrf_token()) . '">';
            }
            ?>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Equipment Information</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>Fields marked with <span class="text-red-500">*</span> are required. Some fields are blocked and cannot be edited.</p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" placeholder="e.g. Computer, Printer" required value="<?= ev($equipment['name']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="kind" class="mb-1 block text-sm font-medium text-gray-700">Asset Type</label>
                    <select id="kind" disabled class="<?= $lockedInput ?>">
                        <option value="">Select asset type</option>
                        <option value="Movable"<?= sel($equipment['kind'], 'Movable') ?>>Movable asset</option>
                        <option value="Fixed"<?= sel($equipment['kind'], 'Fixed') ?>>Fixed asset</option>
                    </select>
                    <input type="hidden" name="kind" value="<?= ev($equipment['kind']) ?>">
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Equipment Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select category</option>
                        <option<?= sel($equipment['category'], 'Fire equipment') ?>>Fire equipment</option>
                        <option<?= sel($equipment['category'], 'IT equipment') ?>>IT equipment</option>
                        <option<?= sel($equipment['category'], 'Office, facility or utility equipment') ?>>Office, facility or utility equipment</option>
                    </select>
                </div>

                <div>
                    <label for="brand" class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                    <input id="brand" name="brand" type="text" placeholder="e.g. Canon" value="<?= ev($equipment['brand']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="model" class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                    <input id="model" name="model" type="text" placeholder="e.g. IP2770" value="<?= ev($equipment['model']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Office / Location <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select location</option>
                        <optgroup label="1st Floor">
                            <option<?= sel($equipment['location'], 'Room 1') ?>>Room 1</option>
                            <option<?= sel($equipment['location'], 'Room 2') ?>>Room 2</option>
                            <option<?= sel($equipment['location'], 'Room 3') ?>>Room 3</option>
                            <option<?= sel($equipment['location'], 'Room 4') ?>>Room 4</option>
                        </optgroup>
                        <optgroup label="2nd Floor">
                            <option<?= sel($equipment['location'], '2nd Floor - Room 1') ?>>2nd Floor - Room 1</option>
                            <option<?= sel($equipment['location'], '2nd Floor - Room 2') ?>>2nd Floor - Room 2</option>
                            <option<?= sel($equipment['location'], '2nd Floor - Room 3') ?>>2nd Floor - Room 3</option>
                            <option<?= sel($equipment['location'], '2nd Floor - Room 4') ?>>2nd Floor - Room 4</option>
                        </optgroup>
                        <optgroup label="3rd Floor">
                            <option<?= sel($equipment['location'], '3rd Floor - Room 1') ?>>3rd Floor - Room 1</option>
                            <option<?= sel($equipment['location'], '3rd Floor - Room 2') ?>>3rd Floor - Room 2</option>
                            <option<?= sel($equipment['location'], '3rd Floor - Room 3') ?>>3rd Floor - Room 3</option>
                            <option<?= sel($equipment['location'], '3rd Floor - Room 4') ?>>3rd Floor - Room 4</option>
                        </optgroup>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="<?= ev($equipment['quantity']) ?>" placeholder="e.g. 30" class="w-60 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">For multiple identical items, such as chairs or tables.</p>
                </div>
            </div>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition and Financial Information</h2>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year</label>
                    <input id="year" name="year" type="number" readonly value="<?= ev($equipment['year']) ?>" placeholder="YYYY" class="<?= $lockedInput ?>">
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
                    <input id="cost" name="cost" type="number" readonly value="<?= ev($equipment['cost']) ?>" class="<?= $lockedInput ?>">
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">
                <div class="xl:col-span-3">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Usage</h2>
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0">
                        <div>
                            <label for="sched" class="mb-1 block text-sm font-medium text-gray-700">Usage Schedule <span class="text-red-500">*</span></label>
                            <select id="sched" name="sched" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <option value="">Select schedule</option>
                                <option value="Standard"<?= sel($equipment['sched'], 'Standard') ?>>Standard (8 hours)</option>
                                <option value="Continuous"<?= sel($equipment['sched'], 'Continuous') ?>>Continuous (24 hours)</option>
                                <option value="Seasonal"<?= sel($equipment['sched'], 'Seasonal') ?>>Emergency / Seasonal</option>
                            </select>
                        </div>

                        <div>
                            <label for="hours" class="mb-1 block text-sm font-medium text-gray-700">Hours of Use per Day</label>
                            <input id="hours" name="hours_per_day" type="number" min="0" max="24" step="0.5" value="<?= ev($equipment['hours_per_day']) ?>" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        </div>

                        <div>
                            <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Current Asset Age</label>
                            <input id="age" type="text" disabled value="<?= ev($assetAge) ?>" class="w-full rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500">
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0 xl:grid-cols-2">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <option value="">Select status</option>
                                <option<?= sel($equipment['status'], 'Good') ?>>Good</option>
                                <option<?= sel($equipment['status'], 'Fair') ?>>Fair</option>
                                <option<?= sel($equipment['status'], 'Poor') ?>>Poor</option>
                            </select>
                        </div>

                        <div class="md:col-span-2 xl:col-span-1">
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2" placeholder="Add remarks.." class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"><?= ev($equipment['remarks']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="equipment_profile.php?id=<?= ev($equipment['id']) ?>" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Cancel</a>
                <button type="reset" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Undo</button>
                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white"><i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes</button>
            </div>
        </form>
    </main>

    <div id="equipmentConfirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i>
            </div>
            <h2 class="mb-2 text-xl font-bold text-gray-800">Save Changes</h2>
            <p class="mb-6 text-sm text-gray-600">Are you sure you want to save the changes to this equipment?</p>
            <div class="flex justify-center gap-3">
                <button type="button" id="cancelEquipmentConfirm" class="rounded-lg border border-slate-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-100">Cancel</button>
                <button type="button" id="confirmEquipmentSave" class="cursor-pointer rounded-lg bg-[#155B92] px-5 py-2 text-sm text-white hover:bg-[#10456f]">Yes, Save</button>
            </div>
        </div>
    </div>

    <div id="toast-warning" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-exclamation text-lg"></i>
        </div>
        <div id="toast-message" class="ml-3 flex-1 text-sm font-medium">Please check the required fields.</div>
        <button type="button" id="close-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="toast-success" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div class="ml-3 flex-1 text-sm font-medium">Equipment information saved successfully!</div>
        <button type="button" id="close-success-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <script>
        const form = document.getElementById('equipmentForm');
        const modal = document.getElementById('equipmentConfirmModal');
        const warningToast = document.getElementById('toast-warning');
        const successToast = document.getElementById('toast-success');
        const toastMessage = document.getElementById('toast-message');
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

        function showSuccessToast() {
            toggleToast(successToast, true);
            clearTimeout(successTimer);
            successTimer = setTimeout(() => toggleToast(successToast, false), 3000);
        }

        document.getElementById('close-toast').addEventListener('click', () => toggleToast(warningToast, false));
        document.getElementById('close-success-toast').addEventListener('click', () => toggleToast(successToast, false));

        if (sessionStorage.getItem('equipmentUpdatedToast') === 'true') {
            sessionStorage.removeItem('equipmentUpdatedToast');
            showSuccessToast();
        }

        form.addEventListener('submit', event => {
            event.preventDefault();

            const requiredFields = [...form.querySelectorAll('[required]')];
            const invalidField = requiredFields.find(field => !field.value.trim() || !field.checkValidity());

            if (invalidField) {
                const label = invalidField.id && form.querySelector(`label[for="${invalidField.id}"]`);
                showToast(`Please check the ${label ? label.textContent.replace('*', '').trim() : 'required fields'} field.`);
                invalidField.focus();
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('cancelEquipmentConfirm').addEventListener('click', closeModal);

        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal();
        });

        document.getElementById('confirmEquipmentSave').addEventListener('click', () => {
            sessionStorage.setItem('equipmentUpdatedToast', 'true');
            form.submit();
        });
    </script>
</body>
</html>
