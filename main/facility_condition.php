<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Condition Assessment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition.css">
    <link rel="stylesheet" href="../css/facility_condition.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="flex flex-row gap-5 w-full condition-layout">
            <div class="w-3/5 overflow-hidden condition-left">
                <h1 class="text-3xl font-bold">Facility Condition Assessment</h1>
                <p class="mt-2 text-gray-700 mb-1">View and manage the current condition of facilities.</p>
                <p id="status" class="sr-only" aria-live="polite"></p>

                <div class="flex items-end gap-4 p-5 flex-wrap">
                    <div class="w-full max-w-sm">
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 text-slate-600"></i>
                            <input id="searchInput" type="text"
                                class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 shadow-sm focus:outline-none focus:border-[#155B92]"
                                placeholder="Search by name or ID...">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="conditionFilter" class="text-xs font-medium text-gray-700">Condition</label>
                        <select id="conditionFilter"
                            class="w-28 h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer">
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
                            <!--sample data lang -->
                            <tr data-notes="">
                                <td>0001</td>
                                <td>Reception Hall</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <tr data-notes="">
                                <td>0002</td>
                                <td>Gymnasium</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <tr data-notes="">
                                <td>0003</td>
                                <td>Secretary's Office</td>
                                <td><span class="condition fair"><span></span>Fair</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <tr data-notes="">
                                <td>0004</td>
                                <td>Cafeteria</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <tr data-notes="">
                                <td>0005</td>
                                <td>Covered Court</td>
                                <td><span class="condition poor"><span></span>Poor</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <tr data-notes="">
                                <td>0006</td>
                                <td>Admin Office</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <tr data-notes="">
                                <td>0007</td>
                                <td>Parking Area</td>
                                <td><span class="condition good"><span></span>Good</span></td>
                                <td>
                                    <button type="button" class="view-button edit-button"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <a href="viewfacility.php" class="view-button view-button-action"><i class="fa-solid fa-eye"></i> View</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p id="noResults" class="hidden text-center text-gray-500 py-6">No facility matches your search.</p>
                </div>

                <div class="pagination" id="pagination">
                    <button type="button" class="pagination-btn" id="prevPage"><i class="fa-solid fa-chevron-left"></i> Previous</button>
                    <div id="pageNumbers" class="flex items-center gap-2"></div>
                    <button type="button" class="pagination-btn" id="nextPage">Next <i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            <div class="w-2/5 bg-white border border-gray-200 rounded-xl shadow-sm assessment-panel">
                <h2 class="text-xl font-semibold text-white pl-4 pt-3 pb-4 bg-[#155B92] rounded-t-xl">facility Details</h2>

                <div class="p-4">
                    <div id="emptyState" class="empty-state">
                        <i class="fa-regular fa-hand-pointer"></i>
                        <p class="empty-title">No facility selected</p>
                        <p class="empty-text">Click <strong>Edit</strong> on a facility in the list to view and update its condition.</p>
                    </div>

                    <form id="assessmentForm" class="space-y-6" hidden>
                        <div class="flex items-center gap-4">
                            <div class="w-1/2 p-2 border border-gray-200 rounded-xl shadow-sm flex items-center justify-center">
                                <img id="image" src="../img/logo.png" alt="facility image" class="block w-full h-40 object-contain">
                            </div>
                            <div class="w-1/2 min-w-0">
                                <p class="text-xs text-gray-500">facility ID</p>
                                <h2 id="id" class="text-3xl font-black text-black"></h2>
                                <p id="name" class="text-sm font-bold text-gray-800 mt-1"></p>
                                <p id="panelCondition" class="text-sm text-gray-500 mt-2"></p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-black mb-3">Condition Assessment</h3>
                            <div class="flex items-center flex-wrap gap-4">
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Good" checked class="w-5 h-5 cursor-pointer accent-green-500">
                                    <span>Good</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Fair" class="w-5 h-5 cursor-pointer accent-orange-500">
                                    <span>Fair</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Poor" class="w-5 h-5 cursor-pointer accent-red-500">
                                    <span>Poor</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="notes" class="block text-xl font-bold text-black mb-2">Notes / Remarks</label>
                            <textarea id="notes" rows="5" placeholder="Enter notes or remarks..."
                                class="w-full p-3 border border-gray-300 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-[#155B92] text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 hover:bg-[#104a78] transition-colors cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Assessment</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

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
        const noResults = document.getElementById("noResults");
        const status = document.getElementById("status");
        const prevButton = document.getElementById("prevPage");
        const nextButton = document.getElementById("nextPage");
        const pageNumbers = document.getElementById("pageNumbers");
        const rows = Array.from(table.querySelectorAll("tbody tr"));

        const rowsPerPage = 10;
        let currentPage = 1;
        let selectedRow = null;

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

        function openfacility(row) {
            emptyState.hidden = true;
            form.hidden = false;

            selectedRow = row;
            const cells = row.querySelectorAll("td");
            const condition = getCondition(row);

            facilityId.textContent = cells[0].textContent.trim();
            facilityName.textContent = cells[1].textContent.trim();
            panelCondition.textContent = "Current condition: " + condition;
            notes.value = row.dataset.notes || "";

            const radio = form.querySelector('input[name="condition"][value="' + condition + '"]');
            if (radio) radio.checked = true;

            rows.forEach(r => r.classList.remove("selected-row"));
            row.classList.add("selected-row");
            status.textContent = "Napili ang facility ID " + cells[0].textContent.trim() + ".";
        }

        rows.forEach(row => {
            row.querySelector(".edit-button")?.addEventListener("click", () => openfacility(row));

            row.querySelector(".view-button-action")?.addEventListener("click", event => {
                openfacility(row);
                status.textContent = "Ipinapakita ang detalye ng facility.";
            });
        });

        function getFilteredRows() {
            const search = searchInput.value.trim().toLowerCase();
            const selectedCondition = conditionFilter.value.toLowerCase();

            return rows.filter(row => {
                const cells = row.querySelectorAll("td");
                const id = cells[0].textContent.trim().toLowerCase();
                const name = cells[1].textContent.trim().toLowerCase();
                const condition = getCondition(row).toLowerCase();
                const matchesSearch = id.includes(search) || name.includes(search);
                const matchesCondition = selectedCondition === "all" || condition === selectedCondition;
                return matchesSearch && matchesCondition;
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

        form.addEventListener("submit", event => {
            event.preventDefault();

            if (!selectedRow) {
                status.textContent = "Pumili muna ng facility gamit ang Edit.";
                return;
            }

            const selectedRadio = form.querySelector('input[name="condition"]:checked');
            if (!selectedRadio) {
                status.textContent = "Pumili muna ng condition.";
                return;
            }

            updateCondition(selectedRow, selectedRadio.value);
            selectedRow.dataset.notes = notes.value;
            panelCondition.textContent = "Current condition: " + selectedRadio.value;

            status.textContent = "Na-update ang assessment ng " + facilityName.textContent + ".";
            renderTable();
        });

        renderTable();
    });
    </script>
</body>
</html>