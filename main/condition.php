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
        <h1 class="text-3xl font-bold"> Condition Assesment</h1>
        <p class="mt-2 text-gray-700 mb-1">View and manage  the current condition  of facilities and equipment </p>

       <div class="flex flex-row gap-5 w-full mt-6">
            <div class="w-3/5  overflow-hidden">
                <div class="flex items-end gap-4 p-5">
                    <!--search-->
                    <div class="w-full max-w-sm">
                        <div class="relative flex items-center">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 text-slate-600"></i>
                            <input
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
                        <tbody>
                            <tr>
                                <td>0001</td>
                                <td>Printer</td>
                                <td>Equipment</td>
                                <td>
                                    <span class="condition good">
                                        <span></span>
                                        Good
                                    </span>
                                </td>
                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>

                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>0002</td>
                                <td>Airconditioner</td>
                                <td>Equipment</td>
                                <td>
                                    <span class="condition good">
                                        <span></span>
                                        Good
                                    </span>
                                </td>
                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>
                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>0003</td>
                                <td>Generator</td>
                                <td>Equipment</td>

                                <td>
                                    <span class="condition fair">
                                        <span></span>
                                        Fair
                                    </span>
                                </td>

                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>

                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>0004</td>
                                <td>Water Dispenser</td>
                                <td>Equipment</td>

                                <td>
                                    <span class="condition good">
                                        <span></span>
                                        Good
                                    </span>
                                </td>

                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>

                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>0005</td>
                                <td>Grass Cutter</td>
                                <td>Equipment</td>
                                <td>
                                    <span class="condition poor">
                                        <span></span>
                                        Poor
                                    </span>
                                </td>
                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>
                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>0006</td>
                                <td>Computer</td>
                                <td>Equipment</td>

                                <td>
                                    <span class="condition good">
                                        <span></span>
                                        Good
                                    </span>
                                </td>

                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>

                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>0007</td>
                                <td>Wheel Barrow</td>
                                <td>Equipment</td>
                                <td>
                                    <span class="condition good">
                                        <span></span>
                                        Good
                                    </span>
                                </td>
                                <td>
                                    <button class="view-button">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </button>
                                    <button class="view-button">
                                        <i class="fa-solid fa-eye"></i>
                                        View
                                    </button>
                                </td>
                            </tr>
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
                    <h2 class="text-xl font-semibold text-white pl-4 pt-3 pb-4 bg-[#155B92]">Facility/Equipment Details</h2>
                    <div class="ml-4">
                             <form id="assessmentForm" class="p-4 space-y-6">
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

                                <!--Condition Assessment -->
                                <div>
                                <h3 class="text-xl font-bold text-black mb-3">Condition Assessment</h3>
                                <div class="flex items-center space-x-6">

                                    <label class="flex items-center space-x-2  text-sm font-bold text-black">
                                        <input type="radio" name="condition" value="Good" checked class="w-6 h-6 cursor-pointer text-green-500 accent-green-500">
                                        <span class="text-l">Good</span>
                                    </label>

                                    <label class="flex items-center space-x-2 text-sm font-bold text-black">
                                        <input type="radio" name="condition" value="Fair" class="w-6 h-6  cursor-pointer text-orange-500 accent-orange-500">
                                        <span class="text-l">Fair</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm font-bold text-black">
                                        <input type="radio" name="condition" value="Poor" class="w-6 h-6  cursor-pointer text-red-500 accent-red-500">
                                        <span class="text-l">Poor</span>
                                    </label>
                                </div>
                                </div>

                                <!-- notes -->
                                <div>
                                    <label for="notes" class="block text-xl font-bold text-black mb-2">Notes/Remarks</label>
                                    <textarea id="notes" rows="5" placeholder="Enter Notes/Remarks..." class="w-full p-3 border border-gray-300 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none"></textarea>
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
   
</body>
</html>