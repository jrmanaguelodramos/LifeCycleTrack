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
                    <li><a href="equipments_list.php" class="block max-w-[200px] no-underline hover:underline mr-2 truncate"> <i class="fa-solid fa-wrench mr-2"></i>Equipments List</a></li>
                    <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right mr-2"></i></li>
                    <li aria-current="page" class="font-medium text-blue-900 cursor-pointer">Add Equipment</li>
                </ol>
            </nav>

        <h1 class="text-3xl font-bold">Add Equipment</h1>
        <p class="mt-2 mb-3 text-gray-600">Register new equipment</p>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form id="equipmentForm" method="POST" action="" enctype="multipart/form-data" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="add">
            <?php if (function_exists('csrf_field')) { echo csrf_field(); } elseif (function_exists('csrf_token')) { echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">'; } ?>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Equipment Information</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>Fields marked with <span class="text-red-500">*</span> are required</p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" placeholder="e.g. Computer, Printer" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="kind" class="mb-1 block text-sm font-medium text-gray-700">Asset Type <span class="text-red-500">*</span></label>
                    <select id="kind" name="kind" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select asset type</option>
                        <option value="Movable">Movable asset</option>
                        <option value="Fixed">Fixed asset</option>
                    </select>
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Equipment Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select category</option>
                        <option>Fire equipment</option>
                        <option>IT equipment</option>
                        <option>Office, facility or utility equipment</option>
                    </select>
                </div>

                <div>
                    <label for="brand" class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                    <input id="brand" name="brand" type="text" placeholder="e.g. Canon" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="model" class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                    <input id="model" name="model" type="text" placeholder="e.g. IP2770" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Equipment Image</label>
                    <input id="image" name="image" type="file" accept="image/*" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">Upload an equipment image (JPG, PNG, or WebP).</p>
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Office / Location <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select location</option>
                        <optgroup label="1st Floor">
                            <option>Room 1</option><option>Room 2</option><option>Room 3</option><option>Room 4</option>
                        </optgroup>
                        <optgroup label="2nd Floor">
                            <option>Room 1</option><option>Room 2</option><option>Room 3</option><option>Room 4</option>
                        </optgroup>
                        <optgroup label="3rd Floor">
                            <option>Room 1</option><option>Room 2</option><option>Room 3</option><option>Room 4</option>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="1" placeholder="e.g. 30" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">For multiple identical items, such as chairs or tables.</p>
                </div>
            </div>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition and Financial Information</h2>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                    <input id="year" name="year" type="number" required min="1950" max="<?= date('Y') ?>" placeholder="YYYY" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-600"><input id="yearEst" name="year_estimated" type="checkbox" value="1"> Estimated year</label>
                </div>

                <div>
                    <label for="basis" class="mb-1 block text-sm font-medium text-gray-700">Cost Type</label>
                    <select id="basis" name="basis" class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="Original">Original cost</option>
                        <option value="Current">Current value (original unknown)</option>
                    </select>
                </div>

                <div>
                    <label for="cost" class="mb-1 block text-sm font-medium text-gray-700">Amount (₱)</label>
                    <input id="cost" name="cost" type="number" min="0" step="0.01" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
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
                                <option value="Standard">Standard (8 hours)</option>
                                <option value="Continuous">Continuous (24 hours)</option>
                                <option value="Seasonal">Emergency / Seasonal</option>
                            </select>
                        </div>

                        <div>
                            <label for="hours" class="mb-1 block text-sm font-medium text-gray-700">Hours of Use per Day</label>
                            <input id="hours" name="hours_per_day" type="number" min="0" max="24" step="0.5" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        </div>

                        <div>
                            <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Current Asset Age</label>
                            <input id="age" type="text" disabled class="w-full rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500">
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0 xl:grid-cols-2">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <option>Good</option><option>Fair</option><option>Poor</option>
                            </select>
                        </div>

                        <div class="md:col-span-2 xl:col-span-1">
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2" placeholder="Add remarks.." class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <button type="reset" class="cursor-pointer rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Clear</button>
                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white">Add Equipment</button>
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
                <button type="button" id="cancelEquipment" class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-xmark mr-1"></i> Cancel</button>
                <button type="button" id="confirmEquipment" class="cursor-pointer rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]"><i class="fa-solid fa-check mr-1"></i> Yes, Add Equipment</button>
            </div>
        </div>
    </div>

    <div id="toast-warning" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600"><i class="fa-solid fa-circle-exclamation text-lg"></i></div>
        <div id="toast-message" class="ml-3 flex-1 text-sm font-medium">Please fill out the required fields.</div>
        <button type="button" id="close-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div id="toast-success" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600"><i class="fa-solid fa-circle-check text-lg"></i></div>
        <div class="ml-3 flex-1 text-sm font-medium">Equipment added successfully!</div>
        <button type="button" id="close-success-toast" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <script>
        const form = document.getElementById('equipmentForm');
        const modal = document.getElementById('equipmentModal');
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

        if (sessionStorage.getItem('equipmentAddedToast') === 'true') {
            sessionStorage.removeItem('equipmentAddedToast');
            showSuccessToast();
        }

        form.addEventListener('submit', event => {
            event.preventDefault();
            const invalidField = form.querySelector(':invalid');

            if (invalidField) {
                const label = invalidField.id && form.querySelector(`label[for="${invalidField.id}"]`);
                showToast(`Please fill out the ${label ? label.textContent.replace('*', '').trim() : 'required fields'} field.`);
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

        document.getElementById('cancelEquipment').addEventListener('click', closeModal);
        modal.addEventListener('click', event => {
            if (event.target === modal) closeModal();
        });

        document.getElementById('confirmEquipment').addEventListener('click', () => {
            sessionStorage.setItem('equipmentAddedToast', 'true');
            form.submit();
        });

        const yearInput = document.getElementById('year');
        const ageInput = document.getElementById('age');

        function updateAge() {
            const year = Number(yearInput.value);
            ageInput.value = year >= 1950 && year <= new Date().getFullYear()
                ? `${new Date().getFullYear() - year} year(s)`
                : '';
        }

        yearInput.addEventListener('input', updateAge);
        updateAge();
    </script>
</body>
</html>