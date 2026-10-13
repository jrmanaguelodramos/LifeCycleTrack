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
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">
            Add Facility
        </h1>

        <p class="mt-2 mb-3 text-gray-600">
            Register new facility
        </p>

        <a href="facilities_list.php" class="text-[#103F6C] font-bold flex items-center mb-5">
            <i class="fa-solid fa-arrow-left px-3"></i>Back to Facilities List
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
            <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Facility Information</h2>

            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-solid fa-circle-info text-[#155B92]"></i>
                <p>
                    Fields marked with <span class="text-red-500">*</span> are required
                </p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">

                <div class="md:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Facility Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" placeholder="e.g. Comfort Room, Multi-Purpose Hall" required
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                </div>

                <!-- Equipment Image Upload -->
                <div>
                    <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Equipment Image</label>
                    <input id="image" name="image" type="file" accept="image/*"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">Upload an equipment image (JPG, PNG, or WebP).</p>
                </div>

                <div>
                    <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Floor<span class="text-red-500">*</span></label>
                    <select id="location" name="location" required
                        class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
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

            <!-- Balanced layout breakdown where the total column grid matches clean across your section panels -->
            <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">
                <div class="xl:col-span-2">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition Date</h2>
                    <div class="mb-6 xl:mb-0">
                       <div>
                            <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                            <input id="year" name="year" type="number" required min="1970" max="<?= date('Y') ?>" placeholder="YYYY"
                                class="w-80 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">

                            <label class="mt-1.5 flex items-center gap-2 text-sm text-gray-600">
                                <input id="yearEst" name="year_estimated" type="checkbox" value="1">
                                Estimated year
                            </label>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-3">
                    <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:mb-0">
                        <div class="md:col-span-1">
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

                        <div class="md:col-span-2">
                            <label for="remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="2" placeholder="Add remarks.."
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <button type="reset" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700 cursor-pointer">Clear</button>

                <button type="submit" class="cursor-pointer rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white">Add Facility</button>

                <!-- confirmation tas toast  -->
            </div>

        </form>
    </main>

</body>

</html>
