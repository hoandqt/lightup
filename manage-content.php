<?php
session_start();

require_once 'functions.php';

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$pageTitle = "Content Management - LightUp.TV";
$pageDescription = "Admin page to manage site content.";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/manage-content";

include 'header.php';
include 'menu.php';

$itemDataDir = __DIR__ . '/item-data';
$jsonOutputDir = __DIR__ . '/json';
$contentFile = $jsonOutputDir . '/content.json';
$fileList = scandir($itemDataDir);

// Handle Bulk Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedItems = isset($_POST['selected_items']) ? json_decode($_POST['selected_items'], true) : [];
    $currentDate = date('Y-m-d H:i:s');
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];

    if ($action === 'update_content_json') {
        foreach ($selectedItems as $uniqueId) {
            if (!isset($content[$uniqueId])) {
                $content[$uniqueId] = ['unique_id' => $uniqueId];
            }
            $content[$uniqueId]['updated_date'] = $currentDate;

            // Add 'category' if missing
            $filePath = $itemDataDir . '/' . $uniqueId . '/' . $uniqueId . '.json';
            if (file_exists($filePath)) {
                $fileData = json_decode(file_get_contents($filePath), true);
                if (isset($fileData['category'])) {
                    $content[$uniqueId]['category'] = $fileData['category'];
                }
            }
        }

        // Write updated content.json
        if (file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT)) !== false) {
            $successMessage = "Selected items have been updated in content.json.";
        } else {
            $errorMessage = "Error: Failed to write content.json.";
        }
    }
}
?>

<script src="/js/jquery-3.7.1.min.js"></script>
<script src="//cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
document.addEventListener("DOMContentLoaded", function () {
    const contentTypeDropdown = document.getElementById("contentType");
    const table = $('#contentTable').DataTable({
        // Set the default number of rows
        pageLength: 25, // Default to 10 rows per page
        // Customize the "rows per page" dropdown options
        lengthMenu: [
            [10, 25, 50, 100, -1], // Values
            [10, 25, 50, 100, "All"] // Display labels
        ],
        columns: [
            { data: null, orderable: false, render: function (data, type, row) {
                return `<input type="checkbox" class="select-item" value="${row.unique_id}">`;
            }},
            { data: "thumbnail", render: renderThumbnail },
            { data: "title" },
            { data: "user" },
            { data: "updated_date" },
            { data: "edit", orderable: false, render: renderEditButton },
            { data: "delete", orderable: false, render: renderDeleteButton },
        ],
        order: [[4, "desc"]],
        createdRow: function (row, data, dataIndex) {
            $('td:eq(0)', row).attr('align', 'center'); // Apply align="center" to the first td
            $(row).addClass('hover:bg-gray-700'); // Add the hover:bg-gray-700 class to the row
        },
        ajax: {
            url: "", // Default URL will be set dynamically
            dataSrc: "",
        },
    });

    // Load content types
    fetch("/json/content-type.json")
        .then(response => response.json())
        .then(data => {
            // Populate the dropdown
            Object.keys(data).forEach(key => {
                const option = document.createElement("option");
                option.value = key;
                option.textContent = data[key].title;
                contentTypeDropdown.appendChild(option);
            });

            // Set default selection to "video"
            contentTypeDropdown.value = "video";
            loadContentType("video", data);
        });

    // Handle content type change
    contentTypeDropdown.addEventListener("change", function () {
        const selectedType = contentTypeDropdown.value;
        fetch("/json/content-type.json")
            .then(response => response.json())
            .then(data => {
                loadContentType(selectedType, data);
            });
    });

    // Function to load content type data
    function loadContentType(type, contentTypeData) {
        const contentPath = `/ajax/load-content?type=${type}`; // Pass type as a query parameter
        table.ajax.url(contentPath).load();
    }

    // Render functions for DataTable
    function renderThumbnail(data, type, row) {
        const videoLink = `/video/${row.alias}`;
        if (data && data !== "No Thumbnail") {
            return `<a href="${videoLink}" target="_blank"><img src="${data}" alt="Thumbnail" class="w-16 h-16 object-cover"></a>`;
        }
        return "No Thumbnail";
    }

    function renderEditButton(data, type, row) {
        return `<a href="edit-video?id=${row.unique_id}" class="btn edit-btn px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>`;
    }

    function renderDeleteButton(data, type, row) {
        return `<button onclick="showDeleteModal('${row.unique_id}')" class="btn delete-btn px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>`;
    }

    // Handle Bulk Update
    $('#bulkUpdateButton').on('click', function () {
        const selectedItems = Array.from(document.querySelectorAll('.select-item:checked')).map(el => el.value);
        document.querySelector('#selectedItemsInput').value = JSON.stringify(selectedItems);
        document.querySelector('#bulkForm').submit();
    });

    // Select All / Deselect All functionality
    const selectAllButton = document.getElementById("selectAllButton");
    const deselectAllButton = document.getElementById("deselectAllButton");

    selectAllButton.addEventListener("click", function () {
        document.querySelectorAll('.select-item').forEach(checkbox => {
            checkbox.checked = true;
        });
    });

    deselectAllButton.addEventListener("click", function () {
        document.querySelectorAll('.select-item').forEach(checkbox => {
            checkbox.checked = false;
        });
    });

    // Handle the "Select All" checkbox functionality
    const selectAllCheckbox = document.getElementById("selectAll");

    // Use event delegation to handle dynamically added checkboxes
    document.addEventListener("change", function (event) {
        if (event.target && event.target.classList.contains("select-item")) {
            // Update "Select All" checkbox based on individual checkboxes
            const allCheckboxes = document.querySelectorAll(".select-item");
            const allChecked = [...allCheckboxes].every(checkbox => checkbox.checked);
            const someChecked = [...allCheckboxes].some(checkbox => checkbox.checked);

            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && someChecked;
        }
    });

    // Handle "Select All" checkbox
    selectAllCheckbox.addEventListener("change", function () {
        const rowCheckboxes = document.querySelectorAll(".select-item");
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    });
});
</script>

