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
    && ($_POST['form_action'] ?? '') === 'save_assessment'
) {

    $id = filter_input(INPUT_POST, 'asset_id', FILTER_VALIDATE_INT);
    $condition = trim((string)($_POST['condition'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));

    if (!$id || !in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
        $_SESSION['assessment_message'] = 'Invalid equipment or condition.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE assets
     SET "condition" = :condition, remarks = :remarks
     WHERE id = :id
       AND asset_type IN (\'equipment\', \'facility\')'
        );

        $stmt->execute([
            'condition' => $condition,
            'remarks' => $notes !== '' ? $notes : null,
            'id' => $id
        ]);

        $_SESSION['assessment_message'] = 'Assessment saved successfully.';
    } catch (Throwable $e) {
        error_log('Assessment save error: ' . $e->getMessage());
        $_SESSION['assessment_message'] = 'Unable to save the assessment.';
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $pdo->query(
    'SELECT id, name, asset_type, "condition", location,
            brand, model, date_acquired, remarks
     FROM assets
     WHERE asset_type IN (\'equipment\', \'facility\')
     ORDER BY id DESC'
);

$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/condition.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold">Equipment Condition Assesment</h1>
        <p class="mt-2 text-gray-700 mb-1">View and manage the current condition of facilities </p>

        <?php if (!empty($_SESSION['assessment_message'])): ?>
            <div class="mb-4 rounded-md bg-blue-50 p-3 text-sm text-[#155B92]">
                <?= htmlspecialchars($_SESSION['assessment_message']) ?>
            </div>
            <?php unset($_SESSION['assessment_message']); ?>
        <?php endif; ?>

        <div class="flex flex-row gap-5 w-full mt-6">
            <div class="w-3/5  overflow-hidden">
                <div class="flex items-end gap-4 p-5">
                    <!--search-->
                    <div class="w-full max-w-sm">
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 text-slate-600"></i>
                            <input
                                id="searchAsset"
                                type="text"
                                class="w-full bg-transparent placeholder:text-slate-400
                                    text-slate-700 text-sm
                                    border border-slate-200 rounded-md
                                    pl-10 pr-3 py-2
                                    transition duration-300 ease
                                    focus:outline-none focus:border-slate-400
                                    hover:border-slate-300
                                    shadow-sm focus:shadow"
                                placeholder="Search by name or id...">
                        </div>
                    </div>

                    <!--condition -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-700">Condition</label>
                        <select
                            id="filterCondition"
                            class="w-28 h-9 px-2 text-sm text-gray-800
                                bg-white border border-slate-300
                                rounded-md
                                focus:outline-none
                                focus:border-[#155B92]
                                cursor-pointer">
                            <option value="all">All</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-700">Category</label>
                        <select
                            id="filterCategory"
                            class="w-28 h-9 px-2 text-sm text-gray-800
                                bg-white border border-slate-300
                                rounded-md
                                focus:outline-none
                                focus:border-[#155B92]
                                cursor-pointer">
                            <option value="all">All</option>
                            <option value="facility">Facility</option>
                            <option value="equipment">Equipment</option>
                        </select>
                    </div>
                </div>

                <!--left panel-->
                <div class="condition-table-container">
                    <table class="condition-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-id-card"></i>ID</th>
                                <th><i class="fa-solid fa-tag"></i>Name</th>
                                <th><i class="fa-solid fa-border-all"></i>Category</th>
                                <th><i class="fa-solid fa-shield-halved"></i>Condition</th>
                                <th><i class="fa-solid fa-gear"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assetTableBody">
                            <?php foreach ($assets as $asset): ?>
                                <?php
                                $condition = ucfirst(strtolower(
                                    (string)($asset['condition'] ?? 'Good')
                                ));

                                if (!in_array($condition, ['Good', 'Fair', 'Poor'], true)) {
                                    $condition = 'Good';
                                }

                                $category = strtolower((string)$asset['asset_type']);
                                ?>

                                <tr
                                    class="asset-row cursor-pointer hover:bg-slate-50"
                                    data-id="<?= (int)$asset['id'] ?>"
                                    data-name="<?= htmlspecialchars($asset['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"
                                    data-condition="<?= htmlspecialchars($condition, ENT_QUOTES, 'UTF-8') ?>"
                                    data-notes="<?= htmlspecialchars($asset['remarks'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-location="<?= htmlspecialchars($asset['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-brand="<?= htmlspecialchars($asset['brand'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-model="<?= htmlspecialchars($asset['model'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <td><?= htmlspecialchars((string)$asset['id']) ?></td>
                                    <td><?= htmlspecialchars($asset['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($category)) ?></td>
                                    <td>
                                        <span class="condition <?= strtolower($condition) ?>">
                                            <span></span>
                                            <?= htmlspecialchars($condition) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="view-button edit-asset">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <button type="button" class="view-button view-asset">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- eto pagination -->
                <div class="pagination">
                    <button class="pagination-btn"><i class="fa-solid fa-chevron-left"></i>Previous</button>
                    <button class="pagination-number active">1</button>
                    <button class="pagination-number">2</button>
                    <button class="pagination-number">3</button>
                    <button class="pagination-btn">Next<i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            <!--eto yung sa right panel-->
            <div class="w-2/5 bg-white border border-gray-200 rounded-xl  shadow-sm">
                <h2 id="detailsTitle" class="text-xl font-semibold text-white pl-4 pt-3 pb-4 bg-[#155B92]">
                    Equipment Details
                </h2>
                <div class="ml-4">
                    <form id="assessmentForm" method="POST" class="p-4 space-y-6">
                        <input type="hidden" name="form_action" value="save_assessment">
                        <input type="hidden" name="asset_id" id="asset_id">
                        <div class="flex items-center space-x-4">

                            <!-- eto yung sa image -->
                            <div class="w-1/2 p-2 border border-gray-200 rounded-xl shadow-sm flex items-center justify-center">
                                <img id="image" src="../img/logo.png" alt="logo" class="block w-full h-40 object-contain">


                            </div>
                            <!-- pati sa name and id -->
                            <div class="w-1/2">
                                <h2 id="id" class="text-3xl font-black text-black">0001</h2>
                                <p id="name" class="text-sm font-bold text-gray-800 mt-1">Printing Machine</p>
                            </div>
                        </div>

                        <!--condition assessment -->
                        <div>
                            <h3 class="text-xl font-bold text-black mb-3">Condition Assessment</h3>
                            <div class="flex items-center space-x-6">

                                <label class="flex items-center space-x-2  text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Good" checked class="w-5 h-5 cursor-pointer text-green-500 accent-green-500">
                                    <span class="text-l">Good</span>
                                </label>

                                <label class="flex items-center space-x-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Fair" class="w-5 h-5  cursor-pointer text-orange-500 accent-orange-500">
                                    <span class="text-l">Fair</span>
                                </label>
                                <label class="flex items-center space-x-2 text-sm font-bold text-black">
                                    <input type="radio" name="condition" value="Poor" class="w-5 h-5  cursor-pointer text-red-500 accent-red-500">
                                    <span class="text-l">Poor</span>
                                </label>
                            </div>
                        </div>

                        <!-- notes -->
                        <div>
                            <label for="notes" class="block text-xl font-bold text-black mb-2">Notes/Remarks</label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="5"
                                placeholder="Enter Notes/Remarks..."
                                class="w-full p-3 border border-gray-300 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-[#155B92] text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center space-x-2 hover:bg-[#104a78] transition-colors cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Assessment</span>
                        </button>
                    </form>
                </div>
            </div>

        </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = [...document.querySelectorAll('.asset-row')];
            const search = document.getElementById('searchAsset');
            const conditionFilter = document.getElementById('filterCondition');
            const categoryFilter = document.getElementById('filterCategory');
            const form = document.getElementById('assessmentForm');

            const idField = document.getElementById('asset_id');
            const nameField = document.getElementById('name');
            const displayId = document.getElementById('id');
            const notesField = document.getElementById('notes');
            const title = document.getElementById('detailsTitle');

            function selectAsset(row) {
                rows.forEach(r => r.classList.remove('bg-blue-50'));
                row.classList.add('bg-blue-50');

                idField.value = row.dataset.id;
                displayId.textContent = row.dataset.id;
                nameField.textContent = row.dataset.name;
                notesField.value = row.dataset.notes || '';

                title.textContent =
                    row.dataset.category === 'facility' ?
                    'Facility Details' :
                    'Equipment Details';

                const radio = form.querySelector(
                    `input[name="condition"][value="${row.dataset.condition}"]`
                );

                if (radio) radio.checked = true;
            }

            function filterAssets() {
                const query = search.value.trim().toLowerCase();
                const selectedCondition = conditionFilter.value.toLowerCase();
                const selectedCategory = categoryFilter.value.toLowerCase();

                rows.forEach(row => {
                    const matchesSearch =
                        row.dataset.name.toLowerCase().includes(query) ||
                        row.dataset.id.includes(query);

                    const matchesCondition =
                        selectedCondition === 'all' ||
                        row.dataset.condition.toLowerCase() === selectedCondition;

                    const matchesCategory =
                        selectedCategory === 'all' ||
                        row.dataset.category === selectedCategory;

                    row.hidden = !(
                        matchesSearch &&
                        matchesCondition &&
                        matchesCategory
                    );
                });
            }

            rows.forEach(row => {
                row.addEventListener('click', () => selectAsset(row));

                row.querySelector('.edit-asset').addEventListener('click', event => {
                    event.stopPropagation();
                    selectAsset(row);
                    notesField.focus();
                });

                row.querySelector('.view-asset').addEventListener('click', event => {
                    event.stopPropagation();
                    selectAsset(row);
                });
            });

            search.addEventListener('input', filterAssets);
            conditionFilter.addEventListener('change', filterAssets);
            categoryFilter.addEventListener('change', filterAssets);

            form.addEventListener('submit', event => {
                if (!idField.value) {
                    event.preventDefault();
                    alert('Please select an equipment or facility first.');
                }
            });

            if (rows.length) selectAsset(rows[0]);
        });
    </script>
</body>

</html>