<?php
session_start();

$pageTitle = "Contact Us - LightUp.TV";
$pageDescription = "Get in touch with the LightUp.TV team! Have questions, feedback, or collaboration ideas? Visit our Contact Us page for quick support and connections. We're here to help!";
$pageKeywords = "LightUp.TV contact, contact LightUp.TV, LightUp.TV support, contact form, LightUp.TV inquiries, feedback, collaboration, LightUp.TV help, get in touch, customer service";
$canonicalURL = "https://www.lightup.tv/contact";
include 'header.php';
include 'menu.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));
    $timestamp = date('Y-m-d H:i:s');

    // Validate form fields
    if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($message)) {
        $data = [
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'timestamp' => $timestamp,
        ];

        $filePath = '../json/contact.json';

        // Read existing data and append new entry
        if (file_exists($filePath)) {
            $existingData = json_decode(file_get_contents($filePath), true) ?: [];
        } else {
            $existingData = [];
        }

        $existingData[] = $data;

        // Save back to file
        file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT));

        $successMessage = "Thank you for reaching out! We will get back to you soon.";
    } else {
        $errorMessage = "Please fill out all fields correctly.";
    }
}
?>

<div class="container mx-auto p-8">
  <h1 class="text-3xl font-bold text-sunset-yellow">Contact Us</h1>
  <p class="text-gray-300 mt-4">Have a question, feedback, or an idea to share? We'd love to hear from you! Fill out the form below, and our team will get back to you as soon as possible.</p>

  <?php if (!empty($successMessage)): ?>
    <div class="bg-green-500 text-white p-4 rounded mt-4">
      <?= $successMessage ?>
    </div>
  <?php elseif (!empty($errorMessage)): ?>
    <div class="bg-red-500 text-white p-4 rounded mt-4">
      <?= $errorMessage ?>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="bg-gray-800 p-6 rounded mt-6 shadow-md">
    <div class="mb-4">
      <label for="name" class="block text-gray-300 font-medium">Your Name</label>
      <input type="text" id="name" name="name" class="w-full p-2 rounded bg-gray-700 text-white" required>
    </div>
    <div class="mb-4">
      <label for="email" class="block text-gray-300 font-medium">Your Email</label>
      <input type="email" id="email" name="email" class="w-full p-2 rounded bg-gray-700 text-white" required>
    </div>
    <div class="mb-4">
      <label for="message" class="block text-gray-300 font-medium">Your Message</label>
      <textarea id="message" name="message" class="w-full p-2 rounded bg-gray-700 text-white" rows="5" required></textarea>
    </div>
    <button type="submit" class="bg-sunset-yellow text-gray-800 font-bold py-2 px-4 rounded hover:bg-yellow-600">
      Submit
    </button>
  </form>
</div>

<?php include 'footer.php'; ?>
