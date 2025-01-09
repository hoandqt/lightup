<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$sitemapFile = '../sitemap.xml';
$sitemapEntries = file_exists($sitemapFile) ? simplexml_load_file($sitemapFile) : [];

foreach ($sitemapEntries->url as $entry) {
    echo "<tr>
            <td class='px-6 py-3'><a href='".htmlspecialchars($entry->loc)."'>" . htmlspecialchars($entry->loc) . "</a></td>
            <td class='px-6 py-3'>" . htmlspecialchars($entry->lastmod) . "</td>
            <td class='px-6 py-3'>" . htmlspecialchars($entry->priority) . "</td>
            <td class='px-6 py-3'>
                <button class='editSitemapEntryButton bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded' data-path='" . htmlspecialchars($entry->loc) . "'>Edit</button>
                <button class='deleteSitemapEntryButton bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded' data-path='" . htmlspecialchars($entry->loc) . "'>Delete</button>
            </td>
          </tr>";
}
