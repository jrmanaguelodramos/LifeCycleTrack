<?php
    $currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit">

<aside class="sidebar ">

    <div class="sidebar-header flex items-center gap-3">
        <img src="../img/logo.png"
             alt="LifeCycle Track Logo"
             class="w-13 h-13 object-contain">
        <h2>LifeCycle Track</h2>
    </div>

    <hr class="h-1 bg-white border-none mb-5" />
    <p class="text-xs mb-5 ml-2">Menu</p>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php"
               class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>

            </a>
        </li>

        <?php
            $facilityPages = [
                'facilities_list.php',
                'viewfacility.php'
            ];?>

        <li>
            <a href="facilities_list.php"
               class="<?= in_array($currentPage, $facilityPages) ? 'active' : '' ?>">
                <i class="fa-solid fa-building-circle-exclamation"></i>
                <span>Facilities</span>
            </a>
        </li>

        <?php
            $equipmentPages = [
                'equipments_list.php',
                'viewequip.php'
            ];?>

        <li>
            <a href="equipments_list.php"
               class="<?= in_array($currentPage, $equipmentPages) ? 'active' : '' ?>">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <span>Equipment</span>

            </a>
        </li>

       <?php
            $conditionPages = [
                'facility_condition.php',
                'equip_condition.php'
            ];
            $isConditionPage = in_array($currentPage, $conditionPages);?>

        <!--cobndition dropdown-->
        <li>
            <button id="condition-toggle" class="menu-button">
                <span>
                    <i class="fa-solid fa-clipboard-check"></i>
                    Condition
                </span>
                <i id="condition-arrow"
                   class="fa-solid fa-chevron-down <?= $isConditionPage ? 'rotate' : '' ?>">
                </i>
            </button>

            <!--facility cond-->
            <ul id="condition-dropdown"
                class="submenu <?= $isConditionPage ? 'show' : '' ?>">
                <li>
                    <a href="facility_condition.php"
                       class="<?= $currentPage == 'facility_condition.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-building"></i>
                        Facility
                    </a>
                </li>

                <!--equipment cond-->
                <li>
                    <a href="equip_condition.php"
                       class="<?= $currentPage == 'equip_condition.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        Equipment
                    </a>
                </li>
            </ul>
        </li>

        <!--report dropdown-->
        <?php
            $reportPages = [
                'trend.php',
                'maintenance.php',
                'downtime.php',
                'lifecycle.php'
            ];
            $isReportPage = in_array($currentPage, $reportPages);?>

        <li>
            <button id="reports-toggle" class="menu-button">
                <span>
                    <i class="fa-solid fa-chart-simple"></i>
                    Reports
                </span>
                <i id="reports-arrow"
                   class="fa-solid fa-chevron-down <?= $isReportPage ? 'rotate' : '' ?>">
                </i>
            </button>

            <ul id="reports-dropdown"
                class="submenu <?= $isReportPage ? 'show' : '' ?>">

                <li>
                    <a href="trend.php"
                       class="<?= $currentPage == 'trend.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-line"></i>
                        Trend Analysis
                    </a>
                </li>

                <li>
                    <a href="maintenance.php"
                       class="<?= $currentPage == 'maintenance.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        Maintenance Analysis
                    </a>
                </li>

                <li>
                    <a href="downtime.php"
                       class="<?= $currentPage == 'downtime.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-clock"></i>
                        Downtime Analysis
                    </a>
                </li>

                <li>
                    <a href="lifecycle.php"
                       class="<?= $currentPage == 'lifecycle.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-pie"></i>
                        LifeCycle Score
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <!--profile-->
    <div class="sidebar-profile-wrapper">
        <div id="profile-menu" class="profile-menu">

            <button type="button"
                    id="logout-btn"
                    class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>

            </button>

        </div>

        <button id="profile-toggle"
                class="sidebar-profile">

            <div class="profile-icon">
                <i class="fa-solid fa-user"></i>
            </div>

            <div class="profile-info">
                <strong>Mang Juan</strong>
                <span>Admin</span>
            </div>

            <i id="profile-arrow"
            class="fa-solid fa-chevron-up profile-arrow">
            </i>
        </button>

    </div>

    <div id="logout-modal"
        class="hidden fixed inset-0 z-[99999] items-center justify-center bg-black/40 backdrop-blur-sm">

        <div class="w-[380px] rounded-2xl bg-white p-7 text-center shadow-2xl">

            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-[#155B92]">
                <i class="fa-solid fa-right-from-bracket text-2xl"></i>
            </div>

            <h3 class="mb-2 text-xl font-semibold text-gray-800">
                Confirm Logout
            </h3>

            <p class="mb-6 text-sm text-gray-500">
                Are you sure you want to log out?
            </p>

            <div class="flex justify-center gap-3">

                <button type="button"
                        id="cancel-logout"
                        class="w-28 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                    Cancel
                </button>

                <a href="../auth/login.php"
                class="w-28 rounded-lg bg-[#155B92] px-4 py-2.5 text-center text-sm font-medium text-white transition hover:bg-[#104a78]">
                    Logout
                </a>
            </div>
        </div>
    </div>

</aside>


    <script>
        //condition 
        const conditionToggle = document.getElementById('condition-toggle');
        const conditionDropdown = document.getElementById('condition-dropdown');
        const conditionArrow = document.getElementById('condition-arrow');

        //reports 
        const reportsToggle = document.getElementById('reports-toggle');
        const reportsDropdown = document.getElementById('reports-dropdown');
        const reportsArrow = document.getElementById('reports-arrow');

        conditionToggle.addEventListener('click', function () {

            const isOpen = conditionDropdown.classList.contains('show');
            reportsDropdown.classList.remove('show');
            reportsArrow.classList.remove('rotate');
            if (isOpen) {
                conditionDropdown.classList.remove('show');
                conditionArrow.classList.remove('rotate');
            } else {
                conditionDropdown.classList.add('show');
                conditionArrow.classList.add('rotate');
            }
        });

        reportsToggle.addEventListener('click', function () {
            const isOpen = reportsDropdown.classList.contains('show');
            conditionDropdown.classList.remove('show');
            conditionArrow.classList.remove('rotate');
            if (isOpen) {
                reportsDropdown.classList.remove('show');
                reportsArrow.classList.remove('rotate');
            } else {
                reportsDropdown.classList.add('show');
                reportsArrow.classList.add('rotate');
            }
        });

        //rpofile
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        const profileArrow = document.getElementById('profile-arrow');
        profileToggle.addEventListener('click', function () {
            profileMenu.classList.toggle('show');
            profileArrow.classList.toggle('rotate');

        });

        //logout modal
        const logoutBtn = document.getElementById('logout-btn');
        const logoutModal = document.getElementById('logout-modal');
        const cancelLogout = document.getElementById('cancel-logout');

        logoutBtn.addEventListener('click', function () {
            profileMenu.classList.remove('show');
            profileArrow.classList.remove('rotate');

            logoutModal.classList.remove('hidden');
            logoutModal.classList.add('flex');
        });

        cancelLogout.addEventListener('click', function () {
            logoutModal.classList.remove('flex');
            logoutModal.classList.add('hidden');
        });

        logoutModal.addEventListener('click', function (event) {
            if (event.target === logoutModal) {
                logoutModal.classList.remove('flex');
                logoutModal.classList.add('hidden');
            }
        });

    </script>
