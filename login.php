<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $redirectUrl = $_GET['redirect'] ?? '/';
    header("Location: $redirectUrl");
    exit;
}

// Get the redirect URL from the query parameter
$redirectUrl = $_GET['redirect'] ?? '/';
$pageTitle = "Login to LightUp.TV";
$pageDescription = "Login to LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/login";
include 'header.php';
include 'menu.php';
?>

<div class="min-h-screen flex items-center justify-center bg-dark-gray text-text-light py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-gray-800 p-8 shadow-lg rounded-lg">
        <h2 class="text-center text-3xl font-extrabold text-sunset-yellow">Login to LightUp.TV</h2>
        <p class="mt-2 text-center text-sm text-sunset-orange">
            Welcome back! Please enter your credentials.
        </p>
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="mt-4 text-red-500 text-sm text-center">
                Invalid username or password. Please try again.
            </div>
        <?php endif; ?>
        <form class="mt-8 space-y-6" action="authenticate" method="POST">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectUrl); ?>">
            <div class="rounded-md shadow-sm -space-y-px">
                <!-- Username -->
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" required
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-700 bg-gray-900 placeholder-sunset-orange text-sunset-yellow rounded-t-md focus:outline-none focus:ring-sunset-orange focus:border-sunset-yellow focus:z-10 sm:text-sm"
                        placeholder="Username">
                </div>
                <!-- Password -->
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-700 bg-gray-900 placeholder-sunset-orange text-sunset-yellow rounded-b-md focus:outline-none focus:ring-sunset-orange focus:border-sunset-yellow focus:z-10 sm:text-sm"
                        placeholder="Password">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="text-sm">
                    <a href="#" class="font-medium text-sunset-orange hover:text-sunset-yellow">Forgot your password?</a>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-dark-gray bg-sunset-yellow hover:bg-sunset-orange focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sunset-orange">
                    Login
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
