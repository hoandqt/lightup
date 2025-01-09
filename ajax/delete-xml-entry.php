<?php
// Ensure the user is authenticated and authorized
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) { // Ensure 'id' matches the JS key
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$sitemapFile = '../sitemap.xml';

// Load the existing sitemap
if (!file_exists($sitemapFile)) {
    http_response_code(404);
    echo json_encode(["error" => "Sitemap not found"]);
    exit;
}

$sitemap = simplexml_load_file($sitemapFile);
$dom = dom_import_simplexml($sitemap);

$found = false;

// Iterate through all <url> elements and remove the matching one
foreach ($sitemap->url as $url) {
    if ((string)$url->loc === $data['id']) {
        $domUrl = dom_import_simplexml($url);
        $domUrl->parentNode->removeChild($domUrl);
        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(["error" => "Entry not found"]);
    exit;
}

// Save the updated sitemap
$sitemap->asXML($sitemapFile);

echo json_encode(["success" => true, "message" => "Entry deleted"]);
