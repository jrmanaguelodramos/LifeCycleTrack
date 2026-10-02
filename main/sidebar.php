<?php
    $currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit">

<aside class="sidebar">

    <div class="sidebar-header flex items-center gap-3">
        <img src="../img/logo.png" alt="LifeCycle Track Logo" class="w-13 h-13 object-contain">
        <h2>LifeCycle Track</h2>
    </div>

    <hr class="h-1 bg-white border-none mb-5" />
    <p class="text-xs mb-5 ml-2">Menu</p>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
             <?php
                $facilityPages = ['facilities_list.php', 'viewfacility.php'];
            ?>
            <a href="facilities_list.php" 
            class="<?= in_array($currentPage, $facilityPages) ? 'active' : '' ?>">
                <i class="fa-solid fa-building-circle-exclamation"></i>
                <span>Facilities</span>
            </a>
        </li>

        <li>
            <?php
                $equipmentPages = ['equipments_list.php', 'viewequip.php'];
            ?>
           <a href="equipments_list.php"
            class="<?= in_array($currentPage, $equipmentPages) ? 'active' : '' ?>">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <span>Equipment</span>
            </a>
        </li>

        <li>
            <a href="condition.php"class="<?= $currentPage == 'condition.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>Condition</span>
            </a>
        </li>

        <?php
            $reportPages = ['trend.php', 'maintenance.php', 'downtime.php', 'lifecycle.php'];
            $isReportPage = in_array($currentPage, $reportPages);?>

            <li>
                <button id="management-toggle" class="menu-button">
                    <span>
                        <i class="fa-solid fa-chart-simple"></i> Reports</span>
                    <i id="management-arrow" class="fa-solid fa-chevron-down <?= $isReportPage ? 'rotate' : '' ?>"></i>
                </button>

                <ul id="management-dropdown" class="submenu <?= $isReportPage ? 'show' : '' ?>">
                    <li>
                        <a href="trend.php" class="<?= $currentPage == 'trend.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Trend Analysis</a>
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

            <!--to yung sa profile-->
            <div class="sidebar-profile">
                <div class="profile-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="profile-info">
                    <strong>Mang Juan</strong>  <!--nakadepende to sa database sa user--->
                    <span>Admin</span>      <!--nakadepende to sa database--->
                </div>
                <i class="fa-solid fa-chevron-up profile-arrow"></i> <!--bagong tab siguro para dito-->
            </div>

    </aside>

            <script>
                const toggle = document.getElementById('management-toggle');
                const dropdown = document.getElementById('management-dropdown');
                const arrow = document.getElementById('management-arrow');

                toggle.onclick = () => {
                    dropdown.classList.toggle('show');
                    arrow.classList.toggle('rotate');
                };
            </script>