<?php
session_start();

if (empty($_SESSION['access_token'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body class=" text-black">

    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold ">
            Dashboard
        </h1>
        <p class="mt-2 text-black-100 mb-8">
            Overview of facilities, equipment and current condition status
        </p>


       <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="col-span-1 h-38.75 bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Total Facilities
                    </h2>

                    <a href="facilities_list.php" class="inline-block">
                        <div class="w-9 h-9 rounded-full bg-[#155B92] flex items-center justify-center cursor-pointer">
                            <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                        </div>
                    </a>

                </div>

                <p class="text-7xl font-bold text-black mt-3">
                    21 <!--nasa database-->
                </p>
            </div>

            <div class="col-span-1 h-38.75 bg-white rounded-2xl shadow-md border border-gray-200 p-5">
                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Total Equipments
                    </h2>

                    <a href="equipments_list.php" class="inline-block">
                        <div class="w-9 h-9 rounded-full bg-[#155B92] flex items-center justify-center cursor-pointer">
                            <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                        </div>
                    </a>
                </div>

                <p class="text-7xl font-bold text-black mt-3">
                    23 <!--nasa database-->
                </p>
            </div>

            <div class="col-span-2 h-38.75 bg-[#FFD2D2] rounded-2xl shadow-md border border-red-200 p-5">
                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Condition Alerts
                    </h2>

                    <div class="w-9 h-9 rounded-full bg-[#155B92] flex items-center justify-center cursor-pointer">
                        <i class="fa-solid fa-arrow-up-right-from-square text-white text-sm"></i>
                    </div>
                </div>

                <p class="text-7xl font-bold text-red-600 text-center mt-1">
                    12            <!--nasa database-->
                </p>
            </div>
        </div>
        
        <!--LifeCycle iskor-->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="col-span-2 h-38.75 rounded-2xl shadow-md border border-gray-200 p-5">

                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Facilities LifeCycle Score
                    </h2>
                </div>

                <p class="text-7xl font-bold text-red-600 text-center mt-1">
                     <!--import nalang nung pinakachart-->
                </p>

            </div>

            <div class="col-span-2 h-38.75 rounded-2xl shadow-md border border-gray-200 p-5">

                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Equipments LifeCycle Score
                    </h2>
                </div>
                <p class="text-7xl font-bold text-red-600 text-center mt-1">
                      <!--import nalang nung pinakachart-->
                </p>
            </div>
        </div>


        <h1 class="text-xl font-bold mb-2">Recent Activity</h1>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-calendar-minus"></i>ㅤDate</th>
                        <th><i class="fa-solid fa-tag"></i>ㅤName</th>
                        <th><i class="fa-solid fa-gauge-high"></i>ㅤCategory</th>
                        <th><i class="fa-solid fa-gear"></i>ㅤActions</th>
                    </tr>
                </thead>

                 <!--sample data-->
                <tbody>
                    <tr>
                        <td>09-20-2026</td>
                        <td>Printer</td>
                        <td>Equipment</td>
                        <td>Updated Condition</td>
                    </tr>

                    <tr>
                        
                        <td>09-19-2026</td>
                        <td>Airconditioner</td>
                        <td>Equipment</td>
                        <td>Added Remarks</td>
                    </tr>

                    <tr>
                        <td>09-18-2026</td>
                        <td>Reception Hall</td>
                        <td>Facility</td>
                        <td>Added Remarks</td>
                    </tr>

                    <tr>
                        <td>09-17-2026</td>
                        <td>Comfort Room</td>
                        <td>Facility</td>
                        <td>Added Remarks</td>
                    </tr>

                    <!--dito pala kahit 7 rows lang makikita, meaning mawawala na sa recent activity yung mga past activity-->

                </tbody>
            </table>
        </div>
        



    </main>
</body>
</html>