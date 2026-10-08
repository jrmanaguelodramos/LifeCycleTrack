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
            <div class="col-span-1 h-38.75 bg-white rounded-2xl shadow-md border border-gray-200 p-5"
                style="border-left: 4px solid rgb(14, 58, 99) !important;">
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

            <div class="col-span-1 h-38.75 bg-white rounded-2xl shadow-md border border-gray-200 p-5"
                style="border-left: 4px solid rgb(14, 58, 99) !important;">
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

            <div class="col-span-2 h-38.75 bg-[#FFD2D2] rounded-2xl shadow-md border border-red-200 p-5"
                style="border-left: 4px solid rgb(239, 68, 68) !important;">
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
        
        <!--paconvert nalang ito-->
        <?php
        function lifecycleChart(int $score, array $counts): void {
            $colors = [
                'Good'     => '#2fbd5b',
                'Fair'     => '#ff7900',
                'Poor'     => '#ff0000',
            ];
            $total = array_sum($counts);
            $radius = 44;
            $circumference = 2 * M_PI * $radius;
            $gap = 3;
            $offset = 0;
            ?>
            <div class="flex items-center gap-6 mt-3" style="display:flex;align-items:center;gap:24px;margin-top:12px;">

                <div class="relative w-28 h-28 shrink-0" style="position:relative;width:112px;height:112px;flex-shrink:0;">
                    <svg viewBox="0 0 112 112" class="w-full h-full -rotate-90" style="width:100%;height:100%;transform:rotate(-90deg);"
                         role="img" aria-label="Score <?= $score ?> out of 100">
                        <circle cx="56" cy="56" r="<?= $radius ?>" fill="none" stroke="#EEF2F6" stroke-width="12"/>
                        <?php foreach ($counts as $name => $count):
                            $segment = ($count / $total) * $circumference;
                            $dash = max(0, $segment - $gap);
                        ?>
                            <circle cx="56" cy="56" r="<?= $radius ?>" fill="none"
                                    stroke="<?= $colors[$name] ?>" stroke-width="12" stroke-linecap="round"
                                    stroke-dasharray="<?= round($dash, 2) ?> <?= round($circumference - $dash, 2) ?>"
                                    stroke-dashoffset="<?= round(-$offset, 2) ?>"/>
                        <?php $offset += $segment; endforeach; ?>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                        <span class="text-3xl font-black text-gray-900 leading-none"><?= $score ?></span>
                        <span class="text-xs text-gray-500 mt-1">out of 100</span>
                    </div>
                </div>

                <div class="flex-1 space-y-2" style="flex:1;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($counts as $name => $count): ?>
                        <div class="items-center text-sm" style="display:grid;grid-template-columns:84px 1fr 28px;align-items:center;gap:12px;">
                            <span class="flex items-center gap-2 text-gray-600" style="display:flex;align-items:center;gap:8px;white-space:nowrap;">
                                <span class="inline-block rounded-full"
                                      style="display:inline-block;width:10px;height:10px;border-radius:9999px;background: <?= $colors[$name] ?>;"></span>
                                <?= $name ?>
                            </span>
                            <span class="rounded-full bg-gray-100 overflow-hidden" style="display:block;height:8px;border-radius:9999px;background:#EEF2F6;overflow:hidden;">
                                <span class="block h-full rounded-full"
                                      style="display:block;height:100%;border-radius:9999px;width: <?= round(($count / $total) * 100) ?>%; background: <?= $colors[$name] ?>;"></span>
                            </span>
                            <span class="text-right font-bold text-gray-900" style="text-align:right;font-weight:700;"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
         // nasa database                
        $facilitiesScore  = 74;
        $facilitiesCounts = ['Good' => 9, 'Fair' => 7, 'Poor' => 5];
        $equipmentsScore  = 66;
        $equipmentsCounts = ['Good' => 9, 'Fair' => 7, 'Poor' => 7];
        ?>

        <!--LifeCycle iskor-->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="col-span-2 min-h-38.75 rounded-2xl shadow-md border border-gray-200 p-5"
            style="border-left: 4px solid rgb(14, 58, 99) !important;">

                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Facilities LifeCycle Score
                    </h2>
                </div>

                <!--import nalang nung pinakachart-->
                <?php lifecycleChart($facilitiesScore, $facilitiesCounts); ?>

            </div>

            <div class="col-span-2 min-h-38.75 rounded-2xl shadow-md border border-gray-200 p-5"
            style="border-left: 4px solid rgb(14, 58, 99) !important;">

                <div class="flex items-start justify-between">
                    <h2 class="text-base font-bold text-gray-900">
                        Equipments LifeCycle Score
                    </h2>
                </div>

                <!--import nalang nung pinakachart-->
                <?php lifecycleChart($equipmentsScore, $equipmentsCounts); ?>
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