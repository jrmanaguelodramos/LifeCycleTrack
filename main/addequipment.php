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
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">
            Add Equipment
        </h1>

        <p class="mt-2 mb-3 text-gray-600">
            Register new equipment
        </p>

        <a href="equipments_list.php" class="text-[#103F6C] font-bold flex items-center mb-5">
            <i class="fa-solid fa-arrow-left px-3"></i>Back to Equipments List
        </a>

        <?php if ($error !== ''): ?>
            <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="mb-5 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white p-6">
            <input type="hidden" name="action" value="add">

            <?php if (function_exists('csrf_field')) {
                echo csrf_field();
            } elseif (function_exists('csrf_token')) {
                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
            } ?>

            <!-- Equipment Information -->
            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Equipment Information</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>
                    Fields marked with <span class="text-red-500">*</span> are required
                </p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">

                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" placeholder="e.g. Computer, Printer" required
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="kind" class="mb-1 block text-sm font-medium text-gray-700">Asset Type <span class="text-red-500">*</span></label>
                    <select id="kind" name="kind" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select asset type</option>
                        <option value="Movable">Movable asset</option>
                        <option value="Fixed">Fixed asset</option>
                    </select>
                </div>

                <div>
                    <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Equipment Category <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select category</option>
                        <option>Fire equipment</option>
                        <option>IT equipment</option>
                        <option>Office, facility or utility equipment</option>
                    </select>
                </div>

                <div>
                    <label for="brand" class="mb-1 block text-sm font-medium text-gray-700">Brand</label>
                    <input id="brand" name="brand" type="text" placeholder="e.g. Canon"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="model" class="mb-1 block text-sm font-medium text-gray-700">Model</label>
                    <input id="model" name="model" type="text" placeholder="e.g. IP2770"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Equipment Image</label>
                    <input id="image" name="image" type="file" accept="image/*"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">Upload an equipment image (JPG, PNG, or WebP).</p>
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Office / Location <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="">Select location</option>

                        <optgroup label="1st Floor">
                            <option>Room 1</option>
                            <option>Room 2</option>
                            <option>Room 3</option>
                            <option>Room 4</option>
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

                <div>
                    <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="1" placeholder="e.g. 30"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">For multiple identical items, such as chairs or tables.</p>
                </div>

            </div>

            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition and Financial Information</h2>
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">

                <div>
                    <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                    <input id="year" name="year" type="number" required min="1950" max="<?= date('Y') ?>" placeholder="YYYY"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">

                    <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-600">
                        <input id="yearEst" name="year_estimated" type="checkbox" value="1">
                        Estimated year
                    </label>
                </div>

                <div>
                    <label for="basis" class="mb-1 block text-sm font-medium text-gray-700">Cost Type</label>
                    <select id="basis" name="basis"
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        <option value="Original">Original cost</option>
                        <option value="Current">Current value (original unknown)</option>
                    </select>
                </div>

                <div>
                    <label for="cost" class="mb-1 block text-sm font-medium text-gray-700">Amount (₱)</label>
                    <input id="cost" name="cost" type="number" min="0" step="0.01"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

            </div>

            <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">

                <div class="xl:col-span-3">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Usage</h2>

                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0">

                        <div>
                            <label for="sched" class="mb-1 block text-sm font-medium text-gray-700">Usage Schedule <span class="text-red-500">*</span></label>
                            <select id="sched" name="sched" required
                                class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                                <option value="">Select schedule</option>
                                <option value="Standard">Standard (8 hours)</option>
                                <option value="Continuous">Continuous (24 hours)</option>
                                <option value="Seasonal">Emergency / Seasonal</option>
                            </select>
                        </div>

                        <div>
                            <label for="hours" class="mb-1 block text-sm font-medium text-gray-700">Hours of Use per Day</label>
                            <input id="hours" name="hours_per_day" type="number" min="0" max="24" step="0.5"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                        </div>

                        <div>
                            <label for="age" class="mb-1 block text-sm font-medium text-gray-700">Current Asset Age</label>
                            <input id="age" type="text" disabled
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
                                    <option>Good</option>
                                    <option>Fair</option>
                                    <option>Poor</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="md:col-span-2 xl:col-span-1">
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2" placeholder="Add remarks.."
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"></textarea>
                        </div>

                    </div>
                </div>

            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <button type="reset" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700 cursor-pointer">Clear</button>

                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white">Add Equipment</button>

                <!-- confirmation tas toast  -->
            </div>

        </form>
    </main>

</body>

</html>
