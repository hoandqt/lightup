<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$categoriesFile = __DIR__ . '/json/video-category.json';
$categoriesData = json_decode(file_get_contents($categoriesFile), true);

// Directory for uploaded images
$imageDir = __DIR__ . '/images/category';
if (!file_exists($imageDir)) {
  mkdir($imageDir, 0777, true);
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$action) {
  echo json_encode(['success' => false, 'message' => 'No action specified']);
  exit;
}

switch ($action) {
  case 'add':
    $name = $_POST['name'] ?? '';
    $metaTitle = $_POST['meta_title'] ?? '';
    $description = $_POST['description'] ?? '';
    $pathAlias = $_POST['path_alias'] ?? '';

    if (empty($id) || empty($name) || empty($metaTitle) || empty($pathAlias)) {
      echo json_encode(['success' => false, 'message' => 'ID, Name, Meta Title and Path Alias are required']);
      exit;
    }

    $imagePath = null;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
      $imageTmpName = $_FILES['category_image']['tmp_name'];
      $imageExtension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
      $imageName = $pathAlias . '.' . $imageExtension;
      $targetPath = $imageDir . '/' . $imageName;

      if (move_uploaded_file($imageTmpName, $targetPath)) {
        $imagePath = 'images/category/' . $imageName;
      } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload the image.']);
        exit;
      }
    }

    $categoriesData[$id] = [
      'name' => $name,
      'meta_title' => $metaTitle,
      'category_description' => $description,
      'path_alias' => $pathAlias,
      'image' => $imagePath,
      'subcategories' => []
    ];

    file_put_contents($categoriesFile, json_encode($categoriesData, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
    break;

  case 'edit':
    $oldId = $_POST['old_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $metaTitle = $_POST['meta_title'] ?? '';
    $description = $_POST['description'] ?? '';
    $pathAlias = $_POST['path_alias'] ?? '';

    if (empty($oldId) || empty($id)) {
      echo json_encode(['success' => false, 'message' => 'Old ID and New ID are required']);
      exit;
    }

    if (!isset($categoriesData[$oldId])) {
      echo json_encode(['success' => false, 'message' => 'Category not found']);
      exit;
    }

    if (empty($name) || empty($pathAlias)) {
      echo json_encode(['success' => false, 'message' => 'Name and Path Alias are required']);
      exit;
    }

    if (empty($metaTitle)) {
      echo json_encode(['success' => false, 'message' => 'Meta Title is required']);
      exit;
    }

    if ($oldId !== $id && isset($categoriesData[$id])) {
      echo json_encode(['success' => false, 'message' => 'New ID already exists']);
      exit;
    }

    $imagePath = $categoriesData[$oldId]['image'] ?? null;
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
      $imageTmpName = $_FILES['category_image']['tmp_name'];
      $imageExtension = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
      $imageName = $pathAlias . '.' . $imageExtension;
      $targetPath = $imageDir . '/' . $imageName;

      if (move_uploaded_file($imageTmpName, $targetPath)) {
        $imagePath = 'images/category/' . $imageName;
      } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload the image.']);
        exit;
      }
    }

    $categoriesData[$id] = [
      'name' => $name,
      'meta_title' => $metaTitle,
      'category_description' => $description,
      'path_alias' => $pathAlias,
      'image' => $imagePath,
      'subcategories' => $categoriesData[$oldId]['subcategories'] ?? []
    ];

    if ($oldId !== $id) {
      unset($categoriesData[$oldId]);
    }

    file_put_contents($categoriesFile, json_encode($categoriesData, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    break;

  case 'delete':
    if (!isset($categoriesData[$id])) {
      echo json_encode(['success' => false, 'message' => 'Category not found']);
      exit;
    }

    // Check and delete the associated image file
    $imagePath = $categoriesData[$id]['image'] ?? null;
    if ($imagePath) {
      $fullImagePath = __DIR__ . '/' . $imagePath;
      if (file_exists($fullImagePath)) {
        unlink($fullImagePath);
      }
    }

    // Remove the category from the data
    unset($categoriesData[$id]);
    file_put_contents($categoriesFile, json_encode($categoriesData, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'message' => 'Category and associated image deleted successfully']);
    break;

  case 'get':
    if (!isset($categoriesData[$id])) {
      echo json_encode(['success' => false, 'message' => 'Category not found']);
      exit;
    }

    echo json_encode(['success' => true, 'category' => $categoriesData[$id]]);
    break;

  default:
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    break;
}
