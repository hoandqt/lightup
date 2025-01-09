<?php
require '../vendor/autoload.php'; // Include Google Cloud PHP client library

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\ImageSource;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\AnnotateImageRequest;

session_start();

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
  exit;
}

// Check if the user has the "admin" role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Forbidden. Only admins can perform this action.']);
  exit;
}

// Path to Google Cloud Vision service account key
putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/json/lightuptv-56628082a530.json');

// Function to generate alt text using Google Vision
function generateOgImageAlt($imageUrl) {
  $imageAnnotator = new ImageAnnotatorClient();

  try {
      // Create an ImageSource object
      $imageSource = (new ImageSource())->setImageUri($imageUrl);

      // Create an Image object
      $image = (new Image())->setSource($imageSource);

      // Define the feature to use (LABEL_DETECTION)
      $feature = (new Feature())->setType(Feature\Type::LABEL_DETECTION);

      // Create an AnnotateImageRequest
      $request = (new AnnotateImageRequest())
          ->setImage($image)
          ->setFeatures([$feature]); // Use setFeatures() instead of addFeatures()

      // Call the Vision API
      $response = $imageAnnotator->batchAnnotateImages([$request]);

      // Parse the response
      $labelAnnotations = $response->getResponses()[0]->getLabelAnnotations();
      if ($labelAnnotations) {
          $descriptions = [];
          foreach ($labelAnnotations as $label) {
              $descriptions[] = $label->getDescription();
          }
          // Convert the list of descriptions into a sentence
          return 'This image contains: ' . implode(', ', $descriptions) . '.';
      } else {
          return 'none';
      }
  } catch (Exception $e) {
      error_log('Error analyzing image: ' . $e->getMessage());
      return 'Error processing the image.';
  } finally {
      $imageAnnotator->close();
  }
}

// Main logic to handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
  $imageUrl = $_POST['imageUrl'] ?? $_GET['imageUrl'] ?? '';

  if (empty($imageUrl)) {
      echo 'No image URL provided.';
      exit;
  }

  // Generate and return the alt text
  $altText = generateOgImageAlt($imageUrl);
  echo htmlspecialchars($altText);
  exit;
} else {
  http_response_code(405); // Method Not Allowed
  echo 'Invalid request method. Only POST or GET allowed.';
  exit;
}