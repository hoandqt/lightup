<?php
session_start();

require_once "functions.php";

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('Location: /login.php');
  exit;
}

$pageTitle = "Category Management";
$pageDescription = "LightUp.TV Category Management";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/category-management";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Load the categories from the JSON file
$categoriesFile = __DIR__ . '/json/video-category.json';
$categoriesData = json_decode(file_get_contents($categoriesFile), true);
?>

<div class="<?php echo $mainContainerClass ?>">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-sunset-yellow">Manage Categories</h1>
    <button id="openModalButton" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Add Category</button>
  </div>

  <div class="overflow-x-auto mt-6">
    <table id="categoriesTable" class="min-w-full bg-gray-800 text-gray-300 display">
      <thead>
        <tr class="bg-gray-700">
          <th class="w-[5%]" style="text-align:center!important"><input type="checkbox" id="selectAll"></th>
          <th class="w-[5%]">ID</th>
          <th class="w-[15%]">Name</th>
          <th class="w-[35%]">Description</th>
          <th class="w-auto">Path Alias</th>
          <th class="w-auto">Sub Categories</th>
          <th class="w-auto">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categoriesData as $id => $category): ?>
          <tr class="hover:bg-gray-700">
            <td class="text-center"></td>
            <td><?php echo htmlspecialchars($id); ?></td>
            <td><a href="/video-category/<?php echo htmlspecialchars($category['path_alias']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></td>
            <td><?php echo htmlspecialchars($category['category_description']); ?></td>
            <td><?php echo htmlspecialchars($category['path_alias']); ?></td>
            <td>
              <?php foreach ($category['subcategories'] as $subcategory): ?>
                <div><?php echo htmlspecialchars($subcategory['name']); ?></div>
              <?php endforeach; ?>
            </td>
            <td>
              <div class="flex flex-wrap justify-center items-center gap-2">
                <button class="w-full editCategoryButton bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded" data-id="<?php echo $id; ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>" data-meta-title="<?php echo htmlspecialchars($category['meta_title']); ?>" data-description="<?php echo htmlspecialchars($category['category_description']); ?>" data-path-alias="<?php echo htmlspecialchars($category['path_alias']); ?>" data-image="<?php echo htmlspecialchars($category['image']) ?>">Edit</button>
                <button class="w-full deleteCategoryButton bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded" data-id="<?php echo $id; ?>">Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="flex justify-start mt-4">
    <button id="selectAllButton" class="mr-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Select All</button>
    <button id="deselectAllButton" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Deselect All</button>
  </div>
</div>

<!-- Modal -->
<div id="categoryModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-gray-800 text-white p-8 rounded-lg w-full sm:w-1/2 relative">
    <!-- Close Button -->
    <button 
      id="closeModalTopButton" 
      class="absolute top-5 right-5 bg-gray-600 hover:bg-gray-700 text-white rounded-full w-8 h-8 flex items-center justify-center"
      aria-label="Close Modal">
      &times;
    </button>

    <h2 id="modalTitle" class="text-2xl font-bold mb-4">Add Category</h2>
    <form id="categoryForm" action="category-action" method="POST" enctype="multipart/form-data">
      <input type="hidden" id="oldCategoryId" name="old_id">
      <input type="hidden" id="modalAction" name="action" value="add">
      <div class="mb-4">
        <label for="modalName" class="block text-gray-400">ID:</label>
        <input type="number" id="categoryId" name="id" class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
      </div>
      <div class="mb-4">
        <label for="modalName" class="block text-gray-400">Name:</label>
        <input type="text" id="modalName" name="name" class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
      </div>
      <div class="mb-4">
        <label for="modalMetaTitle" class="block text-gray-400">Meta Title:</label>
        <input type="text" id="modalMetaTitle" name="meta_title" class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
      </div>
      <div class="mb-4">
        <label for="modalDescription" class="block text-gray-400">Description:</label>
        <textarea id="modalDescription" name="description" class="w-full p-2 bg-gray-700 border border-gray-600 rounded"></textarea>
      </div>
      <div class="mb-4">
        <label for="modalPathAlias" class="block text-gray-400">Path Alias:</label>
        <input type="text" id="modalPathAlias" name="path_alias" class="w-full p-2 bg-gray-700 border border-gray-600 rounded" required>
      </div>
      <div class="mb-4">
        <label for="categoryImage" class="block text-gray-400">Category Image:</label>
        <input type="file" id="categoryImage" name="category_image" class="w-full p-2 bg-gray-700 border border-gray-600 rounded" accept="image/*">
        <div id="previewImg" class="hidden">
          <label class="block text-gray-400">Preview:</label>
          <img src="" id="categoryImagePreview" class="hidden h-20"/>
        </div>
      </div>
      <div class="flex justify-end">
        <button type="button" id="closeModalButton" class="mr-4 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Save</button>
      </div>
    </form>
  </div>
</div>

<div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
  <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
    <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
    <p>Are you sure you want to delete this category?</p>
    <div class="flex justify-end mt-4">
      <button id="cancelDeleteCategory" class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
      <button id="confirmDeleteCategory" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
    </div>
  </div>
</div>

<script src="/js/jquery-3.7.1.min.js"></script>
<script src="//cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="/js/manage-category.js?v=<?php echo rand(1000, 9999); ?>"></script>

<link rel="stylesheet" href="//cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<?php include 'footer.php'; ?>
