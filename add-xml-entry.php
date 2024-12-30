<?php
require_once 'functions.php';

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['path']) || !isset($data['priority'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$sitemapFile = __DIR__ . '/sitemap.xml';

if (!is_writable(dirname($sitemapFile))) {
    error_log('Directory not writable');
    echo json_encode(["error" => "Directory not writable"]);
    exit;
}

libxml_use_internal_errors(true);
if (file_exists($sitemapFile)) {
    $sitemap = simplexml_load_file($sitemapFile);
    if ($sitemap === false) {
        foreach (libxml_get_errors() as $error) {
            error_log($error->message);
        }
        echo json_encode(["error" => "Invalid sitemap XML"]);
        exit;
    }
} else {
    $sitemap = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
}

$found = false;
foreach ($sitemap->url as $url) {
    if ((string) $url->loc === $data['path']) {
        // Update existing entry
        $url->lastmod = date('Y-m-d');
        $url->priority = $data['priority'];
        $found = true;
        break;
    }
}

if (!$found) {
    // Add new entry
    $url = $sitemap->addChild('url');
    $url->addChild('loc', $siteURL . htmlspecialchars($data['path'], ENT_XML1, 'UTF-8'));
    $url->addChild('lastmod', date('Y-m-d'));
    $url->addChild('priority', $data['priority']);
}

if (!$sitemap->asXML($sitemapFile)) {
    error_log('Failed to save sitemap');
    echo json_encode(["error" => "Failed to save sitemap"]);
    exit;
}

echo json_encode(["success" => $found ? "Entry updated" : "Entry added"]);
