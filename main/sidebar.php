<?php
    $currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit">
<!-- NEW: fonts used by the redesigned sidebar (Bricolage Grotesque + Figtree) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

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
                 <i class="ti ti-layout-dashboard" style="font-size: 22px !important;"></i>
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
               <i class="ti ti-buildings" style="font-size: 22px !important;"></i>
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
               <i class="ti ti-tool" style="font-size: 22px !important;"></i>
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
                     <i class="ti ti-clipboard-check" style="font-size: 22px !important;"></i>
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
                   <i class="ti ti-chart-bar" style="font-size: 22px !important;"></i>
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
            <button type="button" onclick="openLogoutModal(event)"
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
</aside>

   <div id="logoutModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-icon">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </div>

                <h2>Confirm Logout</h2>
                <p style="color:black;">Are you sure you want to log out?</p>

                <div class="actions">
                    <button onclick="closeLogoutModal()">Cancel</button>
                    <button onclick="confirmLogout()">Logout</button>
                </div>
            </div>
    </div>

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

         function openLogoutModal(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const modal = document.getElementById('logoutModal');

            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');

            if (modal) {
                modal.style.display = 'none';
            }
        }

        function confirmLogout() {
            window.location.href = '../auth/logout.php';
        }
    </script>