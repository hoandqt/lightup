<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$sitemapFile = '../sitemap.xml';

if (!file_exists($sitemapFile)) {
    exit(json_encode(['success' => false, 'message' => 'Sitemap file not found']));
}

$sitemap = simplexml_load_file($sitemapFile);
$updated = false;

foreach ($sitemap->url as $url) {
    if ((string)$url->loc === $data['oldPath']) {
        $url->loc = $data['newPath'];
        $url->priority = $data['priority'];
        $url->lastmod = date('Y-m-d');
        $updated = true;
        break;
    }
}

if ($updated) {
    $sitemap->asXML($sitemapFile);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Entry not found']);
}
