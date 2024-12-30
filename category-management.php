<?php
session_start();

require_once "functions.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
  header("Location: login.php?redirect=$redirectUrl");
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
$categoriesFile = 'json/video-category.json';
$categoriesData = json_decode(file_get_contents($categoriesFile), true);

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle form submissions for CRUD operations
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'add':
        if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['path_alias'])) {
          $errorMessage = 'Error: ID, Name, and Path Alias are required fields.';
        } else {
          $newCategory = [
            'name' => $_POST['name'],
            'category_description' => $_POST['description'] ?? '',
            'path_alias' => $_POST['path_alias'],
            'subcategories' => []
          ];
          $categoriesData[$_POST['id']] = $newCategory;
          file_put_contents($categoriesFile, json_encode($categoriesData, JSON_PRETTY_PRINT));
          header('Location: category-management');
          exit;
        }
        break;

      case 'edit':
        $id = $_POST['id'];
        if (empty($_POST['name']) || empty($_POST['path_alias'])) {
          $errorMessage = 'Error: Name and Path Alias are required fields.';
        } else {
          $categoriesData[$id]['name'] = $_POST['name'];
          $categoriesData[$id]['category_description'] = $_POST['description'] ?? '';
          $categoriesData[$id]['path_alias'] = $_POST['path_alias'];
          file_put_contents($categoriesFile, json_encode($categoriesData, JSON_PRETTY_PRINT));
          header('Location: category-management');
          exit;
        }
        break;

      case 'delete':
        $id = $_POST['id'];
        unset($categoriesData[$id]);
        file_put_contents($categoriesFile, json_encode($categoriesData, JSON_PRETTY_PRINT));
        header('Location: category-management');
        exit;
    }
  }
}

?>

<?php if (!empty($errorMessage)): ?>
  <div style="color: red; font-weight: bold;">
    <?php echo htmlspecialchars($errorMessage); ?>
  </div>
<?php endif; ?>

<div class="container mx-auto p-8 bg-gray-900 text-white">
  <div class="flex items-center mb-6">
    <h1 class="text-3xl font-bold text-sunset-yellow">Manage Categories</h1>
  </div>

  <table id="categoriesTable" class="table-auto border-collapse w-full bg-gray-800 text-white">
    <thead>
      <tr>
        <th class="border px-4 py-2"><input type="checkbox" id="selectAll"></th>
        <th class="border px-4 py-2">ID</th>
        <th class="border px-4 py-2">Name</th>
        <th class="border px-4 py-2">Description</th>
        <th class="border px-4 py-2">Path Alias</th>
        <th class="border px-4 py-2">Subcategories</th>
        <th class="border px-4 py-2">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categoriesData as $id => $category): ?>
        <tr>
          <td class="border px-4 py-2"><input type="checkbox" class="selectRow"></td>
          <td class="border px-4 py-2"><?php echo htmlspecialchars($id); ?></td>
          <td class="border px-4 py-2"><?php echo htmlspecialchars($category['name']); ?></td>
          <td class="border px-4 py-2"><?php echo htmlspecialchars($category['category_description']); ?></td>
          <td class="border px-4 py-2"><?php echo htmlspecialchars($category['path_alias']); ?></td>
          <td class="border px-4 py-2">
            <?php foreach ($category['subcategories'] as $subcategory): ?>
              <div><?php echo htmlspecialchars($subcategory['name']); ?></div>
            <?php endforeach; ?>
          </td>
          <td class="border px-4 py-2">
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?php echo $id; ?>">
              <button class="bg-blue-500 text-white px-2 py-1 rounded" type="submit">Edit</button>
            </form>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $id; ?>">
              <button class="bg-red-500 text-white px-2 py-1 rounded" type="submit" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Select All / Deselect All Buttons -->
  <div class="flex justify-start mt-4">
    <button id="selectAllButton" class="mr-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Select All</button>
    <button id="deselectAllButton" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Deselect All</button>
  </div>

  <h2 class="mt-6 text-2xl font-bold">Add New Category</h2>
  <form method="POST" class="mt-4">
    <input type="hidden" name="action" value="add">
    <div class="mb-4">
      <label for="id" class="block text-gray-400">ID:</label>
      <input type="text" id="id" name="id" required class="w-full p-2 bg-gray-700 border border-gray-600 rounded">
    </div>
    <div class="mb-4">
      <label for="name" class="block text-gray-400">Name:</label>
      <input type="text" id="name" name="name" required class="w-full p-2 bg-gray-700 border border-gray-600 rounded">
    </div>
    <div class="mb-4">
      <label for="description" class="block text-gray-400">Description:</label>
      <textarea id="description" name="description" class="w-full p-2 bg-gray-700 border border-gray-600 rounded"></textarea>
    </div>
    <div class="mb-4">
      <label for="path_alias" class="block text-gray-400">Path Alias:</label>
      <input type="text" id="path_alias" name="path_alias" required class="w-full p-2 bg-gray-700 border border-gray-600 rounded">
    </div>
    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Add Category</button>
  </form>
</div>

<!-- Include DataTables JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script src="js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
  $(document).ready(function() {
    $('#categoriesTable').DataTable({
      columnDefs: [
        { orderable: false, targets: [0, -1] }
      ]
    });

    $('#selectAllButton').click(function() {
      $('.selectRow').prop('checked', true);
    });

    $('#deselectAllButton').click(function() {
      $('.selectRow').prop('checked', false);
    });

    $('#selectAll').change(function() {
      $('.selectRow').prop('checked', $(this).prop('checked'));
    });
  });
</script>

<?php include 'footer.php'; ?>
