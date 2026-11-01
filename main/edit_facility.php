<?php
require_once __DIR__ . '/_app.php';
require_login();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';

$error = '';
$facilityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$facilityId || $facilityId < 1) {
    http_response_code(400);
    exit('Invalid facility ID.');
}

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

$facility = [
    'id' => $facilityId,
    'name' => '',
    'location' => '',
    'year' => '',
    'year_estimated' => 0,
    'status' => 'Good',
    'remarks' => '',
    'image_url' => ''
];

try {
    $stmt = $pdo->prepare(
        'SELECT id, name, location, acquisition_year, is_estimated_year,
                condition, remarks, image_url
         FROM public.facilities WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $facilityId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(404);
        exit('Facility not found.');
    }

    $facility = [
        'id' => (int) $record['id'],
        'name' => $record['name'] ?? '',
        'location' => $record['location'] ?? '',
        'year' => $record['acquisition_year'] ?? '',
        'year_estimated' => (int) ($record['is_estimated_year'] ?? 0),
        'status' => $record['condition'] ?? 'Good',
        'remarks' => $record['remarks'] ?? '',
        'image_url' => $record['image_url'] ?? ''
    ];
} catch (Throwable $e) {
    error_log('Facility retrieval failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load facility information. Check your database configuration.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newImagePath = null;

    try {
        if (function_exists('verify_csrf')) verify_csrf();

        $postedId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$postedId || $postedId !== $facilityId) {
            throw new RuntimeException('Invalid facility ID.');
        }

        $name = trim($_POST['name'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $yearInput = trim($_POST['year'] ?? '');
        $yearEstimated = isset($_POST['year_estimated']) ? 1 : 0;
        $remarks = trim($_POST['remarks'] ?? '');

        if ($name === '' || mb_strlen($name) > 255) {
            throw new RuntimeException($name === ''
                ? 'Facility name is required.'
                : 'Facility name must not exceed 255 characters.');
        }

        if (!in_array($location, ['Ground Floor', '2nd Floor', '3rd Floor', '4th Floor'], true)) {
            throw new RuntimeException('Please select a valid floor.');
        }

        if ($yearInput === '' || !ctype_digit($yearInput) ||
            (int) $yearInput < 1970 || (int) $yearInput > (int) date('Y')) {
            throw new RuntimeException('Please enter a valid acquisition year.');
        }

        $currentStatusStmt = $pdo->prepare(
            'SELECT condition FROM public.facilities WHERE id = :id LIMIT 1'
        );
        $currentStatusStmt->execute(['id' => $facilityId]);
        $currentStatus = $currentStatusStmt->fetchColumn();

        if ($currentStatus === false) {
            throw new RuntimeException('Facility not found.');
        }

        $status = (string) $currentStatus;

        if (!in_array($status, ['Good', 'Fair', 'Poor'], true)) {
            throw new RuntimeException('The existing facility condition is invalid. Please check the database.');
        }

        $year = (int) $yearInput;
        $imageUrl = $facility['image_url'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Image upload failed. Please try again.');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException('The image must not exceed 5 MB.');
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('Invalid image upload.');
            }

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (!isset($types[$mime])) {
                throw new RuntimeException('Only JPG, PNG, and WebP images are allowed.');
            }

            $directory = dirname(__DIR__) . '/uploads/facilities';

            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create the image directory.');
            }
            if (!is_writable($directory)) {
                throw new RuntimeException('The image directory is not writable.');
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $types[$mime];
            $newImagePath = $directory . '/' . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $newImagePath)) {
                $newImagePath = null;
                throw new RuntimeException('Unable to save the uploaded image.');
            }

            $imageUrl = '../uploads/facilities/' . $fileName;
        }

        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare(
                'UPDATE public.facilities SET
                    name = :name, location = :location,
                    acquisition_year = :year, is_estimated_year = :estimated,
                    condition = :condition, remarks = :remarks, image_url = :image
                 WHERE id = :id'
            );

            $update->execute([
                'name' => $name,
                'location' => $location,
                'year' => $year,
                'estimated' => $yearEstimated,
                'condition' => $status,
                'remarks' => $remarks !== '' ? $remarks : null,
                'image' => $imageUrl !== '' ? $imageUrl : null,
                'id' => $facilityId
            ]);

            if ($update->rowCount() === 0) {
                $check = $pdo->prepare('SELECT id FROM public.facilities WHERE id = :id');
                $check->execute(['id' => $facilityId]);
                if (!$check->fetchColumn()) throw new RuntimeException('Facility not found.');
            }

            $pdo->commit();
            $_SESSION['facilityUpdatedToast'] = true;
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?id=' . $facilityId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($newImagePath && is_file($newImagePath)) unlink($newImagePath);
            throw $e;
        }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();

        foreach (['name', 'location', 'remarks'] as $key) {
            if (isset($_POST[$key])) $facility[$key] = trim($_POST[$key]);
        }

        if (isset($_POST['year'])) $facility['year'] = trim($_POST['year']);
        $facility['year_estimated'] = isset($_POST['year_estimated']) ? 1 : 0;
    } catch (Throwable $e) {
        error_log('Facility update failed: ' . $e->getMessage());
        $error = 'Unable to save facility information. Please check the database configuration and try again.';
    }
}

