<?php
session_start();

$credentialsFile = __DIR__ . '/json/credentials.json';

// Load credentials file
if (file_exists($credentialsFile)) {
    $credentialsData = json_decode(file_get_contents($credentialsFile), true);
    if (!$credentialsData || !isset($credentialsData['users'])) {
        $errorMessage = "Error: Malformed credentials file.";
        $credentialsData['users'] = [];
    }
} else {
    $errorMessage = "Error: Credentials file not found.";
    $credentialsData['users'] = [];
}

// Handle POST login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'])); // Normalize username
    $password = $_POST['password'];

    $userFound = false;
    $userDetails = null;

    // Loop through the users to find a match
    foreach ($credentialsData['users'] as $user) {
        if (strtolower($user['username']) === $username) {
            $userFound = true;
            $userDetails = $user;
            break;
        }
    }

    if ($userFound) {
        // Verify password
        if (password_verify($password, $userDetails['password'])) {
            // Set session variables
            $_SESSION['username'] = $userDetails['username'];
            $_SESSION['loggedin'] = true;
            $_SESSION['role'] = $userDetails['role'];

            // Redirect to specified or default page
            $redirectUrl = $_POST['redirect'] ?? 'index.php';
            header("Location: $redirectUrl");
            exit;
        } else {
            $errorMessage = "Invalid password.";
        }
    } else {
        $errorMessage = "Username not found.";
    }
}
?>

<?php if (isset($errorMessage)): ?>
    <div class="site-notification text-red-500 error"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<?php if (isset($successMessage)): ?>
    <div class="site-notification text-green-500 success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>
