<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['access_token'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['form_action'] ?? '') === 'add_equipment'
) {

    $name = trim((string)($_POST['equipment_name'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $condition = trim((string)($_POST['condition'] ?? ''));
    $brand = trim((string)($_POST['brand'] ?? ''));
    $model = trim((string)($_POST['model'] ?? ''));
    $dateAcquired = trim((string)($_POST['date_acquired'] ?? ''));
    $remarks = trim((string)($_POST['remarks'] ?? ''));

    $allowedConditions = ['Good', 'Fair', 'Poor'];
    $allowedLocations = ['Building A', 'Building B', 'Main Building'];

    $errors = [];

    if ($name === '') {
        $errors[] = 'Equipment name is required.';
    }

    if (!in_array($condition, $allowedConditions, true)) {
        $errors[] = 'Select a valid condition.';
    }

    if (!in_array($location, $allowedLocations, true)) {
        $errors[] = 'Select a valid location.';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateAcquired);

    if (!$date || $date->format('Y-m-d') !== $dateAcquired) {
        $errors[] = 'Select a valid acquisition date.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO assets
                    (name, asset_type, location, condition, brand, model,
                     date_acquired, remarks)
                 VALUES
                    (:name, 'equipment', :location, :condition, :brand,
                     :model, :date_acquired, :remarks)"
            );

            $stmt->execute([
                'name' => $name,
                'location' => $location,
                'condition' => $condition,
                'brand' => $brand !== '' ? $brand : null,
                'model' => $model !== '' ? $model : null,
                'date_acquired' => $dateAcquired,
                'remarks' => $remarks !== '' ? $remarks : null,
            ]);

            header(
                'Location: equipments_list.php?' .
                http_build_query([
                    'success' => 'Equipment added successfully.'
                ])
            );
            exit;

        } catch (Throwable $exception) {
            error_log('Add equipment error: ' . $exception->getMessage());
            $errors[] = 'Unable to save the equipment. Check your database schema.';
        }
    }

    $_SESSION['equipment_form_errors'] = $errors;
    $_SESSION['equipment_form_old'] = $_POST;

    header('Location: equipments_list.php');
    exit;
}
?>

<!-- Add Modal -->
<div id="equipmentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60">

    <div class="w-[95%] max-w-207.5 bg-white shadow-2xl">

        <div class="h-13 bg-[#155B92] border-2 border-[#0D8BD0] flex items-center justify-between px-7 text-white">
            <div class="flex items-center gap-5">
                <i class="fa-solid fa-screwdriver-wrench text-xl"></i>
                <h2 class="text-xl font-bold">Add Equipment</h2>
            </div>

            <button id="closeEquipmentModal" type="button" class="text-xl hover:text-gray-200 cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-7">
            <form
                id="equipmentForm"
                method="POST"
                action="addequipmentmodal.php">

                <input type="hidden" name="form_action" value="add_equipment">

                <div class="border border-slate-300 rounded-md mb-6">
                    <div class="bg-[#C5D9E8] px-4 py-2 rounded-t-md">
                        <h3 class="font-bold text-lg text-black"><i class="fa-solid fa-newspaper mr-3"></i>Basic Information</h3>
                    </div>

                    <div class="p-5">

                        <div class="grid grid-cols-[155px_1fr] gap-5">

                            <!-- Image -->
                            <label class="h-33.75 border border-dashed border-slate-300 flex flex-col items-center justify-center text-[#155B92] cursor-pointer hover:bg-slate-50">
                                <i class="fa-regular fa-image text-3xl mb-3"></i>
                                <span class="text-sm font-semibold text-[#155B92]">Click to Upload</span>
                                <span class="text-sm font-semibold text-[#155B92]">Image</span>
                                <input type="file" name="image" accept="image/*" class="hidden">
                            </label>

                            <div class="grid grid-cols-3 gap-x-6 gap-y-4">

                                <!-- Equipment Name -->
                                <div>
                                    <label class="block text-sm font-semibold text-[#155B92] mb-1">Equipment Name<span class="text-red-500">*</span></label>
                                    <input type="text" name="equipment_name" placeholder="Enter Name.." class="w-full h-9 px-3 border border-[#AFC4D6] rounded-md text-sm outline-none focus:border-[#155B92]">
                                </div>

                                <!-- Brand -->
                                <div>
                                    <label class="block text-sm font-semibold text-[#155B92] mb-1">Brand</label>
                                    <input type="text" name="brand" placeholder="Enter Brand (Optional)" class="w-full h-9 px-3 border border-[#AFC4D6] rounded-md text-sm outline-none focus:border-[#155B92]">
                                </div>

                                <!-- Model -->
                                <div>
                                    <label class="block text-sm font-semibold text-[#155B92] mb-1">Model</label>
                                    <input type="text" name="model" placeholder="Enter Model (Optional)" class="w-full h-9 px-3 border border-[#AFC4D6] rounded-md text-sm outline-none focus:border-[#155B92]">
                                </div>

                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-semibold text-[#155B92] mb-1">Category<span class="text-red-500">*</span></label>
                                    <select name="category" class="w-full h-9 px-3 border border-[#AFC4D6] rounded-md text-sm text-[#7A9BB5] outline-none focus:border-[#155B92]">
                                        <option value="">Select Category</option>
                                        <option value="Equipment" selected>Equipment</option>
                                    </select>
                                </div>

                                <!-- Location -->
                                <div>
                                    <label class="block text-sm font-semibold text-[#155B92] mb-1">Located at:<span class="text-red-500">*</span></label>
                                    <select name="location" class="w-full h-9 px-3 border border-[#AFC4D6] rounded-md text-sm text-[#7A9BB5] outline-none focus:border-[#155B92]">
                                        <option value="">Select Location</option>
                                        <option value="Building A">Building A</option>
                                        <option value="Building B">Building B</option>
                                        <option value="Main Building">Main Building</option>
                                    </select>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="block text-sm font-semibold text-[#155B92] mb-1">Date Acquired<span class="text-red-500">*</span></label>
                                    <input type="date" name="date_acquired" class="w-full h-9 px-3 border border-[#AFC4D6] rounded-md text-sm text-[#7A9BB5] outline-none focus:border-[#155B92]">
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="grid grid-cols-2 gap-3">

                    <div class="border border-slate-300 rounded-md">

                        <div class="bg-[#C5D9E8] px-4 py-2 rounded-t-md">
                            <h3 class="font-bold text-lg text-black"><i class="fa-solid fa-gear mr-3"></i>Additional Details</h3>
                        </div>

                        <div class="p-5">

                            <div class="mb-5">
                                <label class="block text-sm font-semibold text-[#155B92] mb-1">Condition<span class="text-red-500">*</span></label>
                                <select name="condition" class="w-37.5 h-9 px-3 border border-[#AFC4D6] rounded-md text-sm text-[#7A9BB5] outline-none focus:border-[#155B92]">
                                    <option value="">Select Condition</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Poor">Poor</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[#155B92] mb-1">Equipment Name</label>
                                <input type="text" name="details_name" placeholder="Enter Name.." class="w-48.75 h-9 px-3 border border-[#AFC4D6] rounded-md text-sm outline-none focus:border-[#155B92]">
                            </div>

                        </div>

                    </div>

                    <!-- Remarks -->
                    <div class="border border-slate-300 rounded-md">

                        <div class="bg-[#C5D9E8] px-4 py-2 rounded-t-md">
                            <h3 class="font-bold text-lg text-black"><i class="fa-solid fa-comment mr-3"></i>Remarks</h3>
                        </div>

                        <div class="p-3">
                            <textarea name="remarks" placeholder="Enter Notes/Remarks..." class="w-full h-47.5 resize-none p-3 border border-[#AFC4D6] rounded-xl text-sm outline-none focus:border-[#155B92]"></textarea>
                        </div>

                    </div>

                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 mt-3">

                    <button id="clearEquipmentModal" type="button" class="px-5 py-1 border-2 border-[#155B92] text-[#155B92] rounded-md font-semibold hover:bg-slate-100 cursor-pointer">
                        <i class="fa-solid fa-x mr-2"></i>Clear
                    </button>

                    <button id="addEquipment" type="submit" class="px-5 py-1 bg-[#155B92] text-white rounded-md font-semibold hover:bg-[#124b79] cursor-pointer">
                        <i class="fa-solid fa-circle-plus mr-2"></i>Add
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>

<script>
    const equipmentModal = document.getElementById('equipmentModal');
    const openEquipmentModal = document.getElementById('openEquipmentModal');
    const closeEquipmentModal = document.getElementById('closeEquipmentModal');
    const clearEquipmentModal = document.getElementById('clearEquipmentModal');

    openEquipmentModal.addEventListener('click', function() {
        equipmentModal.classList.remove('hidden');
        equipmentModal.classList.add('flex');
    });

    closeEquipmentModal.addEventListener('click', function() {
        equipmentModal.classList.add('hidden');
        equipmentModal.classList.remove('flex');
    });

    clearEquipmentModal.addEventListener('click', function() {
        document.querySelector('#equipmentModal form')?.reset();
    });

    equipmentModal.addEventListener('click', function(event) {
        if (event.target === equipmentModal) {
            equipmentModal.classList.add('hidden');
            equipmentModal.classList.remove('flex');
        }
    });
</script>