$facilityAge = $facility['year'] !== ''
    ? max(0, (int) date('Y') - (int) $facility['year']) . ' year(s)'
    : 'N/A';

$lockedInput = 'w-full cursor-not-allowed rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500';
$showSuccessToast = !empty($_SESSION['facilityUpdatedToast']);
unset($_SESSION['facilityUpdatedToast']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Facility</title>
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
            <li><a href="facilities_list.php" class="no-underline"><i class="fa-regular fa-building mr-2"></i>Facilities List</a></li>
            <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right"></i></li>
            <li><a href="viewfacility.php?id=<?= (int) $facility['id'] ?>" class="no-underline">Information</a></li>
            <li aria-hidden="true"><i class="sep fa-solid fa-chevron-right"></i></li>
            <li aria-current="page" class="font-medium text-blue-900">Edit Information</li>
        </ol>
    </nav>

    <h1 class="text-3xl font-bold">Edit Facility</h1>
    <p class="mt-2 mb-3 text-gray-600">Update facility information</p>

    <?php if ($error !== ''): ?>
        <div class="mb-5 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700"><?= ev($error) ?></div>
    <?php endif; ?>

    <form id="facilityForm" method="POST" enctype="multipart/form-data" novalidate class="mt-5 rounded-lg border border-slate-200 bg-white p-6">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int) $facility['id'] ?>">
        <?php
        if (function_exists('csrf_field')) echo csrf_field();
        elseif (function_exists('csrf_token')) echo '<input type="hidden" name="csrf_token" value="' . ev(csrf_token()) . '">';
        ?>

        <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Facility Information</h2>
        <p class="mb-4 flex items-center gap-2 text-sm text-gray-500"><i class="fa-solid fa-circle-info text-[#155B92]"></i>Fields marked with <span class="text-red-500">*</span> are required. Some fields are blocked and cannot be edited.</p>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <div class="md:col-span-2">
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Facility Name <span class="text-red-500">*</span></label>
                <input id="name" name="name" type="text" value="<?= ev($facility['name']) ?>" placeholder="e.g. Comfort Room, Multi-Purpose Hall" maxlength="255" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
            </div>

            <div>
                <label for="image" class="mb-1 block text-sm font-medium text-gray-700">Facility Image</label>
                <?php if ($facility['image_url'] !== ''): ?>
                    <img src="<?= ev($facility['image_url']) ?>" alt="Current facility image" class="mb-2 h-24 w-32 rounded-md border border-slate-200 object-cover">
                <?php endif; ?>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP. Maximum 5 MB.</p>
            </div>

            <div>
                <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Assigned Floor <span class="text-red-500">*</span></label>
                <select id="location" name="location" required class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                    <option value="">Select location</option>
                    <?php foreach (['Ground Floor', '2nd Floor', '3rd Floor', '4th Floor'] as $floor): ?>
                        <option value="<?= ev($floor) ?>"<?= sel($facility['location'], $floor) ?>><?= ev($floor) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-x-8 xl:grid-cols-5">
            <section class="xl:col-span-2">
                <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Acquisition Date</h2>
                <label for="year" class="mb-1 block text-sm font-medium text-gray-700">Acquisition Year <span class="text-red-500">*</span></label>
                <input id="year" name="year" type="number" min="1970" max="<?= date('Y') ?>" value="<?= ev($facility['year']) ?>" placeholder="YYYY" required class="w-full max-w-xs rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                <label class="mt-2 flex items-center gap-2 text-sm text-gray-600"><input id="yearEst" name="year_estimated" type="checkbox" value="1" <?= !empty($facility['year_estimated']) ? 'checked' : '' ?> class="rounded border-slate-300 text-[#155B92]">Estimated year</label>
                <p class="mt-1 text-xs text-gray-500">Check this if the exact acquisition year is unknown.</p>
            </section>

            <section class="xl:col-span-3">
                <h2 class="mb-4 border-b border-slate-200 pb-2 text-lg font-semibold text-[#103F6C]">Lifecycle Status</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Current Status</label>
                        <select id="status" class="<?= $lockedInput ?>" disabled>
                            <?php foreach (['Good', 'Fair', 'Poor'] as $statusOption): ?>
                                <option value="<?= ev($statusOption) ?>"<?= sel($facility['status'], $statusOption) ?>><?= ev($statusOption) ?></option>
                            <?php endforeach; ?>
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
            </section>
        </div>

        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
            <a href="viewfacility.php?id=<?= (int) $facility['id'] ?>" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Cancel</a>
            <button type="reset" class="rounded-[10px] border border-slate-300 bg-white p-3 px-4 text-sm text-gray-700">Reset Changes</button>
            <button type="submit" class="rounded-[10px] border border-[#15588F] bg-[#15588F] p-2 px-4 text-sm text-white hover:bg-[#103F6C]"><i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes</button>
        </div>
    </form>
</main>

<div id="facilityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100"><i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i></div>
        <h2 class="mb-2 text-xl font-bold text-gray-800">Confirm Changes</h2>
        <p class="mb-6 text-sm text-gray-500">Are you sure you want to save these facility changes?</p>
        <div class="flex justify-center gap-3">
            <button type="button" id="cancelFacility" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-xmark mr-1"></i>Cancel</button>
            <button type="button" id="confirmFacility" class="rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]"><i class="fa-solid fa-check mr-1"></i>Yes, Save</button>
        </div>
    </div>
</div>

<?php foreach ([
    'warning' => ['red', 'circle-exclamation', 'Please check the required fields.'],
    'success' => ['green', 'circle-check', 'Facility information saved successfully!']
] as $type => [$color, $icon, $defaultMessage]): ?>
    <div id="toast-<?= $type ?>" role="alert" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out border-<?= $color ?>-200">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-<?= $color ?>-100 text-<?= $color ?>-600"><i class="fa-solid fa-<?= $icon ?> text-lg"></i></div>
        <div <?= $type === 'warning' ? 'id="toast-message"' : '' ?> class="ml-3 flex-1 text-sm font-medium"><?= ev($defaultMessage) ?></div>
        <button type="button" data-close="<?= $type ?>" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    </div>
<?php endforeach; ?>

<script>
const form = document.getElementById('facilityForm'),
      modal = document.getElementById('facilityModal'),
      warningToast = document.getElementById('toast-warning'),
      successToast = document.getElementById('toast-success'),
      toastMessage = document.getElementById('toast-message'),
      confirmButton = document.getElementById('confirmFacility');

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

document.querySelectorAll('[data-close]').forEach(button => {
    button.addEventListener('click', () => {
        const type = button.dataset.close;
        clearTimeout(type === 'warning' ? warningTimer : successTimer);
        toggleToast(document.getElementById(`toast-${type}`), false);
    });
});

<?php if ($showSuccessToast): ?>showSuccessToast();<?php endif; ?>

form.addEventListener('submit', event => {
    event.preventDefault();
    const invalid = form.querySelector(':invalid');

    if (invalid) {
        const label = invalid.id ? form.querySelector(`label[for="${invalid.id}"]`) : null;
        showToast(`Please check the ${label ? label.textContent.replace('*', '').trim() : 'required fields'} field.`);
        invalid.focus();
        return;
    }

    const year = Number(document.getElementById('year').value);
    if (year < 1970 || year > new Date().getFullYear()) {
        showToast('Please enter a valid acquisition year.');
        document.getElementById('year').focus();
        return;
    }

    const image = document.getElementById('image').files[0];
    if (image) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(image.type)) {
            showToast('Only JPG, PNG, and WebP images are allowed.');
            return;
        }
        if (image.size > 5 * 1024 * 1024) {
            showToast('The image must not exceed 5 MB.');
            return;
        }
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
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
});

confirmButton.addEventListener('click', () => {
    confirmButton.disabled = true;
    confirmButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
    HTMLFormElement.prototype.submit.call(form);
});

form.addEventListener('reset', () => setTimeout(updateAge, 0));
document.getElementById('year').addEventListener('input', updateAge);

function updateAge() {
    const value = document.getElementById('year').value, year = Number(value);
    document.getElementById('age').value = value && year >= 1970
        ? `${Math.max(0, new Date().getFullYear() - year)} year(s)`
        : 'N/A';
}
</script>
</body>
</html>
