<?php
    $currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit">

<aside class="sidebar">

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
    <div class="sidebar-profile">

        <div class="profile-icon">
            <i class="fa-solid fa-user"></i>
        </div>

        <div class="profile-info">
            <!--pa convert nalang to pag may database na-->
            <strong>Mang Juan</strong>  
            <span>Admin</span>
        </div>
        <i class="fa-solid fa-chevron-up profile-arrow"></i>
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
    </script>
