<?php
http_response_code(404);

$pageTitle = "Page Not Found";
$pageDescription = "Page Not Found - LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/404";
include 'header.php';
include 'menu.php';
?>

<div class="<?php echo $mainContainerClass ?> error-container">
    <h1 class="text-3xl font-bold text-text-light mb-6">Page Not Found</h1>
    <p>Sorry, the page you're looking for doesn't exist.</p>
    <a href="/" style="text-decoration: none;" class="inline-flex gap-2 items-center mt-5">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6" class="inline-block h-5">
        <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
      </svg> Go back to Home</a>
</div>

<?php include 'footer.php'; ?>