<div class="<?php echo $mainContainerClass ?>">
    <h1 class="text-3xl font-bold text-sunset-yellow">Manage Content</h1>

    <?php if (!empty($errorMessage)): ?>
        <div class="bg-red-500 text-white p-4 mt-4 rounded">Error: <?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="bg-green-500 text-white p-4 mt-4 rounded">Success: <?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <!-- Content Type Dropdown -->
    <div class="mb-6">
        <label for="contentType" class="block text-sm font-medium text-gray-300 mb-2">Select Content Type</label>
        <select id="contentType" class="block w-full p-2 bg-gray-700 text-gray-300 rounded">
            <!-- Options will be populated dynamically -->
        </select>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto mt-6">
        <table id="contentTable" class="min-w-full bg-gray-800 text-gray-300 display">
            <thead>
                <tr class="bg-gray-700">
                    <th class="w-[5%]" style="text-align:center!important"><input type="checkbox" id="selectAll"></th>
                    <th class="w-[10%]">Thumbnail</th>
                    <th class="w-[35%]">Title</th>
                    <th class="w-auto">User</th>
                    <th class="w-auto">Updated Date</th>
                    <th class="w-auto">Edit</th>
                    <th class="w-auto">Delete</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Select All / Deselect All Buttons -->
    <div class="flex justify-start">
        <button id="selectAllButton" class="mr-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Select All</button>
        <button id="deselectAllButton" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Deselect All</button>
    </div>

    <!-- Bulk Action Form -->
    <form id="bulkForm" method="post" action="" class="mt-6">
        <input type="hidden" name="selected_items" id="selectedItemsInput">
        <select name="bulk_action" class="block w-full p-2 bg-gray-700 text-gray-300 rounded mb-4">
            <option value="update_content_json">Update content.json for selected items</option>
        </select>
        <button id="bulkUpdateButton" type="button" class="bg-sunset-yellow text-black px-4 py-2 rounded shadow hover:bg-yellow-600">
            Bulk Update
        </button>
    </form>
</div>

<?php include 'footer.php'; ?>