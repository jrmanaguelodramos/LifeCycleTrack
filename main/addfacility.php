<?php
require_once __DIR__ . '/_app.php';
require_login();

$message = '';
$error = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Facility</title>
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
                <li><a href="facilities_list.php" class="mr-2 block max-w-[200px] truncate no-underline hover:underline"><i class="fa-regular fa-building mr-2"></i>Facilities List</a></li>
                <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right mr-2"></i></li>
                <li aria-current="page" class="cursor-pointer font-medium text-blue-900">Add Facility</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold">Add Facility</h1>
        <p class="mb-3 mt-2 text-gray-600">Register new facility</p>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="add">

            <?php if (function_exists('csrf_field')) { echo csrf_field(); } elseif (function_exists('csrf_token')) { echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">'; } ?>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Facility Information</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>Fields marked with <span class="text-red-500">*</span> are required</p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Facility Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" placeholder="e.g. Comfort Room, Multi-Purpose Hall" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Facility Image</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">Upload a facility image (JPG, PNG, or WebP).</p>
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Floor <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select location</option>
                        <optgroup label="Floor">
                            <option>Ground Floor</option>
                            <option>2nd Floor</option>
                            <option>3rd Floor</option>
                            <option>4th Floor</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div>
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition Date</h2>

                    <div>
                        <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                        <input id="year" name="year" type="number" required min="1970" max="<?= date('Y') ?>" placeholder="YYYY" class="w-70 max-w-md rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">

                        <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-600">
                            <input id="yearEst" name="year_estimated" type="checkbox" value="1">
                            Estimated year
                        </label>
                    </div>
                </div>

                <div>
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <optgroup label="Select Status">
                                    <option>Good</option>
                                    <option>Fair</option>
                                    <option>Poor</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="5" placeholder="Add remarks..." class="min-h-32 w-full resize-y rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <button type="reset" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Clear</button>
                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white">Add Facility</button>
            </div>
        </form>
    </main>

    <div id="facilityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i>
            </div>
            <h2 class="mb-2 text-xl font-bold text-gray-800">Confirm Facility</h2>
            <p class="mb-6 text-sm text-gray-500">Are you sure you want to add this facility?</p>
            <div class="flex justify-center gap-3">
                <button type="button" id="cancelFacility" class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-xmark mr-1"></i> Cancel</button>
                <button type="button" id="confirmFacility" class="cursor-pointer rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]"><i class="fa-solid fa-check mr-1"></i> Yes, Proceed</button>
            </div>
        </div>
    </div>

    <div id="toast-warning" class="fixed right-5 top-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600"><i class="fa-solid fa-circle-exclamation text-lg"></i></div>
        <div id="toast-message" class="ml-3 flex-1 text-sm font-medium">Please fill out this field.</div>
        <button type="button" id="close-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div id="toast-success" class="fixed right-5 top-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600"><i class="fa-solid fa-circle-check text-lg"></i></div>
        <div id="toast-success-message" class="ml-3 flex-1 text-sm font-medium">Facility added successfully!</div>
        <button type="button" id="close-success-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <script>
        const facilityForm = document.querySelector('form');
        const facilityModal = document.getElementById('facilityModal');
        const cancelFacility = document.getElementById('cancelFacility');
        const confirmFacility = document.getElementById('confirmFacility');
        const toast = document.getElementById('toast-warning');
        const toastMessage = document.getElementById('toast-message');
        const closeToast = document.getElementById('close-toast');
        const successToast = document.getElementById('toast-success');
        const closeSuccessToast = document.getElementById('close-success-toast');
        let toastTimer, successToastTimer;

        function toggleToast(element, show) {
            element.classList.toggle('translate-x-[120%]', !show);
            element.classList.toggle('opacity-0', !show);
            element.classList.toggle('translate-x-0', show);
            element.classList.toggle('opacity-100', show);
        }

        function showToast(message) {
            toastMessage.textContent = message;
            toggleToast(toast, true);
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toggleToast(toast, false), 3000);
        }

        function showSuccessToast() {
            toggleToast(successToast, true);
            clearTimeout(successToastTimer);
            successToastTimer = setTimeout(() => toggleToast(successToast, false), 3000);
        }

        closeToast.addEventListener('click', () => toggleToast(toast, false));
        closeSuccessToast.addEventListener('click', () => toggleToast(successToast, false));

        if (sessionStorage.getItem('facilityAddedToast') === 'true') {
            sessionStorage.removeItem('facilityAddedToast');
            showSuccessToast();
        }

        facilityForm.addEventListener('submit', event => {
            event.preventDefault();

            const field = facilityForm.querySelector(':invalid');

            if (field) {
                const label = field.id && facilityForm.querySelector(`label[for="${field.id}"]`);
                showToast(`Please fill out the ${label ? label.textContent.replace('*', '').trim() : 'required fields'} field.`);
                field.focus();
                return;
            }

            facilityModal.classList.remove('hidden');
            facilityModal.classList.add('flex');
        });

        cancelFacility.addEventListener('click', () => {
            facilityModal.classList.add('hidden');
            facilityModal.classList.remove('flex');
        });

        confirmFacility.addEventListener('click', () => {
            sessionStorage.setItem('facilityAddedToast', 'true');
            facilityForm.submit();
        });

        facilityModal.addEventListener('click', event => {
            if (event.target === facilityModal) {
                facilityModal.classList.add('hidden');
                facilityModal.classList.remove('flex');
            }
        });
    </script>
</body>
</html>
