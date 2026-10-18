
<?php
require_once __DIR__ . '/_app.php';
require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Condition Assessment</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition.css">
    <link rel="stylesheet" href="../css/facility_condition.css">

    <style>
        .toast-notification {
            transform: translateX(calc(100% + 2rem));
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition:
                transform 0.3s ease,
                opacity 0.3s ease,
                visibility 0.3s ease;
        }

        .toast-notification.show {
            transform: translateX(0);
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="condition-layout flex w-full items-stretch gap-5">

            <!-- Left panel -->
            <div class="condition-left flex w-3/5 min-w-0 flex-col overflow-hidden">
                <h1 class="text-3xl font-bold">Facility Condition Assessment</h1>
                <p class="mb-1 mt-2 text-gray-700">
                    View and manage the current condition of facilities.
                </p>

                <p id="status" class="sr-only" aria-live="polite"></p>

                <!-- Search and filter -->
                <div class="flex flex-wrap items-end gap-4 p-5">
                    <div class="w-full max-w-sm">
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 text-slate-600"></i>

                            <input
                                id="searchInput"
                                type="text"
                                class="w-full rounded-md border border-slate-200 bg-transparent py-2 pl-10 pr-3 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-[#155B92] focus:outline-none"
                                placeholder="Search by name or ID..."
                            >
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="conditionFilter" class="text-xs font-medium text-gray-700">
                            Condition
                        </label>

                        <select
                            id="conditionFilter"
                            class="h-9 w-28 cursor-pointer rounded-md border border-slate-300 bg-white px-2 text-sm text-gray-800 focus:border-[#155B92] focus:outline-none"
                        >
                            <option value="all">All</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>

                <!-- Facility table -->
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
                            <tr data-notes="" data-status="">
                                <td>0001</td>
                                <td>Reception Hall</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>

                            <tr data-notes="" data-status="">
                                <td>0002</td>
                                <td>Gymnasium</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>

                            <tr data-notes="" data-status="">
                                <td>0003</td>
                                <td>Secretary's Office</td>
                                <td><span class="condition fair"><span></span>Fair</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>

                            <tr data-notes="" data-status="">
                                <td>0004</td>
                                <td>Cafeteria</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>

                            <tr data-notes="" data-status="">
                                <td>0005</td>
                                <td>Covered Court</td>
                                <td><span class="condition poor"><span></span>Poor</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>

                            <tr data-notes="" data-status="">
                                <td>0006</td>
                                <td>Admin Office</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>

                            <tr data-notes="" data-status="">
                                <td>0007</td>
                                <td>Parking Area</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="viewfacility.php" class="view-button view-button-action">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p id="noResults" class="hidden py-6 text-center text-gray-500">
                        No facility matches your search.
                    </p>
                </div>

                <!-- Pagination -->
                <div class="pagination flex-shrink-0" id="pagination">
                    <button type="button" class="pagination-btn" id="prevPage">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </button>

                    <div id="pageNumbers" class="flex items-center gap-2"></div>

                    <button type="button" class="pagination-btn" id="nextPage">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Right panel -->
            <div class="assessment-panel flex w-2/5 min-w-0 flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                <h2 class="rounded-t-xl bg-[#155B92] pb-4 pl-4 pt-3 text-xl font-semibold text-white">
                    Facility Details
                </h2>

                <div class="flex flex-1 flex-col p-4">
                    <div id="emptyState" class="empty-state">
                        <i class="fa-regular fa-hand-pointer"></i>
                        <p class="empty-title">No facility selected</p>
                        <p class="empty-text">
                            Click <strong>Edit</strong> on a facility in the list to view and update its condition.
                        </p>
                    </div>

                    <form id="assessmentForm" class="flex flex-1 flex-col space-y-6" novalidate hidden>
                        <div class="flex items-center gap-4">
                            <div class="flex w-1/2 items-center justify-center rounded-xl border border-gray-200 p-2 shadow-sm">
                                <img
                                    id="image"
                                    src="../img/logo.png"
                                    alt="Facility image"
                                    class="block h-40 w-full object-contain"
                                >
                            </div>

                            <div class="w-1/2 min-w-0">
                                <p class="text-xs text-gray-500">Facility ID</p>
                                <h2 id="id" class="text-3xl font-black text-black"></h2>
                                <p id="name" class="mt-1 text-sm font-bold text-gray-800"></p>
                                <p id="panelCondition" class="mt-2 text-sm text-gray-500"></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-3 text-xl font-bold text-black">Condition Assessment</h3>

                            <div class="flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input
                                        type="radio"
                                        name="condition"
                                        value="Good"
                                        checked
                                        class="h-5 w-5 cursor-pointer accent-green-500"
                                    >
                                    <span>Good</span>
                                </label>

                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input
                                        type="radio"
                                        name="condition"
                                        value="Fair"
                                        class="h-5 w-5 cursor-pointer accent-orange-500"
                                    >
                                    <span>Fair</span>
                                </label>

                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input
                                        type="radio"
                                        name="condition"
                                        value="Poor"
                                        class="h-5 w-5 cursor-pointer accent-red-500"
                                    >
                                    <span>Poor</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="equipStatus" class="mb-2 block text-xl font-bold text-black">
                                Status
                            </label>

                            <select
                                id="equipStatus"
                                name="status"
                                required
                                class="w-full cursor-pointer rounded-xl border border-gray-300 bg-white p-3 text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
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
                            <label for="notes" class="mb-2 block text-xl font-bold text-black">
                                Notes / Remarks
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="5"
                                placeholder="Enter notes or remarks..."
                                class="w-full resize-none rounded-xl border border-gray-300 p-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            class="mt-auto flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#155B92] px-4 py-3 font-semibold text-white transition-colors hover:bg-[#104a78]"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Assessment</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Confirmation modal -->
    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                <i class="fa-solid fa-circle-question text-3xl text-[#155B92]"></i>
            </div>

            <h2 class="mb-2 text-xl font-bold text-gray-800">Save Changes</h2>

            <p class="mb-6 text-sm text-gray-600">
                Are you sure you want to save the changes to this facility?
            </p>

            <div class="flex justify-center gap-3">
                <button
                    type="button"
                    id="cancelfacilityConfirm"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    id="confirmfacilitySave"
                    class="cursor-pointer rounded-lg bg-[#155B92] px-5 py-2 text-sm text-white hover:bg-[#10456f]"
                >
                    Yes, Save
                </button>
            </div>
        </div>
    </div>

    <!-- Warning toast -->
    <div
        id="toast-warning"
        class="toast-notification fixed right-5 top-5 z-[100] flex w-full max-w-sm items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 shadow-lg"
        role="alert"
        aria-live="assertive"
    >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-exclamation text-lg"></i>
        </div>

        <div id="toast-warning-message" class="ml-3 flex-1 text-sm font-medium">
            Please complete the required fields.
        </div>

        <button
            type="button"
            data-dismiss-toast="warning"
            class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Success toast -->
    <div
        id="toast-success"
        class="toast-notification fixed right-5 top-5 z-[100] flex w-full max-w-sm items-center rounded-lg border border-green-200 bg-white p-4 text-gray-700 shadow-lg"
        role="alert"
        aria-live="polite"
    >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>

        <div id="toast-success-message" class="ml-3 flex-1 text-sm font-medium">
            Facility assessment saved successfully!
        </div>

        <button
            type="button"
            data-dismiss-toast="success"
            class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Danger toast -->
    <div
        id="toast-danger"
        class="toast-notification fixed right-5 top-5 z-[100] flex w-full max-w-sm items-center rounded-lg border border-red-200 bg-white p-4 text-gray-700 shadow-lg"
        role="alert"
        aria-live="assertive"
    >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <i class="fa-solid fa-circle-xmark text-lg"></i>
        </div>

        <div id="toast-danger-message" class="ml-3 flex-1 text-sm font-medium">
            Unable to save the assessment.
        </div>

        <button
            type="button"
            data-dismiss-toast="danger"
            class="ml-3 flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Close"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const table = document.querySelector(".condition-table");
        const searchInput = document.getElementById("searchInput");
        const conditionFilter = document.getElementById("conditionFilter");
        const form = document.getElementById("assessmentForm");
        const emptyState = document.getElementById("emptyState");
        const facilityId = document.getElementById("id");
        const facilityName = document.getElementById("name");
        const panelCondition = document.getElementById("panelCondition");
        const notes = document.getElementById("notes");
        const equipStatus = document.getElementById("equipStatus");
        const noResults = document.getElementById("noResults");
        const status = document.getElementById("status");
        const prevButton = document.getElementById("prevPage");
        const nextButton = document.getElementById("nextPage");
        const pageNumbers = document.getElementById("pageNumbers");
        const confirmModal = document.getElementById("confirmModal");
        const cancelSave = document.getElementById("cancelfacilityConfirm");
        const confirmSave = document.getElementById("confirmfacilitySave");
        const rows = Array.from(table.querySelectorAll("tbody tr"));

        const rowsPerPage = 10;
        const toastTimers = {};

        let currentPage = 1;
        let selectedRow = null;

        // Display a toast notification.
        function showToast(type, message) {
            const toast = document.getElementById("toast-" + type);
            const messageElement = document.getElementById("toast-" + type + "-message");

            if (!toast || !messageElement) {
                console.error("Toast element not found:", type);
                return;
            }

            clearTimeout(toastTimers[type]);

            messageElement.textContent = message;

            // Close other visible toast notifications.
            document.querySelectorAll(".toast-notification.show").forEach(item => {
                if (item !== toast) {
                    item.classList.remove("show");
                }
            });

            // Display the selected toast.
            toast.classList.add("show");

            toastTimers[type] = setTimeout(() => {
                hideToast(type);
            }, 3000);
        }

        // Hide a toast notification.
        function hideToast(type) {
            const toast = document.getElementById("toast-" + type);

            if (!toast) return;

            clearTimeout(toastTimers[type]);
            toast.classList.remove("show");
        }

        // Close toast when the X button is clicked.
        document.querySelectorAll("[data-dismiss-toast]").forEach(button => {
            button.addEventListener("click", () => {
                hideToast(button.dataset.dismissToast);
            });
        });

        // Get the current condition from a table row.
        function getCondition(row) {
            return row.querySelector(".condition")?.textContent.trim() || "";
        }

        // Update the condition badge in the table.
        function updateCondition(row, value) {
            const cells = row.querySelectorAll("td");
            const badge = document.createElement("span");

            badge.className = "condition " + value.toLowerCase();

            const dot = document.createElement("span");

            badge.appendChild(dot);
            badge.appendChild(document.createTextNode(value));

            cells[2].replaceChildren(badge);
        }

        // Open a facility in the assessment panel.
        function openFacility(row) {
            emptyState.hidden = true;
            form.hidden = false;

            selectedRow = row;

            const cells = row.querySelectorAll("td");
            const condition = getCondition(row);

            facilityId.textContent = cells[0].textContent.trim();
            facilityName.textContent = cells[1].textContent.trim();
            panelCondition.textContent = "Current condition: " + condition;

            notes.value = row.dataset.notes || "";
            equipStatus.value = row.dataset.status || "";

            const radio = form.querySelector(
                'input[name="condition"][value="' + condition + '"]'
            );

            if (radio) {
                radio.checked = true;
            }

            rows.forEach(item => item.classList.remove("selected-row"));
            row.classList.add("selected-row");

            status.textContent = "Facility ID " +
                cells[0].textContent.trim() + " selected.";
        }

        // Edit and View actions.
        rows.forEach(row => {
            row.querySelector(".edit-button")?.addEventListener("click", () => {
                openFacility(row);
            });

            row.querySelector(".view-button-action")?.addEventListener("click", event => {
                event.preventDefault();
                openFacility(row);
                status.textContent = "Displaying facility details.";
            });
        });

        // Filter rows using the search input and condition dropdown.
        function getFilteredRows() {
            const search = searchInput.value.trim().toLowerCase();
            const selectedCondition = conditionFilter.value.toLowerCase();

            return rows.filter(row => {
                const cells = row.querySelectorAll("td");

                const id = cells[0].textContent.trim().toLowerCase();
                const name = cells[1].textContent.trim().toLowerCase();
                const condition = getCondition(row).toLowerCase();

                return (
                    (id.includes(search) || name.includes(search)) &&
                    (
                        selectedCondition === "all" ||
                        condition === selectedCondition
                    )
                );
            });
        }

        // Render the table and pagination.
        function renderTable() {
            const filteredRows = getFilteredRows();
            const totalPages = Math.max(
                1,
                Math.ceil(filteredRows.length / rowsPerPage)
            );

            currentPage = Math.min(currentPage, totalPages);

            rows.forEach(row => {
                row.style.display = "none";
            });

            const start = (currentPage - 1) * rowsPerPage;

            filteredRows
                .slice(start, start + rowsPerPage)
                .forEach(row => {
                    row.style.display = "";
                });

            noResults.classList.toggle("hidden", filteredRows.length !== 0);

            pageNumbers.replaceChildren();

            for (let page = 1; page <= totalPages; page++) {
                const button = document.createElement("button");

                button.type = "button";
                button.textContent = page;
                button.className = "pagination-number";

                if (page === currentPage) {
                    button.classList.add("active");
                }

                button.addEventListener("click", () => {
                    currentPage = page;
                    renderTable();
                });

                pageNumbers.appendChild(button);
            }

            prevButton.disabled = currentPage === 1;
            nextButton.disabled =
                currentPage === totalPages || filteredRows.length === 0;

            prevButton.style.opacity = prevButton.disabled ? "0.5" : "1";
            nextButton.style.opacity = nextButton.disabled ? "0.5" : "1";

            prevButton.style.cursor = prevButton.disabled
                ? "not-allowed"
                : "pointer";

            nextButton.style.cursor = nextButton.disabled
                ? "not-allowed"
                : "pointer";
        }

        // Close the save confirmation modal.
        function closeConfirmModal() {
            confirmModal.classList.add("hidden");
            confirmModal.classList.remove("flex");
        }

        // Validate the form before showing the confirmation modal.
        form.addEventListener("submit", event => {
            event.preventDefault();

            if (!selectedRow) {
                showToast("warning", "Please select a facility first.");
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

        // Save the assessment to the current table row.
        confirmSave.addEventListener("click", () => {
            if (!selectedRow) {
                closeConfirmModal();
                showToast("danger", "No facility is selected.");
                return;
            }

            const selectedRadio = form.querySelector(
                'input[name="condition"]:checked'
            );

            if (!selectedRadio || !equipStatus.value) {
                closeConfirmModal();
                showToast("warning", "Please complete the required fields.");
                return;
            }

            updateCondition(selectedRow, selectedRadio.value);

            selectedRow.dataset.notes = notes.value;
            selectedRow.dataset.status = equipStatus.value;

            panelCondition.textContent =
                "Current condition: " + selectedRadio.value;

            const savedName = facilityName.textContent;

            closeConfirmModal();

            status.textContent = "Assessment updated for " + savedName + ".";

            renderTable();

            showToast("success", "Facility condition saved successfully.");
        });

        // Close the modal when clicking outside it.
        confirmModal.addEventListener("click", event => {
            if (event.target === confirmModal) {
                closeConfirmModal();
            }
        });

        // Close the modal using Escape.
        document.addEventListener("keydown", event => {
            if (
                event.key === "Escape" &&
                !confirmModal.classList.contains("hidden")
            ) {
                closeConfirmModal();
            }
        });

        // Search.
        searchInput.addEventListener("input", () => {
            currentPage = 1;
            renderTable();
        });

        // Condition filter.
        conditionFilter.addEventListener("change", () => {
            currentPage = 1;
            renderTable();
        });

        // Previous page.
        prevButton.addEventListener("click", () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });

        // Next page.
        nextButton.addEventListener("click", () => {
            const totalPages = Math.ceil(
                getFilteredRows().length / rowsPerPage
            );

            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        });

        // Initial table rendering.
        renderTable();
    });
    </script>
</body>
</html>
