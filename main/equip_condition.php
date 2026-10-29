<?php
require_once __DIR__ . '/_app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

if (!isset($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}

$actor = $_SESSION['user']['email']
    ?? $_SESSION['email']
    ?? $_SESSION['username']
    ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $equipmentId = (int) ($input['equipment_id'] ?? 0);
    $condition   = (string) ($input['condition'] ?? '');
    $status      = trim((string) ($input['status'] ?? ''));
    $notes       = trim((string) ($input['notes'] ?? ''));

    $allowedStatuses = [
        'Serviceable (functioning)',
        'Unserviceable: for maintenance',
        'Unserviceable: for repair',
        'Unserviceable: for inspection',
        'Beyond repair (irreparable)',
        'Subject for disposal or condemnation',
        'Disposed',
    ];

    if (
        $equipmentId <= 0 ||
        !in_array($condition, ['Good', 'Fair', 'Poor'], true) ||
        !in_array($status, $allowedStatuses, true)
    ) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid input.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $check = $pdo->prepare('SELECT 1 FROM equipment WHERE id = :id');
        $check->execute([':id' => $equipmentId]);
        if (!$check->fetchColumn()) {
            throw new RuntimeException('Equipment not found.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO equipment_condition_assessments
                (equipment_id, condition, status, notes, assessed_by)
             VALUES (:id, :condition, :status, :notes, :by)'
        );
        $stmt->execute([
            ':id'        => $equipmentId,
            ':condition' => $condition,
            ':status'    => $status,
            ':notes'     => $notes !== '' ? $notes : null,
            ':by'        => $actor,
        ]);

        $stmt = $pdo->prepare(
            'UPDATE equipment
                SET condition   = :condition,
                    status      = :status,
                    disposed_at = CASE
                                    WHEN CAST(:status2 AS text) = \'Disposed\'
                                    THEN COALESCE(disposed_at, now())
                                    ELSE disposed_at
                                  END,
                    updated_at  = now()
              WHERE id = :id'
        );
        $stmt->execute([
            ':condition' => $condition,
            ':status'    => $status,
            ':status2'   => $status,
            ':id'        => $equipmentId,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO activity_log (equipment_id, action, details, actor)
             VALUES (:id, :action, :details, :actor)'
        );
        $stmt->execute([
            ':id'      => $equipmentId,
            ':action'  => 'condition_assessment',
            ':details' => "Condition: {$condition}; Status: {$status}",
            ':actor'   => $actor,
        ]);

        $pdo->commit();

        echo json_encode(['ok' => true]);
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Equipment assessment save failed: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Unable to save the assessment.']);
    }
    exit;
}

function load_equipment_assessments(PDO $pdo): array
{
    $sql = "
        SELECT
            q.id,
            q.name,
            q.condition,
            q.status,
            q.image_url,
            COALESCE(
                (SELECT a.notes
                   FROM equipment_condition_assessments a
                  WHERE a.equipment_id = q.id
                  ORDER BY a.assessed_at DESC, a.id DESC
                  LIMIT 1),
                q.remarks
            ) AS assessment_notes
        FROM equipment q
        ORDER BY q.id ASC
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$conditionAssets = load_equipment_assessments($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Condition Assessment</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition.css">
    <link rel="stylesheet" href="../css/equipment_condition.css">

    <style>
        /* Loading state for the confirm modal buttons */
        #confirmModal button:disabled {
            cursor: not-allowed;
        }

        #confirmModal #confirmequipmentSave:disabled {
            background-color: #103F6C;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="condition-layout flex w-full items-stretch gap-5">
            <div class="condition-left flex w-3/5 min-w-0 flex-col overflow-hidden">
                <h1 class="text-3xl font-bold">Equipment Condition Assessment</h1>
                <p class="mb-1 mt-2 text-gray-700">View and manage the current condition of equipments.</p>
                <p id="status" class="sr-only" aria-live="polite"></p>

                <div class="flex flex-wrap items-end gap-4 p-5">
                    <div class="w-full max-w-sm">
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 text-slate-600"></i>
                            <input id="searchInput" type="text" class="w-full rounded-md border border-slate-200 bg-transparent py-2 pl-10 pr-3 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-[#155B92] focus:outline-none" placeholder="Search by name or ID...">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="conditionFilter" class="text-xs font-medium text-gray-700">Condition</label>
                        <select id="conditionFilter" class="h-9 w-28 cursor-pointer rounded-md border border-slate-300 bg-white px-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none">
                            <option value="all">All</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>

                <div class="condition-table-container">
                    <table class="condition-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-id-card"></i> ID</th>
                                <th><i class="fa-solid fa-tag"></i> Name</th>
                                <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                                <th><i class="fa-solid fa-gear"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conditionAssets as $asset): ?>
                                <tr
                                    data-asset-id="<?= (int) $asset['id'] ?>"
                                    data-notes="<?= e($asset['assessment_notes'] ?? '') ?>"
                                    data-status="<?= e($asset['status'] ?? '') ?>"
                                    data-image="<?= e($asset['image_url'] ?? '') ?>">
                                    <td><?= 'EQ-' . str_pad((string) $asset['id'], 4, '0', STR_PAD_LEFT) ?></td>

                                    <td><?= e($asset['name']) ?></td>

                                    <td>
                                        <?= condition_badge((string) $asset['condition']) ?>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="view-button edit-button"
                                            data-id="<?= (int) $asset['id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            Edit
                                        </button>

                                        <a
                                            href="viewequip.php?id=<?= (int) $asset['id'] ?>"
                                            class="view-button view-button-action">
                                            <i class="fa-solid fa-eye"></i>
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p id="noResults" class="hidden py-6 text-center text-gray-500">No equipment matches your search.</p>
                </div>

                <div class="pagination flex-shrink-0" id="pagination">
                    <button type="button" class="pagination-btn" id="prevPage"><i class="fa-solid fa-chevron-left"></i> Previous</button>
                    <div id="pageNumbers" class="flex items-center gap-2"></div>
                    <button type="button" class="pagination-btn" id="nextPage">Next <i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            <div class="assessment-panel flex w-2/5 min-w-0 flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                <h2 class="rounded-t-xl bg-[#155B92] pb-4 pl-4 pt-3 text-xl font-semibold text-white">equipment Details</h2>

                <div class="flex flex-1 flex-col p-4">
                    <div id="emptyState" class="empty-state">
                        <i class="fa-regular fa-hand-pointer"></i>
                        <p class="empty-title">No equipment selected</p>
                        <p class="empty-text">Click <strong>Edit</strong> on a equipment in the list to view and update its condition.</p>
                    </div>

                    <form id="assessmentForm" class="flex flex-1 flex-col space-y-6" novalidate hidden>
                        <div class="flex items-center gap-4">
                            <div class="flex w-1/2 items-center justify-center rounded-xl border border-gray-200 p-2 shadow-sm">
                                <img id="image" src="../img/logo.png" alt="equipment image" class="block h-40 w-full object-contain">
                            </div>
                            <div class="w-1/2 min-w-0">
                                <p class="text-xs text-gray-500">equipment ID</p>
                                <h2 id="id" class="text-3xl font-black text-black"></h2>
                                <p id="name" class="mt-1 text-sm font-bold text-gray-800"></p>
                                <p id="panelCondition" class="mt-2 text-sm text-gray-500"></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-3 text-xl font-bold text-black">Condition Assessment</h3>
                            <div class="flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Good" checked class="h-5 w-5 cursor-pointer accent-green-500">
                                    <span>Good</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Fair" class="h-5 w-5 cursor-pointer accent-orange-500">
                                    <span>Fair</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Poor" class="h-5 w-5 cursor-pointer accent-red-500">
                                    <span>Poor</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="equipStatus" class="mb-2 block text-xl font-bold text-black">Status</label>
                            <select id="equipStatus" name="status" required class="w-full cursor-pointer rounded-xl border border-gray-300 bg-white p-3 text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">Select status</option>
                                <option value="Serviceable (functioning)">Serviceable (functioning)</option>
                                <optgroup label="Unserviceable">
                                    <option value="Unserviceable: for maintenance">Unserviceable: for maintenance</option>
                                    <option value="Unserviceable: for repair">Unserviceable: for repair</option>
                                    <option value="Unserviceable: for inspection">Unserviceable: for inspection</option>
                                </optgroup>
                                <option value="Beyond repair (irreparable)">Beyond repair (irreparable)</option>
                                <option value="Subject for disposal or condemnation">Subject for disposal or condemnation</option>
                                <option value="Disposed">Disposed</option>
                            </select>
                        </div>

                        <div>
                            <label for="notes" class="mb-2 block text-xl font-bold text-black">Notes / Remarks</label>
                            <textarea id="notes" name="notes" rows="5" placeholder="Enter notes or remarks..." class="w-full resize-none rounded-xl border border-gray-300 p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        </div>

                        <button type="submit" class="mt-auto flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#155B92] px-4 py-3 font-semibold text-white transition-colors hover:bg-[#104a78]">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Assessment</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i>
            </div>
            <h2 class="mb-2 text-xl font-bold text-gray-800">Confirm Changes</h2>
            <p class="mb-6 text-sm text-gray-500">Are you sure you want to save these equipment changes?</p>
            <div class="flex justify-center gap-3">
                <button type="button" id="cancelequipmentConfirm" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"><i class="fa-solid fa-xmark mr-1"></i>Cancel</button>
                <button type="button" id="confirmequipmentSave" class="cursor-pointer rounded-lg bg-[#15588F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#103F6C]"><i class="fa-solid fa-check mr-1"></i>Yes, Save</button>
            </div>
        </div>
    </div>

    <div id="toast-warning" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-exclamation text-lg"></i>
        </div>
        <div id="toast-warning-message" class="ml-3 flex-1 text-sm font-medium">Please complete the required fields.</div>
        <button type="button" data-dismiss-toast="warning" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="toast-success" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div id="toast-success-message" class="ml-3 flex-1 text-sm font-medium">Equipment condition saved successfully!</div>
        <button type="button" data-dismiss-toast="success" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div id="toast-danger" class="fixed top-5 right-5 z-[100] flex w-full max-w-sm translate-x-[120%] items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 opacity-0 shadow-lg transition-all duration-300 ease-out" role="alert">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-xmark text-lg"></i>
        </div>
        <div id="toast-danger-message" class="ml-3 flex-1 text-sm font-medium">Unable to save the assessment.</div>
        <button type="button" data-dismiss-toast="danger" class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const table = document.querySelector(".condition-table");
            const searchInput = document.getElementById("searchInput");
            const conditionFilter = document.getElementById("conditionFilter");
            const form = document.getElementById("assessmentForm");
            const emptyState = document.getElementById("emptyState");
            const equipmentId = document.getElementById("id");
            const equipmentName = document.getElementById("name");
            const equipmentImage = document.getElementById("image");
            const panelCondition = document.getElementById("panelCondition");
            const notes = document.getElementById("notes");
            const equipStatus = document.getElementById("equipStatus");
            const noResults = document.getElementById("noResults");
            const status = document.getElementById("status");
            const prevButton = document.getElementById("prevPage");
            const nextButton = document.getElementById("nextPage");
            const pageNumbers = document.getElementById("pageNumbers");
            const confirmModal = document.getElementById("confirmModal");
            const cancelSave = document.getElementById("cancelequipmentConfirm");
            const confirmSave = document.getElementById("confirmequipmentSave");
            const confirmSaveDefaultHtml = confirmSave.innerHTML;
            const rows = Array.from(table.querySelectorAll("tbody tr"));

            const rowsPerPage = 10;
            const toastTimers = {};
            let currentPage = 1;
            let selectedRow = null;
            let isSaving = false;

            function showToast(type, message) {
                const toast = document.getElementById("toast-" + type);
                const messageElement = document.getElementById("toast-" + type + "-message");

                if (!toast || !messageElement) return;

                clearTimeout(toastTimers[type]);
                messageElement.textContent = message;
                toast.classList.remove("translate-x-[120%]", "opacity-0");
                toast.classList.add("translate-x-0", "opacity-100");

                toastTimers[type] = setTimeout(() => hideToast(type), 3000);
            }

            function hideToast(type) {
                const toast = document.getElementById("toast-" + type);
                if (!toast) return;

                clearTimeout(toastTimers[type]);
                toast.classList.remove("translate-x-0", "opacity-100");
                toast.classList.add("translate-x-[120%]", "opacity-0");
            }

            document.querySelectorAll("[data-dismiss-toast]").forEach(button => {
                button.addEventListener("click", () => hideToast(button.dataset.dismissToast));
            });

            function getCondition(row) {
                return row.querySelector(".condition")?.textContent.trim() || "";
            }

            function updateCondition(row, value) {
                const cells = row.querySelectorAll("td");
                const badge = document.createElement("span");
                badge.className = "condition " + value.toLowerCase();

                const dot = document.createElement("span");
                badge.appendChild(dot);
                badge.appendChild(document.createTextNode(value));
                cells[2].replaceChildren(badge);
            }

            function openequipment(row) {
                emptyState.hidden = true;
                form.hidden = false;
                selectedRow = row;

                const cells = row.querySelectorAll("td");
                const condition = getCondition(row);

                equipmentId.textContent = cells[0].textContent.trim();
                equipmentName.textContent = cells[1].textContent.trim();
                panelCondition.textContent = "Current condition: " + condition;
                notes.value = row.dataset.notes || "";
                equipStatus.value = row.dataset.status || "";
                equipmentImage.src = row.dataset.image || "../img/logo.png";

                const radio = form.querySelector('input[name="condition"][value="' + condition + '"]');
                if (radio) radio.checked = true;

                rows.forEach(item => item.classList.remove("selected-row"));
                row.classList.add("selected-row");

                status.textContent = "equipment ID " + cells[0].textContent.trim() + " selected.";
            }

            rows.forEach(row => {
                row.querySelector(".edit-button")?.addEventListener("click", () =>
                    openequipment(row));
            });

            function getFilteredRows() {
                const search = searchInput.value.trim().toLowerCase();
                const selectedCondition = conditionFilter.value.toLowerCase();

                return rows.filter(row => {
                    const cells = row.querySelectorAll("td");
                    const id = cells[0].textContent.trim().toLowerCase();
                    const name = cells[1].textContent.trim().toLowerCase();
                    const condition = getCondition(row).toLowerCase();

                    return (id.includes(search) || name.includes(search)) &&
                        (selectedCondition === "all" || condition === selectedCondition);
                });
            }

            function renderTable() {
                const filteredRows = getFilteredRows();
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
                currentPage = Math.min(currentPage, totalPages);

                rows.forEach(row => row.style.display = "none");

                const start = (currentPage - 1) * rowsPerPage;
                filteredRows.slice(start, start + rowsPerPage).forEach(row => row.style.display = "");

                noResults.classList.toggle("hidden", filteredRows.length !== 0);
                pageNumbers.replaceChildren();

                for (let page = 1; page <= totalPages; page++) {
                    const button = document.createElement("button");
                    button.type = "button";
                    button.textContent = page;
                    button.className = "pagination-number";

                    if (page === currentPage) button.classList.add("active");

                    button.addEventListener("click", () => {
                        currentPage = page;
                        renderTable();
                    });

                    pageNumbers.appendChild(button);
                }

                prevButton.disabled = currentPage === 1;
                nextButton.disabled = currentPage === totalPages || filteredRows.length === 0;

                prevButton.style.opacity = prevButton.disabled ? "0.5" : "1";
                nextButton.style.opacity = nextButton.disabled ? "0.5" : "1";
                prevButton.style.cursor = prevButton.disabled ? "not-allowed" : "pointer";
                nextButton.style.cursor = nextButton.disabled ? "not-allowed" : "pointer";
            }

            function closeConfirmModal() {
                if (isSaving) return;
                confirmModal.classList.add("hidden");
                confirmModal.classList.remove("flex");
            }

            function setSaving(saving) {
                isSaving = saving;
                confirmSave.disabled = saving;
                cancelSave.disabled = saving;
                confirmSave.innerHTML = saving ?
                    '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...' :
                    confirmSaveDefaultHtml;
            }

            form.addEventListener("submit", event => {
                event.preventDefault();

                if (!selectedRow) {
                    showToast("warning", "Please select a equipment first.");
                    return;
                }

                if (!form.querySelector('input[name="condition"]:checked')) {
                    showToast("warning", "Please select a condition before saving.");
                    return;
                }

                if (!equipStatus.value) {
                    showToast("warning", "Please select a status before saving.");
                    equipStatus.focus();
                    return;
                }

                confirmModal.classList.remove("hidden");
                confirmModal.classList.add("flex");
            });

            cancelSave.addEventListener("click", closeConfirmModal);

            confirmSave.addEventListener("click", async () => {
                if (isSaving) return;

                if (!selectedRow) {
                    closeConfirmModal();
                    showToast("danger", "No equipment is selected.");
                    return;
                }

                const selectedRadio = form.querySelector('input[name="condition"]:checked');

                if (!selectedRadio || !equipStatus.value) {
                    closeConfirmModal();
                    showToast("warning", "Please complete the required fields.");
                    return;
                }

                setSaving(true);

                try {
                    const response = await fetch(window.location.pathname, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            equipment_id: selectedRow.dataset.assetId,
                            condition: selectedRadio.value,
                            status: equipStatus.value,
                            notes: notes.value
                        })
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok || !result.ok) {
                        throw new Error(result.message || "Unable to save the assessment.");
                    }

                    updateCondition(selectedRow, selectedRadio.value);
                    selectedRow.dataset.notes = notes.value;
                    selectedRow.dataset.status = equipStatus.value;

                    panelCondition.textContent = "Current condition: " + selectedRadio.value;

                    const savedName = equipmentName.textContent;

                    status.textContent = "Assessment updated for " + savedName + ".";
                    renderTable();

                    showToast("success", "Equipment condition saved successfully.");
                } catch (error) {
                    showToast("danger", error.message);
                } finally {
                    setSaving(false);
                    closeConfirmModal();
                }
            });

            confirmModal.addEventListener("click", event => {
                if (event.target === confirmModal) closeConfirmModal();
            });

            document.addEventListener("keydown", event => {
                if (event.key === "Escape" && !confirmModal.classList.contains("hidden")) {
                    closeConfirmModal();
                }
            });

            searchInput.addEventListener("input", () => {
                currentPage = 1;
                renderTable();
            });

            conditionFilter.addEventListener("change", () => {
                currentPage = 1;
                renderTable();
            });

            prevButton.addEventListener("click", () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            });

            nextButton.addEventListener("click", () => {
                const totalPages = Math.ceil(getFilteredRows().length / rowsPerPage);

                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            });

            renderTable();
        });
    </script>
</body>

</html>