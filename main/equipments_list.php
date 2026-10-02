<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../src/output.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/equipments_list.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <h1 class="text-3xl font-bold ">Equipments List</h1>
        <p class="mt-2 text-black-100 mb-8">View and manage all registered equipment and their current status</p>


        <div class="flex items-end gap-4 mb-5">
        <!--eto yung search bar-->
        <div class="w-full max-w-sm min-w-50">
            <div class="relative flex items-center">
                <i class="fa-solid fa-magnifying-glass absolute w-5 h-5 top-2.5 left-2.5 text-slate-600"></i>

                <input
                    class="w-full bg-transparent placeholder:text-slate-400 text-slate-700 text-sm border border-slate-200 rounded-md pl-10 pr-3 py-2 transition duration-300 ease focus:outline-none focus:border-slate-400 hover:border-slate-300 shadow-sm focus:shadow"
                    placeholder="Search by name or id..."
                />
            </div>
        </div>

        <!--eto yung filter-->
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-700">
                Condition
            </label>

            <select class="w-28 h-9 px-2 text-sm text-gray-800 bg-white border border-slate-300 rounded-md focus:outline-none focus:border-[#155B92] cursor-pointer mr-120">
                <!--base nalang ditor-->
                <option value="all">All</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
            </select>
        </div>

        <div class="flex flex-col ">
            <button id="openEquipmentModal" class="border-[#15588F] p-2 rounded-[10px] bg-[#15588F] pr-3 text-white cursor-pointer hover:bg-[#104a78]">
                <i class="fa-solid fa-circle-plus mr-3 ml-2 text-white "></i> Add Equipment
            </button>
        </div>

    </div>

    <div class="equipment-table-container">

    <table class="equipment-table">
        <thead>
            <tr>
                <th><i class="fa-solid fa-id-card"></i> ID</th>
                <th><i class="fa-solid fa-tag"></i> Name</th>
                <th><i class="fa-solid fa-location-dot"></i>  Location</th>
                <th><i class="fa-solid fa-shield-halved"></i> Condition</th>
                <th><i class="fa-solid fa-gear"></i> Actions</th>
            </tr>
        </thead>

        <tbody>
            <!-- sample data lang naman to -->
            <tr>
                <td>0001</td>
                <td>Printer</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition good">
                        <span></span>
                        Good
                    </span>
                </td>
                <!--view button-->
                <td>
                     <a href="viewequip.php">
                        <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button></a>  
                </td>
            </tr>

            <tr>
                <td>0002</td>
                <td>Arconditioner</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition good">
                        <span></span>
                        Good
                    </span>
                </td>
                <td>
                    <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button>
                </td>
            </tr>

            <tr>
                <td>0003</td>
                <td>Generator</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition fair">
                        <span></span>
                        Fair
                    </span>
                </td>
                <td>
                    <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button>
                </td>
            </tr>

            <tr>
                <td>0004</td>
                <td>Water Dispenser</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition good">
                        <span></span>
                        Good
                    </span>
                </td>
                <td>
                    <a href="">
                        <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button></a>   
                </td>
            </tr>

            <tr>
                <td>0005</td>
                <td>Grass Cutter</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition poor">
                        <span></span>
                        Poor
                    </span>
                </td>
                <td>
                    <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button>
                </td>
            </tr>

            <tr>
                <td>0006</td>
                <td>Computer</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition good">
                        <span></span>
                        Good
                    </span>
                </td>
                <td>
                    <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button>
                </td>
            </tr>

            <tr>
                <td>0007</td>
                <td>Wheel Barrow</td>
                <td>Secretary's Office</td>
                <td>
                    <span class="condition good">
                        <span></span>
                        Good
                    </span>
                </td>
                <td>
                    <button class="view-button">
                        <i class="fa-solid fa-eye"></i>
                        View
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- edit nalang tong pagination -->
    <div class="pagination">

        <button class="pagination-btn">
            <i class="fa-solid fa-chevron-left"></i>
            Previous
        </button>

        <button class="pagination-number active">1</button>
        <button class="pagination-number">2</button>
        <button class="pagination-number">3</button>

        <button class="pagination-btn">
            Next
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
        

    </main>

    <?php
            $modal = __DIR__ . '/addequipmodal.php';

            if (file_exists($modal)) {
                include $modal;
            } else {
                echo "File NOT found: " . $modal;
    }?>
    
</body>
</html>