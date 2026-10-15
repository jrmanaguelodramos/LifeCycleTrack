<?php
require_once __DIR__ . '/_app.php';
require_login();

$message = '';
$error = '';
$facilityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;

$facility = [
    'id' => $facilityId,
    'name' => 'Reception Hall',
    'location' => 'Ground Floor',
    'year' => 2022,
    'year_estimated' => 1,
    'status' => 'Good',
    'remarks' => '',
];

if (!function_exists('ev')) {
    function ev($value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sel')) {
    function sel($saved, $option): string {
        return (string) $saved === (string) $option ? ' selected' : '';
    }
}

$facilityAge = !empty($facility['year'])
    ? max(0, (int) date('Y') - (int) $facility['year']) . ' year(s)'
    : 'N/A';

$lockedInput = 'w-full cursor-not-allowed rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500';
$lockIcon = '<i class="fa-solid fa-lock ml-1 text-xs text-gray-400" title="Locked - cannot be edited"></i>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Facility</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">Edit Facility</h1>
        <p class="mt-2 mb-3 text-gray-600">Update facility information</p>

        <nav aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2" style="color: #0E3A63; font-size:18px;">
                <li><a href="facilities_list.php" class="font-medium no-underline hover:underline"><i class="fa-solid fa-list mr-1"></i> Back to List</a></li>
                <li><i class="fa-solid fa-chevron-right mx-2 text-xs"></i><a href="viewfacility.php?id=<?= (int) $facility['id'] ?>" class="font-medium no-underline hover:underline">Profile/Information</a></li>
                <li><i class="fa-solid fa-chevron-right mx-2 text-xs"></i><a href="edit_facility.php?id=<?= (int) $facility['id'] ?>" class="font-medium no-underline hover:underline">Edit Information</a></li>
            </ol>
        </nav>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= ev($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700"><?= ev($message) ?></div>
        <?php endif; ?>

        <form id="facilityForm" method="POST" action="" enctype="multipart/form-data" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int) $facility['id'] ?>">

            <?php
            if (function_exists('csrf_field')) {
                echo csrf_field();
            } elseif (function_exists('csrf_token')) {
                echo '<input type="hidden" name="csrf_token" value="' . ev(csrf_token()) . '">';
            }
            ?>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Facility Information</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>Fields marked with <span class="text-red-500">*</span> are required. Some fields are blocked and cannot be edited.</p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Facility Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" value="<?= ev($facility['name']) ?>" placeholder="e.g. Comfort Room, Multi-Purpose Hall" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Facility Image</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP.</p>
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Floor <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select location</option>
                        <optgroup label="Floor">
                            <option value="Ground Floor"<?= sel($facility['location'], 'Ground Floor') ?>>Ground Floor</option>
                            <option value="2nd Floor"<?= sel($facility['location'], '2nd Floor') ?>>2nd Floor</option>
                            <option value="3rd Floor"<?= sel($facility['location'], '3rd Floor') ?>>3rd Floor</option>
                            <option value="4th Floor"<?= sel($facility['location'], '4th Floor') ?>>4th Floor</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">
                <div class="xl:col-span-2">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition Date</h2>
                    <div class="mb-6 xl:mb-0">
                        <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                        <input id="year" name="year" type="number" min="1970" max="<?= date('Y') ?>" value="<?= ev($facility['year']) ?>" placeholder="YYYY" required class="w-full max-w-xs rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">

                        <label class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                            <input id="yearEst" name="year_estimated" type="checkbox" value="1" <?= !empty($facility['year_estimated']) ? 'checked' : '' ?> class="rounded border-slate-300 text-[#155B92]">
                            Estimated year
                        </label>

                        <p class="mt-1 text-xs text-gray-500">Check this if the exact acquisition year is unknown.</p>
                    </div>
                </div>

                <div class="xl:col-span-3">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>

                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <option value="Good"<?= sel($facility['status'], 'Good') ?>>Good</option>
                                <option value="Fair"<?= sel($facility['status'], 'Fair') ?>>Fair</option>
                                <option value="Poor"<?= sel($facility['status'], 'Poor') ?>>Poor</option>
                            </select>
                        </div>

                        <div>
                            <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Facility Age</label>
                            <input id="age" type="text" readonly value="<?= ev($facilityAge) ?>" class="<?= $lockedInput ?>">
                        </div>

                        <div>
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="3" placeholder="Add remarks..." class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"><?= ev($facility['remarks']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="view_facility.php?id=<?= (int) $facility['id'] ?>" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Cancel</a>
                <button type="reset" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Undo</button>
                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white hover:bg-[#103F6C]">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Save Changes
                </button>
            </div>
        </form>
    </main>

    <div id="facilityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i>
            </div>
            <h2 class="mb-2 text-xl font-bold text-gray-800">Confirm Changes</h2>
            <p class="mb-6 text-sm text-gray-500">Are you sure you want to save these facility changes?</p>
            <div class="flex justify-center gap-3">
                <button type="button" id="cancelFacility" class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fa-solid fa-xmark mr-1"></i> Cancel
                </button>
                <button type="button" id="confirmFacility" class="cursor-pointer rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]">
                    <i class="fa-solid fa-check mr-1"></i> Yes, Save
                </button>
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
        <div class="ml-3 flex-1 text-sm font-medium">Facility information saved successfully!</div>
        <button type="button" id="close-success-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <script>
        const form = document.getElementById('facilityForm');
        const modal = document.getElementById('facilityModal');
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

        if (sessionStorage.getItem('facilityUpdatedToast') === 'true') {
            sessionStorage.removeItem('facilityUpdatedToast');
            showSuccessToast();
        }

        form.addEventListener('submit', event => {
            event.preventDefault();
            const invalidField = form.querySelector(':invalid');

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

        document.getElementById('cancelFacility').addEventListener('click', closeModal);
        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal();
        });

        document.getElementById('confirmFacility').addEventListener('click', () => {
            sessionStorage.setItem('facilityUpdatedToast', 'true');
            form.submit();
        });
    </script>
</body>
</html>
